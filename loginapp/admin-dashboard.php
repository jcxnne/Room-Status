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
    
    // Also connect to reservation database for stats
    $pdo_reservation = new PDO("mysql:host=$host;dbname=reservation", $username, $password);
    $pdo_reservation->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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

// Get total reservations count
$reservation_sql = "SELECT COUNT(*) as total FROM reservations";
$reservation_stmt = $pdo_reservation->query($reservation_sql);
$reservation_count = $reservation_stmt->fetch(PDO::FETCH_ASSOC)['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="admin-dashboard.css"> 
  <script src="https://unpkg.com/lucide@latest"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <title>Admin Dashboard</title>
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
      <a href="admin-dashboard.php" class="active">
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
      <h1>Dashboard</h1>
      <div class="search-container">
        <div class="search-wrapper">
          <i data-lucide="search" class="search-icon"></i>
          <input type="text" placeholder="Search room number" class="search-bar" id="searchBar" />
        </div>
        <img src="logo.png" alt="Logo" class="logo" onerror="this.style.display='none'">
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="cards">
      <div class="card">
        <h3><i data-lucide="calendar-check" class="card-icon"></i> Reservations</h3>
        <p id="total-reservations"><?php echo $reservation_count; ?></p>
      </div>
      <div class="card">
        <h3><i data-lucide="check-circle" class="card-icon"></i> Available Rooms</h3>
        <p id="available-rooms">0</p>
      </div>
      <div class="card">
        <h3><i data-lucide="x-circle" class="card-icon"></i> Occupied Rooms</h3>
        <p id="occupied-rooms">0</p>
      </div>
      <div class="card room-usage-card">
        <h3><i data-lucide="activity" class="card-icon"></i> Room Usage</h3>
        <div class="usage-circle">
          <svg viewBox="0 0 100 100">
            <circle class="usage-bg" cx="50" cy="50" r="40"></circle>
            <circle class="usage-progress" cx="50" cy="50" r="40" id="usageCircle"></circle>
          </svg>
          <div class="usage-text">
            <span id="usagePercent">0</span>%
          </div>
        </div>
      </div>
    </div>

    <!-- Chart and Room Status Row -->
    <div class="dashboard-row">
      <!-- Daily Room Usage Chart -->
      <div class="chart-container">
        <h2>Daily Room Usage</h2>
        <canvas id="roomUsageChart"></canvas>
      </div>

      <!-- Room Status -->
      <div class="room-status">
        <div class="room-status-header">
          <h2>Room Status</h2>
          <select class="building-filter" id="buildingFilter">
            <option value="all">All Buildings</option>
            <option value="New Building">New Building</option>
            <option value="Old Building">Old Building</option>
          </select>
        </div>

        <div class="room-status-content">
          <div class="rooms" id="roomsContainer">
            <!-- Rooms will be loaded dynamically -->
            <div style="text-align: center; padding: 20px; color: #999;">
              <i data-lucide="loader" style="animation: spin 1s linear infinite;"></i>
              Loading rooms...
            </div>
          </div>

          <!-- Vertical Filters -->
          <div class="vertical-filters">
            <div class="filter-tab available-tab" data-status="available">Available</div>
            <div class="filter-tab reserved-tab" data-status="reserved">Reserved</div>
            <div class="filter-tab pending-tab" data-status="pending">Pending</div>
          </div>
        </div>

        <div class="legend">
          <div class="legend-item"><div class="dot available"></div>Available</div>
          <div class="legend-item"><div class="dot reserved"></div>Reserved</div>
          <div class="legend-item"><div class="dot pending"></div>Pending</div>
        </div>
      </div>
    </div>

    <!-- Room Details Modal -->
    <div class="modal" id="roomDetailsModal">
      <div class="modal-content">
        <div class="modal-header">
          <h2 id="roomModalTitle">Room Details</h2>
          <button class="close-btn" id="closeRoomModal">
            <i data-lucide="x"></i>
          </button>
        </div>
        <div class="modal-body">
          <div class="detail-group">
            <label>Room Number:</label>
            <p id="roomNumber">-</p>
          </div>
          <div class="detail-group">
            <label>Status:</label>
            <p><span class="status-badge available" id="roomStatus">-</span></p>
          </div>
          <div class="detail-group">
            <label>Building:</label>
            <p id="roomBuilding">-</p>
          </div>
          <div class="detail-group">
            <label>Floor:</label>
            <p id="roomFloor">-</p>
          </div>
          <div class="detail-group">
            <label>Capacity:</label>
            <p id="roomCapacity">40 students</p>
          </div>
          <div class="detail-group">
            <label>Facilities:</label>
            <p id="roomFacilities">Projector, Whiteboard, Air Conditioning</p>
          </div>
          <div class="detail-group" id="currentReservationGroup" style="display: none;">
            <label>Current Reservation:</label>
            <p id="currentReservation">-</p>
          </div>
          <div class="detail-group" id="nextAvailableGroup" style="display: none;">
            <label>Next Available:</label>
            <p id="nextAvailable">-</p>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-cancel" id="closeRoomBtn">Close</button>
          <button class="btn-save" id="reserveRoomBtn">Reserve Room</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    lucide.createIcons();

    // Global variables
    let allRooms = [];
    let activeStatusFilter = null;
    let activeBuildingFilter = 'all';
    let searchQuery = '';

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
          moonIcon.style.display = isDark ? 'none' : 'inline';
          sunIcon.style.display = isDark ? 'inline' : 'none';
        }

        if (modeText) {
          modeText.textContent = isDark ? 'Light Mode' : 'Dark Mode';
        }
      }

      function toggleDarkMode(e) {
        e.preventDefault();
        const isDark = document.body.classList.toggle('dark');
        localStorage.setItem('darkMode', isDark);
        updateDarkModeUI(isDark);
        
        if (window.roomChart) {
          updateChartColors(isDark);
        }
      }

      initDarkMode();
      
      const toggle = document.getElementById('darkModeToggle');
      if (toggle) {
        toggle.addEventListener('click', toggleDarkMode);
      }
    })();

    // ============================================
    // LOAD ROOM STATUS
    // ============================================
    async function loadRoomStatus() {
      try {
        const response = await fetch('get_room_status.php');
        const data = await response.json();
        
        if (data.success) {
          allRooms = data.rooms;
          
          // Update stats
          document.getElementById('available-rooms').textContent = data.counts.available;
          document.getElementById('occupied-rooms').textContent = data.counts.reserved + data.counts.pending;
          document.getElementById('total-reservations').textContent = data.counts.total;
          
          // Update usage percentage
          const usagePercent = data.counts.total > 0 
            ? Math.round(((data.counts.reserved + data.counts.pending) / data.counts.total) * 100)
            : 0;
          document.getElementById('usagePercent').textContent = usagePercent;
          updateUsageCircle(usagePercent);
          
          // Render rooms
          renderRooms();
          
          lucide.createIcons();
        }
      } catch (error) {
        console.error('Error loading room status:', error);
        document.getElementById('roomsContainer').innerHTML = '<div style="text-align: center; padding: 20px; color: #f44336;">Error loading rooms. Please refresh the page.</div>';
      }
    }

    function renderRooms() {
      const container = document.getElementById('roomsContainer');
      container.innerHTML = '';
      
      const filteredRooms = allRooms.filter(room => {
        const statusMatch = !activeStatusFilter || room.status === activeStatusFilter;
        const buildingMatch = activeBuildingFilter === 'all' || room.building === activeBuildingFilter;
        const searchMatch = room.name.toLowerCase().includes(searchQuery.toLowerCase());
        return statusMatch && buildingMatch && searchMatch;
      });
      
      if (filteredRooms.length === 0) {
        container.innerHTML = '<div style="text-align: center; padding: 20px; color: #999;">No rooms found</div>';
        return;
      }
      
      filteredRooms.forEach(room => {
        const roomDiv = document.createElement('div');
        roomDiv.className = `room ${room.status}`;
        roomDiv.textContent = room.name;
        roomDiv.setAttribute('data-room-number', room.number);
        roomDiv.setAttribute('data-building', room.building);
        
        if (room.reservation) {
          roomDiv.setAttribute('data-faculty', room.reservation.faculty);
          roomDiv.setAttribute('data-section', room.reservation.section);
          roomDiv.setAttribute('data-time', room.reservation.time);
          roomDiv.title = `${room.reservation.faculty} - ${room.reservation.section}\n${room.reservation.time}`;
        }
        
        roomDiv.addEventListener('click', () => openRoomModal(room));
        container.appendChild(roomDiv);
      });
    }

    // ============================================
    // FILTERS
    // ============================================
    document.querySelectorAll('.filter-tab').forEach(tab => {
      tab.addEventListener('click', () => {
        const status = tab.getAttribute('data-status');
        
        if (tab.classList.contains('active')) {
          tab.classList.remove('active');
          activeStatusFilter = null;
        } else {
          document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
          tab.classList.add('active');
          activeStatusFilter = status;
        }
        
        renderRooms();
      });
    });

    document.getElementById('buildingFilter').addEventListener('change', (e) => {
      activeBuildingFilter = e.target.value;
      renderRooms();
    });

    document.getElementById('searchBar').addEventListener('input', (e) => {
      searchQuery = e.target.value;
      renderRooms();
    });

    // ============================================
    // ROOM MODAL
    // ============================================
    function openRoomModal(room) {
      const modal = document.getElementById('roomDetailsModal');
      const floor = Math.floor(room.number / 100) + (room.number % 100 <= 10 ? 'st' : 'nd') + ' Floor';
      
      document.getElementById('roomModalTitle').textContent = room.name;
      document.getElementById('roomNumber').textContent = room.name;
      document.getElementById('roomBuilding').textContent = room.building;
      document.getElementById('roomFloor').textContent = floor;
      
      const statusBadge = document.getElementById('roomStatus');
      statusBadge.className = `status-badge ${room.status}`;
      statusBadge.textContent = room.status.charAt(0).toUpperCase() + room.status.slice(1);
      
      const currentReservationGroup = document.getElementById('currentReservationGroup');
      const nextAvailableGroup = document.getElementById('nextAvailableGroup');
      const reserveBtn = document.getElementById('reserveRoomBtn');
      
      if (room.reservation) {
        currentReservationGroup.style.display = 'flex';
        document.getElementById('currentReservation').textContent = 
          `${room.reservation.faculty} - ${room.reservation.section} - ${room.reservation.time}`;
        
        nextAvailableGroup.style.display = 'flex';
        document.getElementById('nextAvailable').textContent = 
          room.status === 'pending' ? 'Pending approval' : `After ${room.reservation.time.split(' - ')[1]}`;
      } else {
        currentReservationGroup.style.display = 'none';
        nextAvailableGroup.style.display = 'none';
      }
      
      if (room.status === 'available') {
        reserveBtn.textContent = 'Reserve Room';
        reserveBtn.disabled = false;
        reserveBtn.style.opacity = '1';
        reserveBtn.onclick = () => window.location.href = `admin-reservation.php?room=${room.number}`;
      } else {
        reserveBtn.textContent = room.status === 'pending' ? 'Pending Approval' : 'Currently Reserved';
        reserveBtn.disabled = true;
        reserveBtn.style.opacity = '0.5';
      }
      
      modal.style.display = 'flex';
      setTimeout(() => modal.classList.add('show'), 10);
      lucide.createIcons();
    }

    document.getElementById('closeRoomModal').addEventListener('click', closeRoomModal);
    document.getElementById('closeRoomBtn').addEventListener('click', closeRoomModal);

    function closeRoomModal() {
      const modal = document.getElementById('roomDetailsModal');
      modal.classList.remove('show');
      setTimeout(() => modal.style.display = 'none', 300);
    }

    // ============================================
    // USAGE CIRCLE
    // ============================================
    function updateUsageCircle(percent) {
      const circle = document.getElementById('usageCircle');
      const circumference = 2 * Math.PI * 40;
      const offset = circumference - (percent / 100) * circumference;
      circle.style.strokeDasharray = circumference;
      circle.style.strokeDashoffset = offset;
    }

    // ============================================
    // CHART
    // ============================================
    const ctx = document.getElementById('roomUsageChart').getContext('2d');
    const isDarkMode = document.body.classList.contains('dark');
    
    window.roomChart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
        datasets: [{
          label: 'Usage Hours',
          data: [3, 8, 6, 4, 7.5, 3],
          backgroundColor: '#93c5fd',
          borderColor: '#3b82f6',
          borderWidth: 1,
          borderRadius: 8
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: { legend: { display: false } },
        scales: {
          y: {
            beginAtZero: true,
            max: 10,
            ticks: { stepSize: 2, color: isDarkMode ? '#fff' : '#666' },
            grid: { color: isDarkMode ? '#444' : '#e5e5e5' },
            title: { display: true, text: 'Usage Hours', color: isDarkMode ? '#fff' : '#666' }
          },
          x: {
            ticks: { color: isDarkMode ? '#fff' : '#666' },
            grid: { display: false },
            title: { display: true, text: 'Day', color: isDarkMode ? '#fff' : '#666' }
          }
        }
      }
    });

    function updateChartColors(isDark) {
      window.roomChart.options.scales.y.ticks.color = isDark ? '#fff' : '#666';
      window.roomChart.options.scales.y.grid.color = isDark ? '#444' : '#e5e5e5';
      window.roomChart.options.scales.y.title.color = isDark ? '#fff' : '#666';
      window.roomChart.options.scales.x.ticks.color = isDark ? '#fff' : '#666';
      window.roomChart.options.scales.x.title.color = isDark ? '#fff' : '#666';
      window.roomChart.update();
    }

    // ============================================
    // INIT & AUTO-REFRESH
    // ============================================
    loadRoomStatus();
    setInterval(loadRoomStatus, 30000); // Refresh every 30 seconds
  </script>

  <style>
    @keyframes spin {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
    }
  </style>
</body>
</html>