<?php
session_start();

// Database configuration
$host = 'localhost';
$profile_db = 'profile';
$schedule_db = 'schedule';
$username = 'root';
$password = '';

try {
    // Connection for user profile
    $pdo_profile = new PDO("mysql:host=$host;dbname=$profile_db", $username, $password);
    $pdo_profile->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Connection for schedule data
    $pdo_schedule = new PDO("mysql:host=$host;dbname=$schedule_db", $username, $password);
    $pdo_schedule->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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
$sql = "SELECT * FROM students WHERE user_id = ?";
$stmt = $pdo_profile->prepare($sql);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: login-signup.php");
    exit();
}

// Handle AJAX request to refresh schedules
if (isset($_GET['ajax']) && $_GET['ajax'] === 'refresh' && isset($_GET['section_id'])) {
    header('Content-Type: application/json');
    
    $section_id = intval($_GET['section_id']);
    
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
    $stmt = $pdo_schedule->prepare($sql);
    $stmt->execute([$section_id]);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'schedules' => $schedules]);
    exit();
}

// Fetch all sections from the schedule database
$sql = "SELECT * FROM sections ORDER BY program, year_level, section_number";
$stmt = $pdo_schedule->query($sql);
$sections = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get section_id if provided (for viewing specific schedule)
$section_id = isset($_GET['section_id']) ? intval($_GET['section_id']) : 0;
$selected_section = null;
$schedules = [];

