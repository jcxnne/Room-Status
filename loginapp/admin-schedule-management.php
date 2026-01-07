<?php
session_start();

// Database configuration
$host = 'localhost';
$user_db = 'user_db';
$schedule_db = 'schedule';
$notification_db = 'notification';
$username = 'root';
$password = '';

try {
    $pdo_user = new PDO("mysql:host=$host;dbname=$user_db", $username, $password);
    $pdo_user->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $pdo = new PDO("mysql:host=$host;dbname=$schedule_db", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $pdo_notification = new PDO("mysql:host=$host;dbname=$notification_db", $username, $password);
    $pdo_notification->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin-login.php");
    exit();
}

$user_id = $_SESSION['admin_id'];

$sql = "SELECT * FROM admins WHERE id = ?";
$stmt = $pdo_user->prepare($sql);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: admin-login.php");
    exit();
}

// Handle different column naming conventions and set defaults
$user['first_name'] = $user['first_name'] ?? $user['firstname'] ?? 'Admin';
$user['last_name'] = $user['last_name'] ?? $user['lastname'] ?? 'User';
$user['email'] = $user['email'] ?? 'admin@example.com';
$user['profile_picture'] = $user['profile_picture'] ?? 'https://freesvg.org/img/abstract-user-flat-3.png';

$section_id = isset($_GET['section_id']) ? intval($_GET['section_id']) : 0;

if ($section_id === 0) {
    header("Location: admin-schedule.php");
    exit();
}

