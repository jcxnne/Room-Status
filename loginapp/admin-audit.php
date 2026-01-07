<?php
session_start();

// Database configuration
$host = 'localhost';
$dbname = 'user_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin-login.php");
    exit();
}

$user_id = $_SESSION['admin_id'];

// Fetch admin user data
$sql = "SELECT * FROM admins WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: admin-login.php");
    exit();
}

// Sample flagged cases data
$flaggedCases = [
    [
        'id' => 1,
        'faculty' => 'Mr. Daniel Cruz',
        'description' => 'Double-booked two classes in Room 204 at 10:00 AM.',
        'date' => '2025-10-24',
        'status' => 'Processing'
    ],
    [
        'id' => 2,
        'faculty' => 'Ms. Carla Santos',
        'description' => 'Reserved a lab room overlapping another class session.',
        'date' => '2025-10-17',
        'status' => 'Resolved'
    ],
    [
        'id' => 3,
        'faculty' => 'Mr. Anthony Javier',
        'description' => 'Missing attendance submissions for 2 subjects.',
        'date' => '2025-10-12',
        'status' => 'Resolved'
    ],
    [
        'id' => 4,
        'faculty' => 'Mr. John Macaraig',
        'description' => 'Schedule Conflict',
        'date' => '2025-10-12',
        'status' => 'Processing'
    ],
    [
        'id' => 5,
        'faculty' => 'Ms. Jessica Garcia',
        'description' => 'Schedule Conflict',
        'date' => '2025-10-12',
        'status' => 'Processing'
    ],
];

// Sample room usage data for chart
$roomUsageData = [
    ['room' => 'Rm 101', 'hours' => 15],
    ['room' => 'Rm 102', 'hours' => 35],
    ['room' => 'Rm 103', 'hours' => 28],
    ['room' => 'Rm 104', 'hours' => 12],
    ['room' => 'Rm 105', 'hours' => 38],
    ['room' => 'Rm 106', 'hours' => 16],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="admin-dashboard.css"> 
  <link rel="stylesheet" href="admin-audit.css"> 
  <script src="https://unpkg.com/lucide@latest"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <title>Audit Log - Admin Dashboard</title>
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
      <a href="admin-reservation.php">
        <i data-lucide="clipboard-check"></i><span>Reservation</span>
      </a>
      <a href="admin-audit.php" class="active">
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
      <h1>Audit Log</h1>
      <div class="logo"><?php echo strtoupper(substr($user['full_name'], 0, 2)); ?></div>
    </div>

    <div class="audit-layout">
      <!-- Left Column -->
      <div class="left-column">
        <!-- Weekly Room Usage Chart -->
        <div class="chart-card">
          <h2>Weekly Room Usage (Hours per Room)</h2>
          <canvas id="roomUsageChart"></canvas>
        </div>

        <!-- Date Range Filter -->
        <div class="date-filter-card">
          <div class="date-group">
            <label>From</label>
            <input type="date" id="dateFrom" value="2025-10-01" />
          </div>
          <div class="date-group">
            <label>To</label>
            <input type="date" id="dateTo" value="2025-10-06" />
          </div>
        </div>
      </div>

      <!-- Right Column -->
      <div class="right-column">
        <!-- Flagged Cases Table -->
        <div class="flagged-cases-card">
          <h2>Flagged Cases</h2>
          
          <div class="table-wrapper">
            <table class="flagged-table">
              <thead>
                <tr>
                  <th>Faculty</th>
                  <th>Description</th>
                  <th>Date</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($flaggedCases as $case): ?>
                  <tr class="case-row <?php echo strtolower($case['status']); ?>">
                    <td class="faculty-name"><?php echo htmlspecialchars($case['faculty']); ?></td>
                    <td class="description"><?php echo htmlspecialchars($case['description']); ?></td>
                    <td class="date"><?php echo date('M d, Y', strtotime($case['date'])); ?></td>
                    <td>
                      <span class="status-badge <?php echo strtolower($case['status']); ?>">
                        <?php echo htmlspecialchars($case['status']); ?>
                      </span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
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
        
        // Update chart colors when toggling dark mode
        if (window.roomUsageChart) {
          updateChartColors(isDark);
        }
      }

      initDarkMode();
      
      const toggle = document.getElementById('darkModeToggle');
      if (toggle) {
        toggle.addEventListener('click', toggleDarkMode);
      }
    })();

    // Room Usage Chart
    const ctx = document.getElementById('roomUsageChart').getContext('2d');
    const isDarkMode = document.body.classList.contains('dark');
    
    const roomData = <?php echo json_encode($roomUsageData); ?>;
    const labels = roomData.map(item => item.room);
    const data = roomData.map(item => item.hours);
    
    // Alternating colors
    const backgroundColors = data.map((_, index) => 
      index % 2 === 0 ? '#93c5fd' : '#fcd34d'
    );
    
    window.roomUsageChart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [{
          label: 'Usage Hours',
          data: data,
          backgroundColor: backgroundColors,
          borderRadius: 6,
          barThickness: 40
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                return context.parsed.y + ' hours';
              }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            max: 40,
            ticks: {
              stepSize: 10,
              color: isDarkMode ? '#fff' : '#666'
            },
            grid: {
              color: isDarkMode ? '#444' : '#e5e5e5'
            },
            title: {
              display: true,
              text: 'Usage Hours',
              color: isDarkMode ? '#fff' : '#666'
            }
          },
          x: {
            ticks: {
              color: isDarkMode ? '#fff' : '#666'
            },
            grid: {
              display: false
            },
            title: {
              display: true,
              text: 'Room',
              color: isDarkMode ? '#fff' : '#666'
            }
          }
        }
      }
    });

    function updateChartColors(isDark) {
      window.roomUsageChart.options.scales.y.ticks.color = isDark ? '#fff' : '#666';
      window.roomUsageChart.options.scales.y.grid.color = isDark ? '#444' : '#e5e5e5';
      window.roomUsageChart.options.scales.y.title.color = isDark ? '#fff' : '#666';
      window.roomUsageChart.options.scales.x.ticks.color = isDark ? '#fff' : '#666';
      window.roomUsageChart.options.scales.x.title.color = isDark ? '#fff' : '#666';
      window.roomUsageChart.update();
    }

    // Date filter functionality
    document.getElementById('dateFrom').addEventListener('change', function() {
      console.log('Date From changed:', this.value);
      // Add filter logic here
    });

    document.getElementById('dateTo').addEventListener('change', function() {
      console.log('Date To changed:', this.value);
      // Add filter logic here
    });
  </script>
</body>
</html>