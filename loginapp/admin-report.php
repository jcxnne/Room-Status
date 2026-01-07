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

// Sample room usage data for chart
$roomUsageData = [
    ['room' => 'Rm 101', 'hours' => 17],
    ['room' => 'Rm 102', 'hours' => 38],
    ['room' => 'Rm 103', 'hours' => 29],
    ['room' => 'Rm 104', 'hours' => 15],
    ['room' => 'Rm 105', 'hours' => 38],
    ['room' => 'Rm 106', 'hours' => 17],
];

$roomUsagePercent = 93;
$scheduleConflicts = 5;
$flaggedTeachers = 2;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="admin-dashboard.css"> 
  <link rel="stylesheet" href="admin-report.css"> 
  <script src="https://unpkg.com/lucide@latest"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <title>Reports - Admin Dashboard</title>
</head>
<body>
  <div class="sidebar no-print">
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
      <a href="admin-audit.php">
        <i data-lucide="file-search"></i><span>Audit</span>
      </a>
      <a href="admin-report.php" class="active">
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
    <div class="main-header no-print">
      <h1>Reports</h1>
      <div class="logo"><?php echo strtoupper(substr($user['full_name'], 0, 2)); ?></div>
    </div>

    <div class="reports-layout">
      <!-- Left Column - Chart -->
      <div class="left-column">
        <div class="chart-card" id="chartSection">
          <h2>Weekly Room Usage (Hours per Room)</h2>
          <canvas id="roomUsageChart"></canvas>
        </div>

        <!-- Generate Report Button -->
        <div class="report-actions no-print">
          <button class="generate-btn" id="generateReportBtn">
            <i data-lucide="file-text"></i> Generate Report
          </button>
        </div>
      </div>

      <!-- Right Column - Summary Cards -->
      <div class="right-column">
        <div class="summary-card">
          <h3>Room Usage Summary</h3>
          <div class="usage-circle">
            <svg viewBox="0 0 100 100">
              <circle class="usage-bg" cx="50" cy="50" r="40"></circle>
              <circle class="usage-progress" cx="50" cy="50" r="40" id="usageCircle"></circle>
            </svg>
            <div class="usage-text">
              <span id="usagePercent"><?php echo $roomUsagePercent; ?></span>%
            </div>
          </div>
        </div>

        <div class="summary-card conflict-card">
          <h3>Schedule Conflict Detected</h3>
          <div class="stat-display">
            <i data-lucide="calendar-x" class="stat-icon conflict-icon"></i>
            <span class="stat-number"><?php echo $scheduleConflicts; ?></span>
          </div>
        </div>

        <div class="summary-card flagged-card">
          <h3>Flagged Teachers</h3>
          <div class="stat-display">
            <i data-lucide="alert-triangle" class="stat-icon flagged-icon"></i>
            <span class="stat-number"><?php echo $flaggedTeachers; ?></span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Report Options Modal -->
  <div class="modal" id="reportModal">
    <div class="modal-content report-modal">
      <div class="modal-header">
        <h2>Generate Report</h2>
        <button class="close-btn" id="closeReportModal">
          <i data-lucide="x"></i>
        </button>
      </div>
      <div class="modal-body">
        <p class="modal-description">Choose how you want to generate your report:</p>
        
        <div class="report-options">
          <button class="report-option-btn" id="downloadPdfBtn">
            <i data-lucide="download"></i>
            <div class="option-content">
              <h4>Download as PDF</h4>
              <p>Save the report to your device</p>
            </div>
          </button>

          <button class="report-option-btn" id="printBtn">
            <i data-lucide="printer"></i>
            <div class="option-content">
              <h4>Print Report</h4>
              <p>Send directly to printer</p>
            </div>
          </button>
        </div>

        <div class="report-settings">
          <h4>Report Settings</h4>
          <div class="setting-group">
            <label>
              <input type="checkbox" id="includeChart" checked />
              Include Chart
            </label>
          </div>
          <div class="setting-group">
            <label>
              <input type="checkbox" id="includeSummary" checked />
              Include Summary Statistics
            </label>
          </div>
          <div class="setting-group">
            <label>
              Date Range:
              <input type="date" id="reportDateFrom" class="date-input" />
              to
              <input type="date" id="reportDateTo" class="date-input" />
            </label>
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

    // Room Usage Circle
    function updateUsageCircle() {
      const percent = <?php echo $roomUsagePercent; ?>;
      const circle = document.getElementById('usageCircle');
      const circumference = 2 * Math.PI * 40;
      const offset = circumference - (percent / 100) * circumference;
      circle.style.strokeDasharray = circumference;
      circle.style.strokeDashoffset = offset;
    }
    updateUsageCircle();

    // Room Usage Chart
    const ctx = document.getElementById('roomUsageChart').getContext('2d');
    const isDarkMode = document.body.classList.contains('dark');
    
    const roomData = <?php echo json_encode($roomUsageData); ?>;
    const labels = roomData.map(item => item.room);
    const data = roomData.map(item => item.hours);
    
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
          barThickness: 50
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: {
            display: false
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
              text: 'Usages (hour)',
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

    // Modal Functions
    const reportModal = document.getElementById('reportModal');
    const generateReportBtn = document.getElementById('generateReportBtn');
    const closeReportModal = document.getElementById('closeReportModal');

    function openReportModal() {
      reportModal.style.display = 'flex';
      setTimeout(() => {
        reportModal.classList.add('show');
      }, 10);
      lucide.createIcons();
    }

    function closeModal() {
      reportModal.classList.remove('show');
      setTimeout(() => {
        reportModal.style.display = 'none';
      }, 300);
    }

    generateReportBtn.addEventListener('click', openReportModal);
    closeReportModal.addEventListener('click', closeModal);

    reportModal.addEventListener('click', (e) => {
      if (e.target === reportModal) {
        closeModal();
      }
    });

    // Print Function
    document.getElementById('printBtn').addEventListener('click', () => {
      closeModal();
      setTimeout(() => {
        window.print();
      }, 500);
    });

    // Download PDF Function
    document.getElementById('downloadPdfBtn').addEventListener('click', async () => {
      closeModal();
      
      const { jsPDF } = window.jspdf;
      const pdf = new jsPDF('p', 'mm', 'a4');
      
      // Add title
      pdf.setFontSize(20);
      pdf.text('Room Usage Report', 105, 20, { align: 'center' });
      
      // Add date
      pdf.setFontSize(10);
      const today = new Date().toLocaleDateString();
      pdf.text(`Generated: ${today}`, 105, 30, { align: 'center' });
      
      // Capture chart
      const chartCanvas = document.getElementById('roomUsageChart');
      const chartImage = chartCanvas.toDataURL('image/png');
      pdf.addImage(chartImage, 'PNG', 15, 40, 180, 100);
      
      // Add summary statistics
      pdf.setFontSize(14);
      pdf.text('Summary Statistics', 15, 155);
      
      pdf.setFontSize(11);
      pdf.text(`Room Usage: <?php echo $roomUsagePercent; ?>%`, 15, 165);
      pdf.text(`Schedule Conflicts: <?php echo $scheduleConflicts; ?>`, 15, 175);
      pdf.text(`Flagged Teachers: <?php echo $flaggedTeachers; ?>`, 15, 185);
      
      // Save PDF
      pdf.save('room-usage-report.pdf');
      
      alert('PDF downloaded successfully!');
    });
  </script>
</body>
</html>