$sql = "SELECT * FROM sections WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$section_id]);
$section = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$section) {
    header("Location: admin-schedule.php");
    exit();
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'];
    
    try {
        switch ($action) {
            case 'add_schedule':
                $subject_id = $_POST['subject_id'];
                $instructor_id = $_POST['instructor_id'];
                $room_id = $_POST['room_id'];
                $day_of_week = $_POST['day_of_week'];
                $start_time = $_POST['start_time'];
                $end_time = $_POST['end_time'];
                $semester = $_POST['semester'];
                $academic_year = $_POST['academic_year'];
                
                // Check for schedule conflicts
                try {
                    $sql = "SELECT COUNT(*) as conflict_count FROM schedules 
                            WHERE section_id = ? AND day_of_week = ? 
                            AND semester = ? AND academic_year = ?
                            AND status = 'Active'
                            AND ((start_time < ? AND end_time > ?) 
                            OR (start_time < ? AND end_time > ?)
                            OR (start_time >= ? AND end_time <= ?))";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$section_id, $day_of_week, $semester, $academic_year, 
                                   $end_time, $start_time, $end_time, $start_time, $start_time, $end_time]);
                    $conflict = $stmt->fetch(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    $conflict = ['conflict_count' => 0];
                }
                
                if ($conflict['conflict_count'] > 0) {
                    echo json_encode(['success' => false, 'message' => 'Schedule conflict detected for this section']);
                    exit();
                }
                
                // Check instructor availability
                try {
                    $sql = "SELECT COUNT(*) as conflict_count FROM schedules 
                            WHERE instructor_id = ? AND day_of_week = ? 
                            AND semester = ? AND academic_year = ?
                            AND status = 'Active'
                            AND ((start_time < ? AND end_time > ?) 
                            OR (start_time < ? AND end_time > ?)
                            OR (start_time >= ? AND end_time <= ?))";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$instructor_id, $day_of_week, $semester, $academic_year, 
                                   $end_time, $start_time, $end_time, $start_time, $start_time, $end_time]);
                    $conflict = $stmt->fetch(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    $conflict = ['conflict_count' => 0];
                }
                
                if ($conflict['conflict_count'] > 0) {
                    echo json_encode(['success' => false, 'message' => 'Instructor is not available at this time']);
                    exit();
                }
                
                // Check room availability
                try {
                    $sql = "SELECT COUNT(*) as conflict_count FROM schedules 
                            WHERE room_id = ? AND day_of_week = ? 
                            AND semester = ? AND academic_year = ?
                            AND status = 'Active'
                            AND ((start_time < ? AND end_time > ?) 
                            OR (start_time < ? AND end_time > ?)
                            OR (start_time >= ? AND end_time <= ?))";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$room_id, $day_of_week, $semester, $academic_year, 
                                   $end_time, $start_time, $end_time, $start_time, $start_time, $end_time]);
                    $conflict = $stmt->fetch(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    $conflict = ['conflict_count' => 0];
                }
                
                if ($conflict['conflict_count'] > 0) {
                    echo json_encode(['success' => false, 'message' => 'Room is not available at this time']);
                    exit();
                }
                
                $sql = "INSERT INTO schedules (section_id, subject_id, instructor_id, room_id, day_of_week, start_time, end_time, semester, academic_year, created_by) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$section_id, $subject_id, $instructor_id, $room_id, $day_of_week, $start_time, $end_time, $semester, $academic_year, $user_id]);
                
                echo json_encode(['success' => true, 'message' => 'Schedule added successfully']);
                break;
                
            case 'update_schedule':
                $schedule_id = $_POST['schedule_id'];
                $subject_id = $_POST['subject_id'];
                $instructor_id = $_POST['instructor_id'];
                $room_id = $_POST['room_id'];
                $day_of_week = $_POST['day_of_week'];
                $start_time = $_POST['start_time'];
                $end_time = $_POST['end_time'];
                $semester = $_POST['semester'];
                $academic_year = $_POST['academic_year'];
                
                // Check for conflicts (excluding current schedule)
                $sql = "SELECT COUNT(*) as conflict_count FROM schedules 
                        WHERE section_id = ? AND day_of_week = ? 
                        AND semester = ? AND academic_year = ?
                        AND status = 'Active' AND id != ?
                        AND ((start_time < ? AND end_time > ?) 
                        OR (start_time < ? AND end_time > ?)
                        OR (start_time >= ? AND end_time <= ?))";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$section_id, $day_of_week, $semester, $academic_year, $schedule_id,
                               $end_time, $start_time, $end_time, $start_time, $start_time, $end_time]);
                $conflict = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($conflict['conflict_count'] > 0) {
                    echo json_encode(['success' => false, 'message' => 'Schedule conflict detected']);
                    exit();
                }
                
                $sql = "UPDATE schedules SET subject_id = ?, instructor_id = ?, room_id = ?, day_of_week = ?, 
                        start_time = ?, end_time = ?, semester = ?, academic_year = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$subject_id, $instructor_id, $room_id, $day_of_week, $start_time, $end_time, $semester, $academic_year, $schedule_id]);
                
                echo json_encode(['success' => true, 'message' => 'Schedule updated successfully']);
                break;
                
            case 'delete_schedule':
                $ids = json_decode($_POST['ids'], true);
                $placeholders = str_repeat('?,', count($ids) - 1) . '?';
                $sql = "DELETE FROM schedules WHERE id IN ($placeholders)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($ids);
                
                echo json_encode(['success' => true, 'message' => 'Schedule(s) deleted successfully']);
                break;
                
            case 'get_schedule':
                $schedule_id = $_POST['schedule_id'];
                $sql = "SELECT * FROM schedules WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$schedule_id]);
                $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'schedule' => $schedule]);
                break;
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// Fetch schedules
$sql = "SELECT s.*, 
        subj.subject_code, subj.subject_name, subj.units,
        CONCAT(i.first_name, ' ', i.last_name) as instructor_name,
        r.room_number, r.room_name, r.building
        FROM schedules s
        JOIN subjects subj ON s.subject_id = subj.id
        JOIN instructors i ON s.instructor_id = i.id
        JOIN rooms r ON s.room_id = r.id
        WHERE s.section_id = ? AND s.status = 'Active'
        ORDER BY 
            FIELD(s.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
            s.start_time";
$stmt = $pdo->prepare($sql);
$stmt->execute([$section_id]);
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sql = "SELECT * FROM subjects ORDER BY subject_code";
$subjects = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$sql = "SELECT id, CONCAT(first_name, ' ', last_name) as name FROM instructors ORDER BY first_name, last_name";
$instructors = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$sql = "SELECT * FROM rooms ORDER BY room_number";
$rooms = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="schedule.css"> 
  <script src="https://unpkg.com/lucide@latest"></script>
  <title>Schedule Management - <?php echo htmlspecialchars($section['name']); ?></title>
</head>
<body>
  <!-- SIDEBAR -->
  <div class="sidebar">
    <!-- Profile Section - Always Centered -->
    <div class="profile">
      <div class="profile-link">
        <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile" />
      </div>
      <div class="profile-info">
        <h3><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
        <p><?php echo htmlspecialchars($user['email']); ?><br>Admin</p>
      </div>
    </div>

    <!-- Main Navigation Links - Left Aligned -->
    <div class="nav-links nav-top">
      <a href="admin-dashboard.php">
        <i data-lucide="layout-dashboard"></i><span>Dashboard</span>
      </a>
      <a href="admin-notification.php">
        <i data-lucide="bell"></i><span>Notification</span>
      </a>
      <a href="admin-schedule.php" class="active">
        <i data-lucide="calendar"></i><span>Schedule</span>
      </a>
      <a href="admin-reservation.php">
        <i data-lucide="clipboard-check"></i><span>Reservation</span>
      </a>
      <a href="admin-audit.php">
        <i data-lucide="file-search"></i><span>Audit</span>
      </a>
      <a href="admin-report.php">
        <i data-lucide="bar-chart-3"></i><span>Reports</span>
      </a>
    </div>

    <!-- Bottom Navigation - Left Aligned -->
    <div class="nav-links nav-bottom">
      <a id="darkModeToggle" href="#">
        <i data-lucide="moon" class="icon-moon"></i>
        <i data-lucide="sun" class="icon-sun" style="display: none;"></i>
        <span>Dark Mode</span>
      </a>
      <a href="admin-logout.php">
        <i data-lucide="log-out"></i><span>Log Out</span>
      </a>
    </div>
  </div>

  <!-- MAIN CONTENT -->
  <div class="main">
    <h2>Schedule Management</h2>

    <!-- SCHEDULE DETAIL VIEW -->
    <div class="schedule-view">
      <!-- Admin Notice -->
      <div class="admin-notice">
        <i data-lucide="shield-check"></i>
        <span>Admin Mode: Changes sync automatically to student and faculty views.</span>
        <div class="admin-actions">
          <button class="btn-add" onclick="openAddModal()">
            <i data-lucide="plus"></i>
            Add Schedule
          </button>
          <button class="btn-delete-selected" onclick="deleteSelected()" style="display: none;" id="deleteSelectedBtn">
            <i data-lucide="trash-2"></i>
            Delete Selected
          </button>
        </div>
      </div>
      
      <div class="schedule-header">
        <div>
          <h3><?php echo htmlspecialchars($section['name']); ?> - <?php echo htmlspecialchars($section['program']); ?> Year <?php echo $section['year_level']; ?></h3>
        </div>
        <div class="schedule-actions">
          <a href="admin-schedule.php" class="back-to-sections">
            <i data-lucide="arrow-left"></i>
            Back to Sections
          </a>
        </div>
      </div>
      
      <?php if (empty($schedules)): ?>
        <div class="no-schedules">
          <p>No schedules available for this section yet. Click "Add Schedule" to create one.</p>
        </div>
      <?php else: ?>
        <table class="schedule-table">
          <thead>
            <tr>
              <th class="checkbox-cell">
                <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)">
              </th>
              <th>Day</th>
              <th>Time</th>
              <th>Subject</th>
              <th>Instructor</th>
              <th>Room</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($schedules as $schedule): ?>
              <tr>
                <td class="checkbox-cell">
                  <input type="checkbox" class="schedule-checkbox" value="<?php echo $schedule['id']; ?>" onchange="updateDeleteButton()">
                </td>
                <td><span class="day-badge"><?php echo htmlspecialchars($schedule['day_of_week']); ?></span></td>
                <td>
                  <?php 
                    echo date('g:i A', strtotime($schedule['start_time'])) . ' - ' . 
                         date('g:i A', strtotime($schedule['end_time'])); 
                  ?>
                </td>
                <td>
                  <div class="subject-info">
                    <strong><?php echo htmlspecialchars($schedule['subject_code']); ?></strong>
                    <span><?php echo htmlspecialchars($schedule['subject_name']); ?></span>
                    <small><?php echo $schedule['units']; ?> units</small>
                  </div>
                </td>
                <td><?php echo htmlspecialchars($schedule['instructor_name']); ?></td>
                <td>
                  <div class="room-info">
                    <strong><?php echo htmlspecialchars($schedule['room_number']); ?></strong>
                    <span><?php echo htmlspecialchars($schedule['building']); ?></span>
                  </div>
                </td>
                <td>
                  <div class="action-buttons">
                    <button class="btn-icon" onclick="editSchedule(<?php echo $schedule['id']; ?>)" title="Edit">
                      <i data-lucide="edit"></i>
                    </button>
                    <button class="btn-icon btn-danger" onclick="deleteSchedule([<?php echo $schedule['id']; ?>])" title="Delete">
                      <i data-lucide="trash-2"></i>
                    </button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>

  <!-- Add/Edit Schedule Modal -->
  <div id="scheduleModal" class="modal" style="display: none;">
    <div class="modal-content">
      <div class="modal-header">
        <h2 id="modalTitle">Add Schedule</h2>
        <button class="modal-close" onclick="closeModal()" type="button">&times;</button>
      </div>
      <form id="scheduleForm" onsubmit="saveSchedule(event)">
        <input type="hidden" id="scheduleId" name="schedule_id">
        
        <div class="form-group">
          <label for="subject_id">Subject *</label>
          <select id="subject_id" name="subject_id" required>
            <option value="">Select Subject</option>
            <?php foreach ($subjects as $subject): ?>
              <option value="<?php echo $subject['id']; ?>">
                <?php echo htmlspecialchars($subject['subject_code'] . ' - ' . $subject['subject_name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="instructor_id">Instructor *</label>
          <select id="instructor_id" name="instructor_id" required>
            <option value="">Select Instructor</option>
            <?php foreach ($instructors as $instructor): ?>
              <option value="<?php echo $instructor['id']; ?>">
                <?php echo htmlspecialchars($instructor['name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="room_id">Room *</label>
          <select id="room_id" name="room_id" required>
            <option value="">Select Room</option>
            <?php foreach ($rooms as $room): ?>
              <option value="<?php echo $room['id']; ?>">
                <?php echo htmlspecialchars($room['room_number'] . ' - ' . $room['building']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="day_of_week">Day of Week *</label>
          <select id="day_of_week" name="day_of_week" required>
            <option value="">Select Day</option>
            <option value="Monday">Monday</option>
            <option value="Tuesday">Tuesday</option>
            <option value="Wednesday">Wednesday</option>
            <option value="Thursday">Thursday</option>
            <option value="Friday">Friday</option>
            <option value="Saturday">Saturday</option>
            <option value="Sunday">Sunday</option>
          </select>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="start_time">Start Time *</label>
            <input type="time" id="start_time" name="start_time" required step="60">
          </div>

          <div class="form-group">
            <label for="end_time">End Time *</label>
            <input type="time" id="end_time" name="end_time" required step="60">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="semester">Semester *</label>
            <select id="semester" name="semester" required>
              <option value="">Select Semester</option>
              <option value="1st Semester">1st Semester</option>
              <option value="2nd Semester">2nd Semester</option>
              <option value="Summer">Summer</option>
            </select>
          </div>

          <div class="form-group">
            <label for="academic_year">Academic Year *</label>
            <input type="text" id="academic_year" name="academic_year" placeholder="e.g., 2023-2024" required pattern="\d{4}-\d{4}">
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
          <button type="submit" class="btn-primary">Save Schedule</button>
        </div>
      </form>
    </div>
  </div>



  <script>
    lucide.createIcons();

    // ============================================
    // MODAL FUNCTIONS
    // ============================================
    function openAddModal() {
      const modal = document.getElementById('scheduleModal');
      const modalContent = modal.querySelector('.modal-content');
      
      document.getElementById('modalTitle').textContent = 'Add Schedule';
      document.getElementById('scheduleForm').reset();
      document.getElementById('scheduleId').value = '';
      
      // Show modal with animation
      modal.style.display = 'flex';
      document.body.style.overflow = 'hidden';
      
      // Trigger animation
      setTimeout(() => {
        modalContent.style.opacity = '1';
        modalContent.style.transform = 'translateY(0) scale(1)';
      }, 10);
    }

    function closeModal() {
      const modal = document.getElementById('scheduleModal');
      const modalContent = modal.querySelector('.modal-content');
      
      // Animate out
      modalContent.style.opacity = '0';
      modalContent.style.transform = 'translateY(-50px) scale(0.95)';
      
      setTimeout(() => {
        modal.style.display = 'none';
        document.body.style.overflow = '';
      }, 300);
    }

    window.onclick = function(event) {
      const modal = document.getElementById('scheduleModal');
      if (event.target === modal) {
        closeModal();
      }
    }

    document.addEventListener('keydown', function(event) {
      if (event.key === 'Escape') {
        const modal = document.getElementById('scheduleModal');
        if (modal.style.display === 'flex') {
          closeModal();
        }
      }
    });

    // ============================================
    // CRUD OPERATIONS
    // ============================================
    async function saveSchedule(event) {
      event.preventDefault();
      
      const formData = new FormData(event.target);
      const scheduleId = document.getElementById('scheduleId').value;
      
      formData.append('action', scheduleId ? 'update_schedule' : 'add_schedule');
      
      try {
        const response = await fetch('', {
          method: 'POST',
          body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
          alert(result.message);
          closeModal();
          location.reload();
        } else {
          alert(result.message);
        }
      } catch (error) {
        alert('An error occurred: ' + error.message);
      }
    }

    async function editSchedule(scheduleId) {
      const formData = new FormData();
      formData.append('action', 'get_schedule');
      formData.append('schedule_id', scheduleId);
      
      try {
        const response = await fetch('', {
          method: 'POST',
          body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
          const schedule = result.schedule;
          document.getElementById('modalTitle').textContent = 'Edit Schedule';
          document.getElementById('scheduleId').value = schedule.id;
          document.getElementById('subject_id').value = schedule.subject_id;
          document.getElementById('instructor_id').value = schedule.instructor_id;
          document.getElementById('room_id').value = schedule.room_id;
          document.getElementById('day_of_week').value = schedule.day_of_week;
          document.getElementById('start_time').value = schedule.start_time;
          document.getElementById('end_time').value = schedule.end_time;
          document.getElementById('semester').value = schedule.semester;
          document.getElementById('academic_year').value = schedule.academic_year;
          
          const modal = document.getElementById('scheduleModal');
          const modalContent = modal.querySelector('.modal-content');
          
          modal.style.display = 'flex';
          document.body.style.overflow = 'hidden';
          
          setTimeout(() => {
            modalContent.style.opacity = '1';
            modalContent.style.transform = 'translateY(0) scale(1)';
          }, 10);
        } else {
          alert('Failed to load schedule data');
        }
      } catch (error) {
        alert('An error occurred: ' + error.message);
      }
    }

    async function deleteSchedule(ids) {
      if (!confirm('Are you sure you want to delete this schedule?')) {
        return;
      }
      
      const formData = new FormData();
      formData.append('action', 'delete_schedule');
      formData.append('ids', JSON.stringify(ids));
      
      try {
        const response = await fetch('', {
          method: 'POST',
          body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
          alert(result.message);
          location.reload();
        } else {
          alert(result.message);
        }
      } catch (error) {
        alert('An error occurred: ' + error.message);
      }
    }

    function deleteSelected() {
      const checkboxes = document.querySelectorAll('.schedule-checkbox:checked');
      
      if (checkboxes.length === 0) {
        alert('Please select at least one schedule to delete');
        return;
      }
      
      const ids = Array.from(checkboxes).map(cb => parseInt(cb.value));
      
      if (!confirm(`Are you sure you want to delete ${ids.length} schedule(s)?`)) {
        return;
      }
      
      const formData = new FormData();
      formData.append('action', 'delete_schedule');
      formData.append('ids', JSON.stringify(ids));
      
      fetch('', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(result => {
        if (result.success) {
          alert(result.message);
          location.reload();
        } else {
          alert(result.message);
        }
      })
      .catch(error => {
        alert('An error occurred: ' + error.message);
      });
    }

    function toggleSelectAll(checkbox) {
      const checkboxes = document.querySelectorAll('.schedule-checkbox');
      checkboxes.forEach(cb => {
        cb.checked = checkbox.checked;
      });
      updateDeleteButton();
    }
    
    function updateDeleteButton() {
      const checkboxes = document.querySelectorAll('.schedule-checkbox:checked');
      const deleteBtn = document.getElementById('deleteSelectedBtn');
      deleteBtn.style.display = checkboxes.length > 0 ? 'flex' : 'none';
    }

    // ============================================
    // DARK MODE
    // ============================================
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
  </script>
</body>
</html>