if ($section_id > 0) {
    // Fetch section details
    $sql = "SELECT * FROM sections WHERE id = ?";
    $stmt = $pdo_schedule->prepare($sql);
    $stmt->execute([$section_id]);
    $selected_section = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($selected_section) {
        // Fetch schedules for this section - These automatically show admin updates!
        $sql = "SELECT s.*, 
                subj.subject_code, subj.subject_name, subj.units,
                CONCAT(i.first_name, ' ', i.last_name) as instructor_name,
                r.room_number, r.room_name, r.building,
                s.updated_at
                FROM schedules s
                JOIN subjects subj ON s.subject_id = subj.id
                JOIN instructors i ON s.instructor_id = i.id
                JOIN rooms r ON s.room_id = r.id
                WHERE s.section_id = ? AND s.status = 'Active'
                ORDER BY 
                    FIELD(s.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
                    s.start_time";
        $stmt = $pdo_schedule->prepare($sql);
        $stmt->execute([$section_id]);
        $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Schedules</title>
  <link rel="stylesheet" href="schedule.css">
  <script src="https://unpkg.com/lucide@latest"></script>
  <style>
    .refresh-notice {
      background: #e3f2fd;
      border-left: 4px solid #2196f3;
      padding: 12px 16px;
      margin-bottom: 20px;
      border-radius: 4px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .refresh-notice i {
      color: #2196f3;
    }
    
    .refresh-button {
      background: #2196f3;
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 4px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 8px;
      margin-left: auto;
    }
    
    .refresh-button:hover {
      background: #1976d2;
    }
    
    .last-updated {
      font-size: 12px;
      color: #666;
      margin-top: 10px;
      text-align: right;
    }
    
    .schedule-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      flex-wrap: wrap;
      gap: 15px;
    }
    
    .schedule-actions {
      display: flex;
      gap: 10px;
      align-items: center;
    }
    
    .btn-download {
      background: #4caf50;
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 4px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .btn-download:hover {
      background: #45a049;
    }
    
    @keyframes highlight {
      0% { background-color: #fff3cd; }
      100% { background-color: transparent; }
    }
    
    .schedule-updated {
      animation: highlight 2s ease-in-out;
    }
  </style>
</head>

<body>
  <!-- SIDEBAR -->
  <div class="sidebar">
    <!-- Profile Section - Always Centered -->
    <div class="profile">
      <a href="sprofile.php" class="profile-link">
        <img src="<?php echo htmlspecialchars($user['profile_picture'] ?? 'https://freesvg.org/img/abstract-user-flat-3.png'); ?>" alt="Profile" />
      </a>
      <div class="profile-info">
        <h3><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
        <p><?php echo htmlspecialchars($user['email']); ?><br>Student</p>
      </div>
    </div>

    <!-- Main Navigation Links - Left Aligned -->
    <div class="nav-links nav-top">
      <a href="sdashboard.php">
        <i data-lucide="layout-dashboard"></i><span>Dashboard</span>
      </a>
      <a href="snotification.php">
        <i data-lucide="bell"></i><span>Notifications</span>
      </a>
      <a href="sschedule.php" class="active">
        <i data-lucide="calendar"></i><span>Schedule</span>
      </a>
    </div>

    <!-- Bottom Navigation - Left Aligned -->
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

  <!-- MAIN CONTENT -->
  <div class="main">
    <h2>Schedules</h2>

    <?php if ($selected_section): ?>
      <!-- SCHEDULE DETAIL VIEW -->
      <div class="schedule-view">
        <!-- Info Notice -->
        <div class="refresh-notice">
          <i data-lucide="info"></i>
          <span>This schedule is updated in real-time. Any changes made by administrators will appear here automatically.</span>
          <button class="refresh-button" onclick="refreshSchedule()">
            <i data-lucide="refresh-cw"></i>
            Refresh
          </button>
        </div>
        
        <div class="schedule-header">
          <div>
            <h3><?php echo htmlspecialchars($selected_section['name']); ?> - <?php echo htmlspecialchars($selected_section['program']); ?> Year <?php echo $selected_section['year_level']; ?></h3>
          </div>
          <div class="schedule-actions">
            <button class="btn-download" onclick="downloadSchedule()">
              <i data-lucide="download"></i>
              Download PDF
            </button>
            <a href="sschedule.php" class="back-to-sections">
              <i data-lucide="arrow-left"></i>
              Back to Sections
            </a>
          </div>
        </div>
        
        <?php if (empty($schedules)): ?>
          <div class="no-schedules">
            <p>No schedules available for this section yet.</p>
          </div>
        <?php else: ?>
          <table class="schedule-table" id="scheduleTable">
            <thead>
              <tr>
                <th>Day</th>
                <th>Time</th>
                <th>Subject</th>
                <th>Instructor</th>
                <th>Room</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($schedules as $schedule): ?>
                <tr data-schedule-id="<?php echo $schedule['id']; ?>">
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
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <div class="last-updated">
            Last refreshed: <span id="lastUpdated"><?php echo date('M d, Y g:i A'); ?></span>
          </div>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <!-- SCHEDULE CONTAINER (SECTION SELECTION) -->
      <div class="schedule-container">
        
        <!-- SEARCH AND FILTER -->
        <div class="search-filter">
          <input type="text" placeholder="Search section" id="searchInput">
          <select id="departmentFilter">
            <option value="all">All Programs</option>
            <option value="BSCS">BSCS</option>
            <option value="BSIT">BSIT</option>
            <option value="BSIS">BSIS</option>
          </select>
        </div>

        <!-- SCHEDULE BUTTONS -->
        <div class="schedule-buttons">
          <?php foreach ($sections as $section): ?>
            <button class="<?php echo strtolower($section['program']); ?>" 
                    data-program="<?php echo $section['program']; ?>"
                    data-name="<?php echo strtolower($section['name']); ?>"
                    onclick="window.location.href='sschedule.php?section_id=<?php echo $section['id']; ?>'">
              <?php echo htmlspecialchars($section['name']); ?>
            </button>
          <?php endforeach; ?>
        </div>

      </div>
    <?php endif; ?>
  </div>

  <script>
    // ============================================
    // INITIALIZE LUCIDE ICONS FIRST
    // ============================================
    lucide.createIcons();

    // ============================================
    // REFRESH SCHEDULE FUNCTIONALITY
    // ============================================
    async function refreshSchedule() {
      const sectionId = <?php echo $section_id; ?>;
      const refreshBtn = document.querySelector('.refresh-button');
      const icon = refreshBtn.querySelector('i');
      
      // Disable button and add spinning animation
      refreshBtn.disabled = true;
      icon.style.animation = 'spin 1s linear infinite';
      
      try {
        const response = await fetch(`sschedule.php?ajax=refresh&section_id=${sectionId}`);
        const result = await response.json();
        
        if (result.success) {
          // Update the table
          updateScheduleTable(result.schedules);
          
          // Update last updated time
          document.getElementById('lastUpdated').textContent = new Date().toLocaleString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
          });
          
          // Show success message
          showNotification('Schedule refreshed successfully!', 'success');
        }
      } catch (error) {
        console.error('Error refreshing schedule:', error);
        showNotification('Failed to refresh schedule', 'error');
      } finally {
        // Re-enable button
        refreshBtn.disabled = false;
        icon.style.animation = '';
        lucide.createIcons();
      }
    }
    
    function updateScheduleTable(schedules) {
      const tbody = document.querySelector('#scheduleTable tbody');
      
      if (schedules.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">No schedules available</td></tr>';
        return;
      }
      
      tbody.innerHTML = schedules.map(schedule => `
        <tr data-schedule-id="${schedule.id}" class="schedule-updated">
          <td><span class="day-badge">${escapeHtml(schedule.day_of_week)}</span></td>
          <td>${formatTime(schedule.start_time)} - ${formatTime(schedule.end_time)}</td>
          <td>
            <div class="subject-info">
              <strong>${escapeHtml(schedule.subject_code)}</strong>
              <span>${escapeHtml(schedule.subject_name)}</span>
              <small>${schedule.units} units</small>
            </div>
          </td>
          <td>${escapeHtml(schedule.instructor_name)}</td>
          <td>
            <div class="room-info">
              <strong>${escapeHtml(schedule.room_number)}</strong>
              <span>${escapeHtml(schedule.building)}</span>
            </div>
          </td>
        </tr>
      `).join('');
    }
    
    function formatTime(timeStr) {
      const [hours, minutes] = timeStr.split(':');
      const hour = parseInt(hours);
      const ampm = hour >= 12 ? 'PM' : 'AM';
      const hour12 = hour % 12 || 12;
      return `${hour12}:${minutes} ${ampm}`;
    }
    
    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }
    
    function showNotification(message, type) {
      const notification = document.createElement('div');
      notification.textContent = message;
      notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        background: ${type === 'success' ? '#4caf50' : '#f44336'};
        color: white;
        border-radius: 4px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        z-index: 10000;
      `;
      document.body.appendChild(notification);
      
      setTimeout(() => {
        notification.remove();
      }, 3000);
    }
    
    function downloadSchedule() {
      window.print();
    }
    
    // Add spinning animation for refresh icon
    const style = document.createElement('style');
    style.textContent = `
      @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
      }
    `;
    document.head.appendChild(style);

    // ============================================
    // UNIVERSAL DARK MODE FUNCTION
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

    // ============================================
    // SCHEDULE FILTER FUNCTIONALITY
    // ============================================
    const searchInput = document.getElementById('searchInput');
    const departmentSelect = document.getElementById('departmentFilter');
    const buttons = document.querySelectorAll('.schedule-buttons button');

    if (searchInput && departmentSelect) {
      function filterSchedules() {
        const searchText = searchInput.value.toLowerCase();
        const selectedDept = departmentSelect.value;

        buttons.forEach(btn => {
          const name = btn.dataset.name;
          const program = btn.dataset.program;

          let matchDept = selectedDept === 'all' || program === selectedDept;
          let matchSearch = name.includes(searchText);

          if (matchDept && matchSearch) {
            btn.style.display = 'block';
          } else {
            btn.style.display = 'none';
          }
        });
      }

      searchInput.addEventListener('input', filterSchedules);
      departmentSelect.addEventListener('change', filterSchedules);
    }
  </script>

</body>
</html>