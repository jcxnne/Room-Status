<?php
session_start();

// Database configuration for user data
$host = 'localhost';
$username = 'root';
$password = '';

try {
    // Connection for faculty table (profile database)
    $pdo_profile = new PDO("mysql:host=$host;dbname=profile", $username, $password);
    $pdo_profile->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Connection for reservations table (reservation database)
    $pdo = new PDO("mysql:host=$host;dbname=reservation", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login-signup.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user data
$sql = "SELECT * FROM faculty WHERE user_id = ?";
$stmt = $pdo_profile->prepare($sql);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: login-signup.php");
    exit();
}

$success_message = '';
$error_message = '';

// Add this after session check to display success message from redirect
if (isset($_GET['success'])) {
    $success_message = $_GET['success'];
}

// Handle check-in/check-out
if (isset($_POST['action']) && ($_POST['action'] === 'check_in' || $_POST['action'] === 'check_out')) {
    $reservation_id = $_POST['reservation_id'];
    $action = $_POST['action'];
    
    try {
        // Verify the reservation belongs to the user
        $verify_sql = "SELECT * FROM reservations WHERE id = ? AND user_id = ?";
        $verify_stmt = $pdo->prepare($verify_sql);
        $verify_stmt->execute([$reservation_id, $user_id]);
        $reservation = $verify_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($reservation) {
            if ($action === 'check_in') {
                $update_sql = "UPDATE reservations SET checked_in = 1, check_in_time = NOW() WHERE id = ?";
                $pdo->prepare($update_sql)->execute([$reservation_id]);
                $success_message = "Checked in successfully!";
            } else if ($action === 'check_out') {
                $update_sql = "UPDATE reservations SET checked_out = 1, check_out_time = NOW() WHERE id = ?";
                $pdo->prepare($update_sql)->execute([$reservation_id]);
                $success_message = "Checked out successfully!";
            }
            // Redirect to refresh the page and show updated status
            header("Location: reservation.php?success=" . urlencode($success_message));
            exit();
        }
    } catch(PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// AJAX handler for getting available rooms
if (isset($_GET['action']) && $_GET['action'] === 'get_available_rooms') {
    header('Content-Type: application/json');
    
    $building = $_GET['building'] ?? '';
    $date = $_GET['date'] ?? '';
    $start_time = $_GET['start_time'] ?? '';
    $end_time = $_GET['end_time'] ?? '';
    
    // Define all rooms per building
    $all_rooms = [
        'Old Building' => ['101', '102', '103', '104', '105', '201', '202', '203', '204', '205'],
        'New Building' => ['301', '302', '303', '304', '305', '401', '402', '403', '404', '405']
    ];
    
    $available_rooms = [];
    
    if ($building && isset($all_rooms[$building])) {
        $available_rooms = $all_rooms[$building];
        
        // If date and time are provided, filter out reserved rooms
        if ($date && $start_time && $end_time) {
            try {
                $check_sql = "SELECT DISTINCT room FROM reservations 
                             WHERE building = ? 
                             AND date = ? 
                             AND status != 'cancelled'
                             AND (
                                 (start_time <= ? AND end_time > ?) OR
                                 (start_time < ? AND end_time >= ?) OR
                                 (start_time >= ? AND end_time <= ?)
                             )";
                $check_stmt = $pdo->prepare($check_sql);
                $check_stmt->execute([
                    $building, $date, 
                    $start_time, $start_time,
                    $end_time, $end_time,
                    $start_time, $end_time
                ]);
                
                $reserved_rooms = $check_stmt->fetchAll(PDO::FETCH_COLUMN);
                $available_rooms = array_diff($available_rooms, $reserved_rooms);
                $available_rooms = array_values($available_rooms);
            } catch(PDOException $e) {
                // If error, return all rooms
            }
        }
    }
    
    echo json_encode(['rooms' => $available_rooms]);
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    $room = trim($_POST['room']);
    $date = $_POST['date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $subject_code = trim($_POST['subject_code']);
    $building = trim($_POST['building']);
    $purpose = trim($_POST['purpose']);
    $faculty_name = $user['first_name'] . ' ' . $user['last_name'];
    
    // Validate times
    if ($start_time >= $end_time) {
        $error_message = "End time must be after start time.";
    } else {
        try {
            // Check for conflicts
            $check_sql = "SELECT * FROM reservations 
                         WHERE room = ? 
                         AND date = ? 
                         AND status != 'cancelled'
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
                $error_message = "This room is already reserved for the selected time slot.";
            } else {
                // Insert reservation with faculty name
                $insert_sql = "INSERT INTO reservations (user_id, faculty_name, room, date, start_time, end_time, subject_code, building, purpose, status, checked_in, checked_out, created_at) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 0, 0, NOW())";
                $insert_stmt = $pdo->prepare($insert_sql);
                $insert_stmt->execute([$user_id, $faculty_name, $room, $date, $start_time, $end_time, $subject_code, $building, $purpose]);
                
                $success_message = "Reservation submitted successfully! Status: Pending approval.";
            }
        } catch(PDOException $e) {
            $error_message = "Error creating reservation: " . $e->getMessage();
        }
    }
}

// Fetch user's reservations
try {
    $reservations_sql = "SELECT * FROM reservations 
                        WHERE user_id = ? 
                        AND (date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) OR date >= CURDATE())
                        ORDER BY date DESC, start_time DESC";
    $reservations_stmt = $pdo->prepare($reservations_sql);
    $reservations_stmt->execute([$user_id]);
    $reservations = $reservations_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $reservations = [];
}

// Fetch history
try {
    $history_sql = "SELECT * FROM reservations 
                   WHERE user_id = ? 
                   AND date < DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                   ORDER BY date DESC, start_time DESC";
    $history_stmt = $pdo->prepare($history_sql);
    $history_stmt->execute([$user_id]);
    $history = $history_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $history = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="dashboard.css">
  <link rel="stylesheet" href="reservation.css">
  <script src="https://unpkg.com/lucide@latest"></script>
  <title>Reservation</title>
</head>
<body>
  <div class="sidebar">
    <div class="profile">
      <a href="fprofile.php" class="profile-link">
        <img src="<?php echo htmlspecialchars($user['profile_picture'] ?? 'https://freesvg.org/img/abstract-user-flat-3.png'); ?>" alt="Profile" />
      </a>
      <div class="profile-info">
        <h3><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
        <p><?php echo htmlspecialchars($user['email']); ?><br>Faculty</p>
      </div>
    </div>

    <div class="nav-links nav-top">
      <a href="fdashboard.php">
        <i data-lucide="layout-dashboard"></i><span>Dashboard</span>
      </a>
      <a href="fnotification.php">
        <i data-lucide="bell"></i><span>Notification</span>
      </a>
      <a href="fschedule.php">
        <i data-lucide="calendar"></i><span>Schedule</span>
      </a>
      <a href="reservation.php" class="active">
        <i data-lucide="clipboard-check"></i><span>Reservation</span>
      </a>
    </div>

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
    <h1 class="page-title">Reservation</h1>

    <?php if ($success_message): ?>
      <div class="alert alert-success">
        <i data-lucide="check-circle"></i>
        <?php echo htmlspecialchars($success_message); ?>
      </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
      <div class="alert alert-error">
        <i data-lucide="alert-circle"></i>
        <?php echo htmlspecialchars($error_message); ?>
      </div>
    <?php endif; ?>

    <div class="reservation-container">
      <form method="POST" class="reservation-form" id="reservationForm">
        <div class="form-grid">
          <div class="form-group">
            <label>Date</label>
            <div class="input-with-icon">
              <input type="date" name="date" id="dateInput" required />
              <i data-lucide="calendar-range"></i>
            </div>
          </div>

          <div class="form-group">
            <label>Start Time</label>
            <div class="input-with-icon">
              <input type="time" name="start_time" id="startTimeInput" required />
              <i data-lucide="clock"></i>
            </div>
          </div>

          <div class="form-group">
            <label>End Time</label>
            <div class="input-with-icon">
              <input type="time" name="end_time" id="endTimeInput" required />
              <i data-lucide="clock"></i>
            </div>
          </div>

          <div class="form-group">
            <label>Building</label>
            <div class="input-with-icon">
              <select name="building" id="buildingSelect" required>
                <option value="">Select Building</option>
                <option value="Old Building">Old Building</option>
                <option value="New Building">New Building</option>
              </select>
              <i data-lucide="school"></i>
            </div>
          </div>

          <div class="form-group">
            <label>Room</label>
            <div class="input-with-icon">
              <select name="room" id="roomSelect" required disabled>
                <option value="">Select building first</option>
              </select>
              <i data-lucide="door-closed"></i>
            </div>
          </div>

          <div class="form-group">
            <label>Subject Code</label>
            <div class="input-with-icon">
              <input type="text" name="subject_code" placeholder="COSC75" required />
              <i data-lucide="book-open"></i>
            </div>
          </div>
        </div>

        <div class="form-group full-width">
          <label>Purpose/Reason</label>
          <textarea name="purpose" placeholder="Enter purpose or reason for reservation" rows="4" required></textarea>
        </div>

        <button type="submit" class="reserve-btn">Reserve</button>
      </form>
    </div>

    <div class="my-reservations">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="margin: 0;">My Reservations</h2>
        <button id="viewHistoryBtn" class="history-btn" onclick="toggleHistory()">
          <i data-lucide="history"></i>
          View History (<?php echo count($history); ?>)
        </button>
      </div>
      
      <div id="currentReservations" class="reservations-list">
        <?php if (empty($reservations)): ?>
          <p class="no-reservations">No current reservations found.</p>
        <?php else: ?>
          <?php foreach ($reservations as $reservation): ?>
            <div class="reservation-item <?php echo $reservation['status']; ?>" 
                 data-id="<?php echo $reservation['id']; ?>"
                 data-room="<?php echo htmlspecialchars($reservation['room']); ?>"
                 data-building="<?php echo htmlspecialchars($reservation['building']); ?>"
                 data-faculty="<?php echo htmlspecialchars($reservation['faculty_name']); ?>"
                 data-subject="<?php echo htmlspecialchars($reservation['subject_code']); ?>"
                 data-date="<?php echo $reservation['date']; ?>"
                 data-start="<?php echo $reservation['start_time']; ?>"
                 data-end="<?php echo $reservation['end_time']; ?>"
                 data-purpose="<?php echo htmlspecialchars($reservation['purpose']); ?>"
                 data-status="<?php echo $reservation['status']; ?>"
                 data-checkedin="<?php echo $reservation['checked_in'] ?? 0; ?>"
                 data-checkedout="<?php echo $reservation['checked_out'] ?? 0; ?>">
              <div class="reservation-header">
                <div>
                  <h3>Room <?php echo htmlspecialchars($reservation['room']); ?> - <?php echo htmlspecialchars($reservation['building']); ?></h3>
                  <p class="faculty-name-badge">Reserved by: <?php echo htmlspecialchars($reservation['faculty_name']); ?></p>
                </div>
                <span class="status-badge <?php echo $reservation['status']; ?>">
                  <?php echo ucfirst($reservation['status']); ?>
                </span>
              </div>
              <div class="reservation-details">
                <p><strong>Subject:</strong> <?php echo htmlspecialchars($reservation['subject_code']); ?></p>
                <p><strong>Date:</strong> <?php echo date('F d, Y', strtotime($reservation['date'])); ?></p>
                <p><strong>Time:</strong> <?php echo date('g:i A', strtotime($reservation['start_time'])); ?> - <?php echo date('g:i A', strtotime($reservation['end_time'])); ?></p>
                <p><strong>Purpose:</strong> <?php echo htmlspecialchars($reservation['purpose']); ?></p>
                
                <?php if (($reservation['checked_in'] ?? 0) || ($reservation['checked_out'] ?? 0)): ?>
                <div class="check-status">
                  <?php if ($reservation['checked_in'] ?? 0): ?>
                    <span class="status-indicator checked-in">
                      <i data-lucide="log-in"></i>
                      Checked In
                    </span>
                  <?php endif; ?>
                  <?php if ($reservation['checked_out'] ?? 0): ?>
                    <span class="status-indicator checked-out">
                      <i data-lucide="log-out"></i>
                      Checked Out
                    </span>
                  <?php endif; ?>
                </div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div id="historyReservations" class="reservations-list" style="display: none; margin-top: 20px;">
        <?php if (empty($history)): ?>
          <p class="no-reservations">No reservation history found.</p>
        <?php else: ?>
          <?php foreach ($history as $reservation): ?>
            <div class="reservation-item <?php echo $reservation['status']; ?> history-item" 
                 data-id="<?php echo $reservation['id']; ?>"
                 data-room="<?php echo htmlspecialchars($reservation['room']); ?>"
                 data-building="<?php echo htmlspecialchars($reservation['building']); ?>"
                 data-faculty="<?php echo htmlspecialchars($reservation['faculty_name']); ?>"
                 data-subject="<?php echo htmlspecialchars($reservation['subject_code']); ?>"
                 data-date="<?php echo $reservation['date']; ?>"
                 data-start="<?php echo $reservation['start_time']; ?>"
                 data-end="<?php echo $reservation['end_time']; ?>"
                 data-purpose="<?php echo htmlspecialchars($reservation['purpose']); ?>"
                 data-status="<?php echo $reservation['status']; ?>"
                 data-checkedin="<?php echo $reservation['checked_in'] ?? 0; ?>"
                 data-checkedout="<?php echo $reservation['checked_out'] ?? 0; ?>">
              <div class="reservation-header">
                <div>
                  <h3>Room <?php echo htmlspecialchars($reservation['room']); ?> - <?php echo htmlspecialchars($reservation['building']); ?></h3>
                  <p class="faculty-name-badge">Reserved by: <?php echo htmlspecialchars($reservation['faculty_name']); ?></p>
                </div>
                <span class="status-badge <?php echo $reservation['status']; ?>">
                  <?php echo ucfirst($reservation['status']); ?>
                </span>
              </div>
              <div class="reservation-details">
                <p><strong>Subject:</strong> <?php echo htmlspecialchars($reservation['subject_code']); ?></p>
                <p><strong>Date:</strong> <?php echo date('F d, Y', strtotime($reservation['date'])); ?></p>
                <p><strong>Time:</strong> <?php echo date('g:i A', strtotime($reservation['start_time'])); ?> - <?php echo date('g:i A', strtotime($reservation['end_time'])); ?></p>
                <p><strong>Purpose:</strong> <?php echo htmlspecialchars($reservation['purpose']); ?></p>
                
                <?php if (($reservation['checked_in'] ?? 0) || ($reservation['checked_out'] ?? 0)): ?>
                <div class="check-status">
                  <?php if ($reservation['checked_in'] ?? 0): ?>
                    <span class="status-indicator checked-in">
                      <i data-lucide="log-in"></i>
                      Checked In
                    </span>
                  <?php endif; ?>
                  <?php if ($reservation['checked_out'] ?? 0): ?>
                    <span class="status-indicator checked-out">
                      <i data-lucide="log-out"></i>
                      Checked Out
                    </span>
                  <?php endif; ?>
                </div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div id="reservationModal" class="modal">
    <div class="modal-content">
      <button class="modal-close" onclick="closeReservationModal()">&times;</button>
      <div class="modal-header">
        <h3 id="modalRoomTitle"></h3>
        <span id="modalStatusBadge" class="status-badge"></span>
      </div>
      <div class="modal-details" id="modalDetails"></div>
      <div id="modalCheckButtons"></div>
    </div>
  </div>

  <script>
    lucide.createIcons();

    const buildingSelect = document.getElementById('buildingSelect');
    const dateInput = document.getElementById('dateInput');
    const startTimeInput = document.getElementById('startTimeInput');
    const endTimeInput = document.getElementById('endTimeInput');
    const roomSelect = document.getElementById('roomSelect');

    async function updateAvailableRooms() {
      const building = buildingSelect.value;
      const date = dateInput.value;
      const startTime = startTimeInput.value;
      const endTime = endTimeInput.value;

      if (!building) {
        roomSelect.disabled = true;
        roomSelect.innerHTML = '<option value="">Select building first</option>';
        return;
      }

      roomSelect.disabled = true;
      roomSelect.innerHTML = '<option value="">Loading...</option>';

      try {
        const params = new URLSearchParams({
          action: 'get_available_rooms',
          building: building,
          date: date || '',
          start_time: startTime || '',
          end_time: endTime || ''
        });

        const response = await fetch(`reservation.php?${params}`);
        const data = await response.json();

        roomSelect.innerHTML = '<option value="">Select Room</option>';
        
        if (data.rooms && data.rooms.length > 0) {
          data.rooms.forEach(room => {
            const option = document.createElement('option');
            option.value = room;
            option.textContent = 'Room ' + room;
            roomSelect.appendChild(option);
          });
          roomSelect.disabled = false;
        } else {
          roomSelect.innerHTML = '<option value="">No rooms available</option>';
        }
      } catch (error) {
        console.error('Error fetching rooms:', error);
        roomSelect.innerHTML = '<option value="">Error loading rooms</option>';
      }
    }

    buildingSelect.addEventListener('change', updateAvailableRooms);
    dateInput.addEventListener('change', updateAvailableRooms);
    startTimeInput.addEventListener('change', updateAvailableRooms);
    endTimeInput.addEventListener('change', updateAvailableRooms);

    function openReservationModal(reservation) {
      const modal = document.getElementById('reservationModal');
      const modalRoomTitle = document.getElementById('modalRoomTitle');
      const modalStatusBadge = document.getElementById('modalStatusBadge');
      const modalDetails = document.getElementById('modalDetails');
      const modalCheckButtons = document.getElementById('modalCheckButtons');

      modalRoomTitle.textContent = `Room ${reservation.room} - ${reservation.building}`;
      modalStatusBadge.textContent = reservation.status.charAt(0).toUpperCase() + reservation.status.slice(1);
      modalStatusBadge.className = `status-badge ${reservation.status}`;

      const date = new Date(reservation.date + 'T00:00:00');
      const formattedDate = date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
      });
      
      const startTime = new Date(`2000-01-01T${reservation.start_time}`);
      const endTime = new Date(`2000-01-01T${reservation.end_time}`);
      const formattedStartTime = startTime.toLocaleTimeString('en-US', { 
        hour: 'numeric', 
        minute: '2-digit', 
        hour12: true 
      });
      const formattedEndTime = endTime.toLocaleTimeString('en-US', { 
        hour: 'numeric', 
        minute: '2-digit', 
        hour12: true 
      });

      modalDetails.innerHTML = `
        <p><strong>Reserved by:</strong> ${reservation.faculty_name}</p>
        <p><strong>Subject:</strong> ${reservation.subject_code}</p>
        <p><strong>Date:</strong> ${formattedDate}</p>
        <p><strong>Time:</strong> ${formattedStartTime} - ${formattedEndTime}</p>
        <p><strong>Purpose:</strong> ${reservation.purpose}</p>
      `;

      // Only show check-in/check-out buttons if status is approved
      if (reservation.status === 'approved') {
        const checkedIn = parseInt(reservation.checked_in) || 0;
        const checkedOut = parseInt(reservation.checked_out) || 0;

        const now = new Date();
        const reservationDate = new Date(reservation.date + 'T00:00:00');
        const [startHours, startMinutes] = reservation.start_time.split(':');
        const startDateTime = new Date(reservationDate);
        startDateTime.setHours(parseInt(startHours), parseInt(startMinutes), 0);
        
        const [endHours, endMinutes] = reservation.end_time.split(':');
        const endDateTime = new Date(reservationDate);
        endDateTime.setHours(parseInt(endHours), parseInt(endMinutes), 0);
        
        // Allow check-in 30 minutes before start time
        const checkInAllowedTime = new Date(startDateTime.getTime() - 30 * 60000);
        const canCheckIn = now >= checkInAllowedTime && now <= endDateTime;
        const isPast = now > endDateTime;

        let buttonsHtml = '<div class="check-buttons">';
        
        if (!checkedIn && !checkedOut) {
          // Not checked in yet
          if (isPast) {
            buttonsHtml += `
              <button class="check-btn" disabled style="opacity: 0.5;">
                <i data-lucide="clock-x"></i>
                Check-in Time Passed
              </button>
            `;
          } else if (!canCheckIn) {
            const timeUntilCheckIn = Math.ceil((checkInAllowedTime - now) / 60000);
            const hoursUntil = Math.floor(timeUntilCheckIn / 60);
            const minutesUntil = timeUntilCheckIn % 60;
            const timeText = hoursUntil > 0 ? `${hoursUntil}h ${minutesUntil}m` : `${minutesUntil}m`;
            
            buttonsHtml += `
              <button class="check-btn" disabled style="opacity: 0.5;">
                <i data-lucide="clock"></i>
                Available in ${timeText}
              </button>
            `;
          } else {
            buttonsHtml += `
              <form method="POST" style="flex: 1;">
                <input type="hidden" name="action" value="check_in">
                <input type="hidden" name="reservation_id" value="${reservation.id}">
                <button type="submit" class="check-btn check-in">
                  <i data-lucide="log-in"></i>
                  Check In
                </button>
              </form>
            `;
          }
        } else if (checkedIn && !checkedOut) {
          // Checked in but not checked out
          if (isPast) {
            buttonsHtml += `
              <button class="check-btn completed" disabled>
                <i data-lucide="check"></i>
                Checked In
              </button>
              <button class="check-btn" disabled style="opacity: 0.5;">
                <i data-lucide="clock-x"></i>
                Check-out Time Passed
              </button>
            `;
          } else {
            buttonsHtml += `
              <button class="check-btn completed" disabled>
                <i data-lucide="check"></i>
                Checked In
              </button>
              <form method="POST" style="flex: 1;">
                <input type="hidden" name="action" value="check_out">
                <input type="hidden" name="reservation_id" value="${reservation.id}">
                <button type="submit" class="check-btn check-out">
                  <i data-lucide="log-out"></i>
                  Check Out
                </button>
              </form>
            `;
          }
        } else if (checkedIn && checkedOut) {
          // Both checked in and checked out
          buttonsHtml += `
            <button class="check-btn completed" disabled>
              <i data-lucide="check-circle"></i>
              Completed
            </button>
          `;
        }
        
        buttonsHtml += '</div>';
        modalCheckButtons.innerHTML = buttonsHtml;
      } else if (reservation.status === 'pending') {
        modalCheckButtons.innerHTML = `
          <div class="check-buttons">
            <button class="check-btn" disabled style="opacity: 0.5; background-color: #fef3c7; color: #92400e;">
              <i data-lucide="clock"></i>
              Awaiting Approval
            </button>
          </div>
        `;
      } else if (reservation.status === 'cancelled') {
        modalCheckButtons.innerHTML = `
          <div class="check-buttons">
            <button class="check-btn" disabled style="opacity: 0.5; background-color: #fee2e2; color: #991b1b;">
              <i data-lucide="x-circle"></i>
              Reservation Cancelled
            </button>
          </div>
        `;
      } else {
        modalCheckButtons.innerHTML = '';
      }

      modal.classList.add('active');
      lucide.createIcons();
    }

    function closeReservationModal() {
      const modal = document.getElementById('reservationModal');
      modal.classList.remove('active');
    }

    window.addEventListener('click', function(event) {
      const modal = document.getElementById('reservationModal');
      if (event.target === modal) {
        closeReservationModal();
      }
    });

    // Make sure all reservation cards are clickable
    function attachReservationClickEvents() {
      document.querySelectorAll('.reservation-item').forEach(item => {
        item.style.cursor = 'pointer';
        
        // Remove any existing click listeners by cloning
        const newItem = item.cloneNode(true);
        item.parentNode.replaceChild(newItem, item);
      });
      
      // Re-attach click listeners
      document.querySelectorAll('.reservation-item').forEach(item => {
        item.addEventListener('click', function(e) {
          // Don't open modal if clicking on a form or button inside the card
          if (e.target.closest('form') || e.target.closest('button')) {
            return;
          }
          
          const reservation = {
            id: this.dataset.id,
            room: this.dataset.room,
            building: this.dataset.building,
            faculty_name: this.dataset.faculty,
            subject_code: this.dataset.subject,
            date: this.dataset.date,
            start_time: this.dataset.start,
            end_time: this.dataset.end,
            purpose: this.dataset.purpose,
            status: this.dataset.status,
            checked_in: this.dataset.checkedin || '0',
            checked_out: this.dataset.checkedout || '0'
          };
          
          openReservationModal(reservation);
        });
      });
    }

    // Call this after page loads
    document.addEventListener('DOMContentLoaded', function() {
      attachReservationClickEvents();
    });

    function toggleHistory() {
      const historySection = document.getElementById('historyReservations');
      const currentSection = document.getElementById('currentReservations');
      const btn = document.getElementById('viewHistoryBtn');
      const btnIcon = btn.querySelector('i');
      
      if (historySection.style.display === 'none') {
        historySection.style.display = 'grid';
        currentSection.style.display = 'none';
        btnIcon.setAttribute('data-lucide', 'arrow-left');
        btn.childNodes[2].textContent = ' Back to Current';
      } else {
        historySection.style.display = 'none';
        currentSection.style.display = 'grid';
        btnIcon.setAttribute('data-lucide', 'history');
        btn.childNodes[2].textContent = ' View History (<?php echo count($history); ?>)';
      }
      
      lucide.createIcons();
      // Re-attach click events after toggling
      attachReservationClickEvents();
    }

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

        lucide.createIcons();
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

    dateInput.min = new Date().toISOString().split('T')[0];
  </script>
</body>
</html>