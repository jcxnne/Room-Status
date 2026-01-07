<?php
// Disable error display for AJAX requests - errors will be logged instead
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();

// Database configuration
$host = 'localhost';
$user_db = 'user_db';
$reservation_db = 'reservation';
$notification_db = 'notification';
$username = 'root';
$password = '';

try {
    // Connection for user authentication
    $pdo_user = new PDO("mysql:host=$host;dbname=$user_db", $username, $password);
    $pdo_user->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Connection for reservations
    $pdo = new PDO("mysql:host=$host;dbname=$reservation_db", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Connection for notifications (optional)
    $pdo_notification = null;
    try {
        $pdo_notification = new PDO("mysql:host=$host;dbname=$notification_db", $username, $password);
        $pdo_notification->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch(PDOException $e) {
        // Notification database not available, continue without it
        error_log("Notification database not available: " . $e->getMessage());
    }
} catch(PDOException $e) {
    // If this is an AJAX request, return JSON error
    if (isset($_POST['action'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit();
    }
    die("Connection failed: " . $e->getMessage());
}

// Include notification helper (optional - system works without it)
$notificationHelper = null;
if ($pdo_notification && file_exists('notification_helper.php')) {
    require_once 'notification_helper.php';
    try {
        $notificationHelper = new NotificationHelper($pdo_notification);
    } catch (Exception $e) {
        error_log("Notification helper error: " . $e->getMessage());
    }
}

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin-login.php");
    exit();
}

$user_id = $_SESSION['admin_id'];

// Fetch admin user data
$sql = "SELECT * FROM admins WHERE id = ?";
$stmt = $pdo_user->prepare($sql);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: admin-login.php");
    exit();
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Clear any output buffers to prevent HTML from being sent before JSON
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    header('Content-Type: application/json');
    
    $action = $_POST['action'];
    
    try {
        switch ($action) {
            case 'add_reservation':
                $room = $_POST['room'];
                $faculty_name = $_POST['faculty'];
                $section = $_POST['section'];
                $date = $_POST['date'];
                $start_time = $_POST['start_time'];
                $end_time = $_POST['end_time'];
                $purpose = $_POST['purpose'];
                $status = $_POST['status'];
                $building = $_POST['building'] ?? 'New Building';
                
                // Check for conflicts
                $check_sql = "SELECT * FROM reservations 
                             WHERE room = ? 
                             AND date = ? 
                             AND status != 'cancelled'
                             AND status != 'rejected'
                             AND (
                                 (start_time <= ? AND end_time > ?) OR
                                 (start_time < ? AND end_time >= ?) OR
                                 (start_time >= ? AND end_time <= ?)
                             )";
                $check_stmt = $pdo->prepare($check_sql);
                $check_stmt->execute([
                    $room, $date, 
                    $start_time, $start_time,
                    $end_time, $end_time,
                    $start_time, $end_time
                ]);
                
                if ($check_stmt->rowCount() > 0) {
                    $time = date('g:i A', strtotime($start_time)) . ' - ' . date('g:i A', strtotime($end_time));
                    
                    // Send notification if helper exists
                    if ($notificationHelper) {
                        $notificationHelper->notifyRoomDoubleBooking(
                            $room,
                            $building,
                            date('l', strtotime($date)),
                            $time,
                            'Conflicting reservation attempt',
                            $user_id
                        );
                    }
                    
                    echo json_encode(['success' => false, 'message' => 'Room is already reserved for the selected time slot']);
                    exit();
                }
                
                // Insert reservation
                $insert_sql = "INSERT INTO reservations (user_id, faculty_name, room, date, start_time, end_time, subject_code, building, purpose, status, created_at) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                $insert_stmt = $pdo->prepare($insert_sql);
                $insert_stmt->execute([$user_id, $faculty_name, $room, $date, $start_time, $end_time, $section, $building, $purpose, $status]);
                
                // Send notification if helper exists
                if ($notificationHelper) {
                    try {
                        $time = date('g:i A', strtotime($start_time)) . ' - ' . date('g:i A', strtotime($end_time));
                        $formatted_date = date('F d, Y', strtotime($date));
                        $notificationHelper->notifyRoomReservation(
                            $room,
                            $building,
                            $faculty_name,
                            $purpose,
                            $formatted_date,
                            $time,
                            $status,
                            $user_id
                        );
                    } catch (Exception $e) {
                        error_log("Notification error: " . $e->getMessage());
                    }
                }
                
                echo json_encode(['success' => true, 'message' => 'Reservation added successfully']);
                break;
                
            case 'approve_reservation':
                $reservation_id = $_POST['reservation_id'];
                
                // Get reservation details first
                $sql = "SELECT * FROM reservations WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$reservation_id]);
                $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$reservation) {
                    echo json_encode(['success' => false, 'message' => 'Reservation not found']);
                    exit();
                }
                
                // Check for conflicts with already approved reservations
                $check_sql = "SELECT * FROM reservations 
                             WHERE room = ? 
                             AND date = ? 
                             AND id != ?
                             AND status = 'approved'
                             AND (
                                 (start_time <= ? AND end_time > ?) OR
                                 (start_time < ? AND end_time >= ?) OR
                                 (start_time >= ? AND end_time <= ?)
                             )";
                $check_stmt = $pdo->prepare($check_sql);
                $check_stmt->execute([
                    $reservation['room'], 
                    $reservation['date'], 
                    $reservation_id,
                    $reservation['start_time'], $reservation['start_time'],
                    $reservation['end_time'], $reservation['end_time'],
                    $reservation['start_time'], $reservation['end_time']
                ]);
                
                if ($check_stmt->rowCount() > 0) {
                    echo json_encode(['success' => false, 'message' => 'Cannot approve: Time slot conflicts with another approved reservation']);
                    exit();
                }
                
                // Update status to approved
                $update_sql = "UPDATE reservations SET status = 'approved' WHERE id = ?";
                $update_stmt = $pdo->prepare($update_sql);
                $update_stmt->execute([$reservation_id]);
                
                // Send notification if helper exists
                if ($notificationHelper) {
                    try {
                        $time = date('g:i A', strtotime($reservation['start_time'])) . ' - ' . date('g:i A', strtotime($reservation['end_time']));
                        $formatted_date = date('F d, Y', strtotime($reservation['date']));
                        $notificationHelper->notifyRoomReservation(
                            $reservation['room'],
                            $reservation['building'],
                            $reservation['faculty_name'],
                            $reservation['purpose'],
                            $formatted_date,
                            $time,
                            'approved',
                            $user_id
                        );
                    } catch (Exception $e) {
                        error_log("Notification error: " . $e->getMessage());
                    }
                }
                
                echo json_encode(['success' => true, 'message' => 'Reservation approved successfully']);
                break;
                
            case 'reject_reservation':
                $reservation_id = $_POST['reservation_id'];
                
                // Get reservation details
                $sql = "SELECT * FROM reservations WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$reservation_id]);
                $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$reservation) {
                    echo json_encode(['success' => false, 'message' => 'Reservation not found']);
                    exit();
                }
                
                // Update status
                $sql = "UPDATE reservations SET status = 'rejected' WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$reservation_id]);
                
                // Send notification if helper exists
                if ($notificationHelper) {
                    try {
                        $time = date('g:i A', strtotime($reservation['start_time'])) . ' - ' . date('g:i A', strtotime($reservation['end_time']));
                        $formatted_date = date('F d, Y', strtotime($reservation['date']));
                        $notificationHelper->notifyRoomReservation(
                            $reservation['room'],
                            $reservation['building'],
                            $reservation['faculty_name'],
                            $reservation['purpose'],
                            $formatted_date,
                            $time,
                            'rejected',
                            $user_id
                        );
                    } catch (Exception $e) {
                        error_log("Notification error: " . $e->getMessage());
                    }
                }
                
                echo json_encode(['success' => true, 'message' => 'Reservation rejected successfully']);
                break;
                
            case 'delete_reservation':
                $reservation_id = $_POST['reservation_id'];
                
                $sql = "DELETE FROM reservations WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$reservation_id]);
                
                echo json_encode(['success' => true, 'message' => 'Reservation deleted successfully']);
                break;
        }
    } catch (PDOException $e) {
        error_log("Database error in admin-reservation.php: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        error_log("General error in admin-reservation.php: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit();
}

// Fetch all reservations from database
$sql = "SELECT * FROM reservations ORDER BY date DESC, start_time DESC";
$reservations = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Calculate stats
$pending_count = 0;
$approved_count = 0;
$rejected_count = 0;

foreach ($reservations as $reservation) {
    switch ($reservation['status']) {
        case 'pending':
            $pending_count++;
            break;
        case 'approved':
            $approved_count++;
            break;
        case 'rejected':
            $rejected_count++;
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="admin-dashboard.css"> 
  <link rel="stylesheet" href="admin-reservation.css"> 
  <script src="https://unpkg.com/lucide@latest"></script>
  <title>Reservations - Admin Dashboard</title>
</head>
<body>
  <div class="sidebar">
    <!-- Profile Section -->
    <div class="profile">
      <a href="admin-profile.php" class="profile-link">
        <img src="<?php echo htmlspecialchars($user['profile_picture'] ?? 'https://freesvg.org/img/abstract-user-flat-3.png'); ?>" alt="Profile" />
      </a>
      <div class="profile-info">
        <h3><?php echo htmlspecialchars($user['full_name']); ?></h3>
        <p><?php echo htmlspecialchars($user['email']); ?><br>Administrator</p>
      </div>
    </div>

    <!-- Main Navigation Links -->
    <div class="nav-links nav-top">
      <a href="admin-dashboard.php">
        <i data-lucide="layout-dashboard"></i><span>Dashboard</span>
      </a>
      <a href="admin-notification.php">
        <i data-lucide="bell"></i><span>Notification</span>
      </a>
      <a href="admin-schedule.php">
        <i data-lucide="calendar"></i><span>Schedule</span>
      </a>
      <a href="admin-reservation.php" class="active">
        <i data-lucide="clipboard-check"></i><span>Reservation</span>
      </a>
      <a href="admin-audit.php">
        <i data-lucide="file-search"></i><span>Audit</span>
      </a>
      <a href="admin-report.php">
        <i data-lucide="bar-chart-3"></i><span>Reports</span>
      </a>
    </div>

    <!-- Bottom Navigation -->
    <div class="nav-links nav-bottom">
      <a id="darkModeToggle" href="#">
        <i data-lucide="moon" class="icon-moon"></i>
        <i data-lucide="sun" class="icon-sun" style="display: none;"></i>
        <span>Dark Mode</span>
      </a>
      <a href="login-signup.php">
        <i data-lucide="log-out"></i><span>Log Out</span>
      </a>
    </div>
  </div>

  <div class="main">
    <div class="main-header">
      <h1>Room Reservations</h1>
      <img src="logo.png" alt="UniSched Logo" class="logo">
    </div>

    <!-- Reservation Stats -->
    <div class="reservation-stats">
      <div class="stat-card pending">
        <i data-lucide="clock"></i>
        <div class="stat-info">
          <h3>Pending</h3>
          <p id="pendingCount"><?php echo $pending_count; ?></p>
        </div>
      </div>
      <div class="stat-card approved">
        <i data-lucide="check-circle"></i>
        <div class="stat-info">
          <h3>Approved</h3>
          <p id="approvedCount"><?php echo $approved_count; ?></p>
        </div>
      </div>
      <div class="stat-card rejected">
        <i data-lucide="x-circle"></i>
        <div class="stat-info">
          <h3>Rejected</h3>
          <p id="rejectedCount"><?php echo $rejected_count; ?></p>
        </div>
      </div>
      <div class="stat-card total">
        <i data-lucide="calendar-check"></i>
        <div class="stat-info">
          <h3>Total</h3>
          <p id="totalCount"><?php echo count($reservations); ?></p>
        </div>
      </div>
    </div>

    <!-- Reservation Controls -->
    <div class="reservation-controls">
      <div class="search-wrapper">
        <i data-lucide="search" class="search-icon"></i>
        <input type="text" placeholder="Search reservations..." class="search-bar" id="searchReservation" />
      </div>
      
      <select class="status-filter" id="statusFilter">
        <option value="all">All Status</option>
        <option value="pending">Pending</option>
        <option value="approved">Approved</option>
        <option value="rejected">Rejected</option>
      </select>

      <button class="action-btn primary" id="addReservationBtn">
        <i data-lucide="plus"></i> New Reservation
      </button>
    </div>

    <!-- Reservations Table -->
    <div class="table-container">
      <table class="reservations-table">
        <thead>
          <tr>
            <th><input type="checkbox" id="selectAll" /></th>
            <th>Room</th>
            <th>Faculty</th>
            <th>Section</th>
            <th>Date</th>
            <th>Time</th>
            <th>Purpose</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="reservationsBody">
          <?php foreach ($reservations as $reservation): ?>
            <tr class="reservation-row" data-status="<?php echo $reservation['status']; ?>" data-id="<?php echo $reservation['id']; ?>">
              <td><input type="checkbox" class="row-checkbox" /></td>
              <td><strong><?php echo htmlspecialchars($reservation['room']); ?></strong></td>
              <td><?php echo htmlspecialchars($reservation['faculty_name']); ?></td>
              <td><?php echo htmlspecialchars($reservation['subject_code']); ?></td>
              <td><?php echo date('M d, Y', strtotime($reservation['date'])); ?></td>
              <td><?php echo date('g:i A', strtotime($reservation['start_time'])) . ' - ' . date('g:i A', strtotime($reservation['end_time'])); ?></td>
              <td><?php echo htmlspecialchars($reservation['purpose']); ?></td>
              <td>
                <span class="status-badge <?php echo $reservation['status']; ?>">
                  <?php echo ucfirst($reservation['status']); ?>
                </span>
              </td>
              <td>
                <div class="action-buttons">
                  <?php if ($reservation['status'] === 'pending'): ?>
                    <button class="btn-icon approve-btn" title="Approve" data-id="<?php echo $reservation['id']; ?>">
                      <i data-lucide="check"></i>
                    </button>
                    <button class="btn-icon reject-btn" title="Reject" data-id="<?php echo $reservation['id']; ?>">
                      <i data-lucide="x"></i>
                    </button>
                  <?php endif; ?>
                  <button class="btn-icon view-btn" title="View Details" data-id="<?php echo $reservation['id']; ?>">
                    <i data-lucide="eye"></i>
                  </button>
                  <button class="btn-icon delete-btn" title="Delete" data-id="<?php echo $reservation['id']; ?>">
                    <i data-lucide="trash-2"></i>
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Add Reservation Modal -->
  <div class="modal" id="addReservationModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2><i data-lucide="plus-circle"></i> New Reservation</h2>
        <button class="close-btn" id="closeAddModal">
          <i data-lucide="x"></i>
        </button>
      </div>
      <div class="modal-body">
        <form id="addReservationForm">
          <div class="form-row">
            <div class="form-group">
              <label>Room Number</label>
              <input type="text" name="room" placeholder="e.g., 101" required />
            </div>
            <div class="form-group">
              <label>Building</label>
              <select name="building" required>
                <option value="New Building">New Building</option>
                <option value="Old Building">Old Building</option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Faculty Name</label>
              <input type="text" name="faculty" placeholder="Enter faculty name" required />
            </div>
            <div class="form-group">
              <label>Section</label>
              <input type="text" name="section" placeholder="e.g., BSCS 3-1" required />
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Date</label>
              <input type="date" name="date" required />
            </div>
            <div class="form-group">
              <label>Start Time</label>
              <input type="time" name="start_time" required />
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>End Time</label>
              <input type="time" name="end_time" required />
            </div>
            <div class="form-group">
              <label>Status</label>
              <select name="status" required>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
              </select>
            </div>
          </div>

          <div class="form-group full-width">
            <label>Purpose</label>
            <textarea name="purpose" rows="3" placeholder="Enter purpose of reservation" required></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn-cancel" id="cancelAddBtn">Cancel</button>
        <button class="btn-save" id="saveReservationBtn">
          <i data-lucide="save"></i> Save Reservation
        </button>
      </div>
    </div>
  </div>

  <!-- View Details Modal -->
  <div class="modal" id="detailsModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Reservation Details</h2>
        <button class="close-btn" id="closeDetailsModal">
          <i data-lucide="x"></i>
        </button>
      </div>
      <div class="modal-body">
        <div class="detail-group">
          <label>Room:</label>
          <p id="detailRoom">-</p>
        </div>
        <div class="detail-group">
          <label>Building:</label>
          <p id="detailBuilding">-</p>
        </div>
        <div class="detail-group">
          <label>Faculty:</label>
          <p id="detailFaculty">-</p>
        </div>
        <div class="detail-group">
          <label>Section:</label>
          <p id="detailSection">-</p>
        </div>
        <div class="detail-group">
          <label>Date:</label>
          <p id="detailDate">-</p>
        </div>
        <div class="detail-group">
          <label>Time:</label>
          <p id="detailTime">-</p>
        </div>
        <div class="detail-group">
          <label>Purpose:</label>
          <p id="detailPurpose">-</p>
        </div>
        <div class="detail-group">
          <label>Status:</label>
          <p><span class="status-badge pending" id="detailStatus">Pending</span></p>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn-cancel" id="closeDetailsBtn">Close</button>
      </div>
    </div>
  </div>

  <script>
    lucide.createIcons();

    // Dark Mode
    (function() {
      'use strict';

      function initDarkMode() {
        const isDarkMode = localStorage.getItem('darkMode') === 'true';
        if (isDarkMode) {
          document.body.classList.add('dark');
        }
        updateDarkModeUI(isDarkMode);
      }

      function updateDarkModeUI(isDark) {
        const toggle = document.getElementById('darkModeToggle');
        if (!toggle) return;

        const moonIcon = toggle.querySelector('.icon-moon');
        const sunIcon = toggle.querySelector('.icon-sun');
        const modeText = toggle.querySelector('span');

        if (moonIcon && sunIcon) {
          if (isDark) {
            moonIcon.style.display = 'none';
            sunIcon.style.display = 'inline';
          } else {
            moonIcon.style.display = 'inline';
            sunIcon.style.display = 'none';
          }
        }

        if (modeText) {
          modeText.textContent = isDark ? 'Light Mode' : 'Dark Mode';
        }
      }

      function toggleDarkMode(e) {
        e.preventDefault();
        const body = document.body;
        const isDark = body.classList.toggle('dark');
        localStorage.setItem('darkMode', isDark);
        updateDarkModeUI(isDark);
      }

      initDarkMode();
      
      const toggle = document.getElementById('darkModeToggle');
      if (toggle) {
        toggle.addEventListener('click', toggleDarkMode);
      }
    })();

    // Modal Management
    const addReservationModal = document.getElementById('addReservationModal');
    const detailsModal = document.getElementById('detailsModal');

    function openModal(modal) {
      modal.style.display = 'flex';
      setTimeout(() => {
        modal.classList.add('show');
      }, 10);
      lucide.createIcons();
    }

    function closeModal(modal) {
      modal.classList.remove('show');
      setTimeout(() => {
        modal.style.display = 'none';
      }, 300);
    }

    // Add Reservation
    document.getElementById('addReservationBtn').addEventListener('click', () => {
      openModal(addReservationModal);
    });

    document.getElementById('closeAddModal').addEventListener('click', () => {
      closeModal(addReservationModal);
    });

    document.getElementById('cancelAddBtn').addEventListener('click', () => {
      closeModal(addReservationModal);
    });

    // Save Reservation with reload
    document.getElementById('saveReservationBtn').addEventListener('click', async () => {
      const form = document.getElementById('addReservationForm');
      if (form.checkValidity()) {
        const formData = new FormData(form);
        formData.append('action', 'add_reservation');

        try {
          const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
          });

          const result = await response.json();

          if (result.success) {
            alert(result.message);
            form.reset();
            closeModal(addReservationModal);
            // Reload the page to show updated data
            location.reload();
          } else {
            alert('Error: ' + result.message);
          }
        } catch (error) {
          console.error('Error:', error);
          alert('Error creating reservation');
        }
      } else {
        form.reportValidity();
      }
    });

    // Approve/Reject/Delete Actions with reload
    document.addEventListener('click', async (e) => {
      if (e.target.closest('.approve-btn')) {
        const btn = e.target.closest('.approve-btn');
        const id = btn.dataset.id;
        
        console.log('Approving reservation ID:', id); // Debug log
        
        if (confirm('Approve this reservation?')) {
          const formData = new FormData();
          formData.append('action', 'approve_reservation');
          formData.append('reservation_id', id);
          
          try {
            const response = await fetch(window.location.href, {
              method: 'POST',
              body: formData
            });
            
            console.log('Response status:', response.status); // Debug log
            
            // Get response as text first to check if it's valid JSON
            const responseText = await response.text();
            console.log('Response text:', responseText); // Debug log
            
            let result;
            try {
              result = JSON.parse(responseText);
            } catch (jsonError) {
              console.error('JSON Parse Error:', jsonError);
              console.error('Response was:', responseText);
              alert('Server returned invalid response. Check console for details.');
              return;
            }
            
            console.log('Result:', result); // Debug log
            
            if (result.success) {
              alert(result.message);
              location.reload(); // Reload to update all data
            } else {
              alert('Error: ' + result.message);
            }
          } catch (error) {
            console.error('Error:', error);
            alert('Error approving reservation. Please check the console for details.');
          }
        }
      }
      
      if (e.target.closest('.reject-btn')) {
        const btn = e.target.closest('.reject-btn');
        const id = btn.dataset.id;
        
        console.log('Rejecting reservation ID:', id); // Debug log
        
        if (confirm('Reject this reservation?')) {
          const formData = new FormData();
          formData.append('action', 'reject_reservation');
          formData.append('reservation_id', id);
          
          try {
            const response = await fetch(window.location.href, {
              method: 'POST',
              body: formData
            });
            
            console.log('Response status:', response.status); // Debug log
            
            // Get response as text first to check if it's valid JSON
            const responseText = await response.text();
            console.log('Response text:', responseText); // Debug log
            
            let result;
            try {
              result = JSON.parse(responseText);
            } catch (jsonError) {
              console.error('JSON Parse Error:', jsonError);
              console.error('Response was:', responseText);
              alert('Server returned invalid response. Check console for details.');
              return;
            }
            
            console.log('Result:', result); // Debug log
            
            if (result.success) {
              alert(result.message);
              location.reload(); // Reload to update all data
            } else {
              alert('Error: ' + result.message);
            }
          } catch (error) {
            console.error('Error:', error);
            alert('Error rejecting reservation. Please check the console for details.');
          }
        }
      }
      
      if (e.target.closest('.delete-btn')) {
        const btn = e.target.closest('.delete-btn');
        const id = btn.dataset.id;
        
        console.log('Deleting reservation ID:', id); // Debug log
        
        if (confirm('Delete this reservation? This action cannot be undone.')) {
          const formData = new FormData();
          formData.append('action', 'delete_reservation');
          formData.append('reservation_id', id);
          
          try {
            const response = await fetch(window.location.href, {
              method: 'POST',
              body: formData
            });
            
            console.log('Response status:', response.status); // Debug log
            
            const result = await response.json();
            console.log('Result:', result); // Debug log
            
            if (result.success) {
              alert(result.message);
              location.reload(); // Reload to update all data
            } else {
              alert('Error: ' + result.message);
            }
          } catch (error) {
            console.error('Error:', error);
            alert('Error deleting reservation. Please check the console for details.');
          }
        }
      }
    });

    // Search and Filter
    const searchInput = document.getElementById('searchReservation');
    const statusFilter = document.getElementById('statusFilter');

    function filterReservations() {
      const searchQuery = searchInput.value.toLowerCase();
      const selectedStatus = statusFilter.value;
      const rows = document.querySelectorAll('.reservation-row');

      rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const status = row.dataset.status;
        
        const matchesSearch = text.includes(searchQuery);
        const matchesStatus = selectedStatus === 'all' || status === selectedStatus;

        if (matchesSearch && matchesStatus) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      });
    }

    searchInput.addEventListener('input', filterReservations);
    statusFilter.addEventListener('change', filterReservations);

    // View Details
    document.querySelectorAll('.view-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        const row = this.closest('tr');
        const cells = row.querySelectorAll('td');
        
        // Get data from PHP (more reliable than cell text)
        const reservationId = row.dataset.id;
        
        document.getElementById('detailRoom').textContent = cells[1].textContent.trim();
        document.getElementById('detailFaculty').textContent = cells[2].textContent.trim();
        document.getElementById('detailSection').textContent = cells[3].textContent.trim();
        document.getElementById('detailDate').textContent = cells[4].textContent.trim();
        document.getElementById('detailTime').textContent = cells[5].textContent.trim();
        document.getElementById('detailPurpose').textContent = cells[6].textContent.trim();
        
        // Get building from reservations data (you might need to add this to the PHP output)
        document.getElementById('detailBuilding').textContent = 'New Building'; // Default
        
        const statusBadge = row.querySelector('.status-badge');
        const detailStatusBadge = document.getElementById('detailStatus');
        detailStatusBadge.className = statusBadge.className;
        detailStatusBadge.textContent = statusBadge.textContent;
        
        openModal(detailsModal);
      });
    });

    document.getElementById('closeDetailsModal').addEventListener('click', () => {
      closeModal(detailsModal);
    });

    document.getElementById('closeDetailsBtn').addEventListener('click', () => {
      closeModal(detailsModal);
    });

    // Close modals when clicking outside
    window.addEventListener('click', (e) => {
      if (e.target === addReservationModal) {
        closeModal(addReservationModal);
      }
      if (e.target === detailsModal) {
        closeModal(detailsModal);
      }
    });

    // Set minimum date to today
    const dateInput = document.querySelector('input[type="date"]');
    if (dateInput) {
      dateInput.min = new Date().toISOString().split('T')[0];
    }

    // Select All Checkbox
    document.getElementById('selectAll').addEventListener('change', function() {
      const checkboxes = document.querySelectorAll('.row-checkbox');
      checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
      });
    });

    // Auto-refresh notification to show data updates
    let lastReservationCount = <?php echo count($reservations); ?>;
    
    async function checkForUpdates() {
      try {
        const response = await fetch('get_reservation_count.php');
        const data = await response.json();
        
        if (data.count !== lastReservationCount) {
          // Show notification that data has changed
          const notification = document.createElement('div');
          notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #10b981;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 9999;
            animation: slideIn 0.3s ease;
          `;
          notification.innerHTML = `
            <div style="display: flex; align-items: center; gap: 10px;">
              <i data-lucide="refresh-cw" style="width: 20px; height: 20px;"></i>
              <span>New reservations available. <a href="#" onclick="location.reload()" style="color: white; text-decoration: underline;">Refresh</a></span>
            </div>
          `;
          document.body.appendChild(notification);
          lucide.createIcons();
          
          lastReservationCount = data.count;
          
          // Auto-remove after 10 seconds
          setTimeout(() => {
            notification.remove();
          }, 10000);
        }
      } catch (error) {
        console.error('Error checking for updates:', error);
      }
    }
    
    // Check for updates every 15 seconds
    setInterval(checkForUpdates, 15000);
  </script>

  <style>
    @keyframes slideIn {
      from {
        transform: translateX(400px);
        opacity: 0;
      }
      to {
        transform: translateX(0);
        opacity: 1;
      }
    }
  </style>
</body>
</html>