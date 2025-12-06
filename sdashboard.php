<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="sdashboard.css"> 
  <script src="https://unpkg.com/lucide@latest"></script>
  <title>Dashboard</title>
</head>
<body>
  <div class="sidebar">

  <div class="profile">
    <a href="sprofile.php" class="profile-link">
    <img src="https://i.pravatar.cc/100?img=47" alt="Profile" />
    <div class="profile-info"></a>
      <h3>Joyce Anne Halili</h3>
      <p>joycehalili@gmail.com<br>Student</p>
    </div>
  </div>

  <div class="nav-links nav-top">
    <a href="sdashboard.php" class="active">
    <i data-lucide="layout-dashboard"></i><span>Dashboard</span>
</a>
    <a href="snotification.php"><i data-lucide="bell"></i><span>Notification</span></a>
    <a href="#"><i data-lucide="calendar"></i><span>Schedule</span></a>
  </div>

  <div class="nav-links nav-bottom">
    <a id="darkModeToggle" href="#"><i data-lucide="moon"></i><span>Dark Mode</span></a>
    <a href="login.php"><i data-lucide="log-out"></i><span>Log Out</span></a>
  </div>

</div>


  <div class="main">
    <div class="main-header">
      <h1>Dashboard</h1>
      <div class="search-container">
        <div class="search-wrapper">
  <i data-lucide="search" class="search-icon"></i>
  <input type="text" placeholder="Search room number" class="search-bar" />
</div>
        <div class="logo">US</div>
      </div>
    </div>

    <div class="cards">
      <div class="card">
         <h3><i data-lucide="home" class="card-icon"></i> Total Rooms</h3>
         <p id="total-rooms">0</p>
      </div>
      <div class="card">
        <h3><i data-lucide="check-circle" class="card-icon"></i> Available Rooms</h3>
         <p id="available-rooms">0</p>
      </div>
      <div class="card">
         <h3><i data-lucide="x-circle" class="card-icon"></i> Occupied Rooms</h3>
       <p id="occupied-rooms">0</p>

      </div>
       <div class="card">
    <h3><i data-lucide="clock" class="card-icon"></i> Date & Time</h3>
    <p id="time" style="font-size:22px; font-weight:600; margin-bottom:5px;"></p>
    <p id="date" style="font-size:14px; color:#555;"></p>
      </div>
    </div>

    <div class="room-status">
  <div class="room-status-header">
    <h2>Room Status</h2>
    <select class="building-filter">
      <option>Building</option>
      <option>New Building</option>
      <option>Old Building</option>
    </select>
  </div>

  <div class="room-status-content">
    <div class="rooms">
      <div class="room available">Room 101</div>
      <div class="room available">Room 102</div>
      <div class="room available">Room 103</div>
      <div class="room pending">Room 104</div>
      <div class="room reserved">Room 105</div>
      <div class="room available">Room 106</div>
      <div class="room pending">Room 107</div>
      <div class="room reserved">Room 108</div>
      <div class="room reserved">Room 109</div>
      <div class="room available">Room 110</div>
      <div class="room pending">Room 104</div>
      <div class="room available">Room 111</div>
      <div class="room reserved">Room 112</div>
      <div class="room pending">Room 113</div>
      <div class="room available">Room 114</div>
       <div class="room available">Room 101</div>
      <div class="room reserved">Room 116</div>
      <div class="room available">Room 117</div>
      <div class="room pending">Room 118</div>
      <div class="room available">Room 119</div>
      <div class="room reserved">Room 120</div>
      <div class="room reserved">Room 201</div>
      <div class="room reserved">Room 201</div>
      <div class="room available">Room 202</div>
      <div class="room available">Room 111</div>
      <div class="room reserved">Room 105</div>
      <div class="room available">Room 101</div>
      <div class="room available">Room 101</div>
       <div class="room available">Room 101</div>
       <div class="room pending">Room 107</div>
       <div class="room reserved">Room 105</div>
       <div class="room pending">Room 104</div>
       <div class="room pending">Room 104</div>
       <div class="room reserved">Room 109</div>
          <div class="room available">Room 101</div>
           <div class="room reserved">Room 109</div>
            <div class="room available">Room 111</div>
            
    </div>

    <!-- Vertical Filters -->
    <div class="vertical-filters">
      <div class="filter-tab available-tab">Available</div>
      <div class="filter-tab reserved-tab">Reserved</div>
      <div class="filter-tab pending-tab">Pending</div>
    </div>
  </div>

  <div class="legend">
    <div class="legend-item"><div class="dot available"></div>Available</div>
    <div class="legend-item"><div class="dot reserved"></div>Reserved</div>
    <div class="legend-item"><div class="dot pending"></div>Pending</div>
  </div>
</div>

  <script>
    lucide.createIcons();

    // Live Time and Date
    function updateTime() {
      const now = new Date();
      const options = { weekday: 'short', month: 'short', day: 'numeric' };
      document.getElementById('time').textContent = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
      document.getElementById('date').textContent = now.toLocaleDateString('en-US', options);
    }
    setInterval(updateTime, 1000);
    updateTime();

    const toggle = document.getElementById('darkModeToggle');
const modeText = document.getElementById('mode-text');
const icon = toggle.querySelector('i');

toggle.addEventListener('click', (e) => {
  e.preventDefault();

  document.body.classList.toggle('dark');

  const isDark = document.body.classList.contains('dark');

  // Update icon
  icon.setAttribute('data-lucide', isDark ? 'sun' : 'moon');

  // Use replace() to update the icon correctly
  lucide.replace(); // or lucide.createIcons();

  // Update text
  modeText.textContent = isDark ? 'Light Mode' : 'Dark Mode';
});

    // ROOM FILTERS
const rooms = document.querySelectorAll('.rooms .room');
const filterTabs = document.querySelectorAll('.filter-tab');
const buildingSelect = document.querySelector('.building-filter');
const searchInput = document.querySelector('.search-bar');

let activeStatusFilter = null; // null = show all
let activeBuildingFilter = 'Building'; // default
let searchQuery = '';

// Function to update room visibility
function updateRoomVisibility() {
  rooms.forEach(room => {
    const statusMatch = !activeStatusFilter || room.classList.contains(activeStatusFilter);
    const buildingMatch = activeBuildingFilter === 'Building' || room.textContent.includes(activeBuildingFilter);
    const searchMatch = room.textContent.toLowerCase().includes(searchQuery.toLowerCase());

    if (statusMatch && buildingMatch && searchMatch) {
      room.style.display = 'block'; // show + move up
    } else {
      room.style.display = 'none';  // hide + remove space
    }
  });
}

// Vertical status filter click
filterTabs.forEach(tab => {
  tab.addEventListener('click', () => {
    // Toggle the filter on/off
    if (tab.classList.contains('active')) {
      tab.classList.remove('active');
      activeStatusFilter = null;
    } else {
      filterTabs.forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      if (tab.classList.contains('available-tab')) activeStatusFilter = 'available';
      if (tab.classList.contains('reserved-tab')) activeStatusFilter = 'reserved';
      if (tab.classList.contains('pending-tab')) activeStatusFilter = 'pending';
    }
    updateRoomVisibility();
  });
});

// Building dropdown filter
buildingSelect.addEventListener('change', () => {
  activeBuildingFilter = buildingSelect.value;
  updateRoomVisibility();
});

// Search filter
searchInput.addEventListener('input', () => {
  searchQuery = searchInput.value;
  updateRoomVisibility();
});

function updateRoomCounters() {
  const rooms = document.querySelectorAll('.rooms .room');
  const totalRooms = rooms.length;
  const availableRooms = document.querySelectorAll('.rooms .room.available').length;
  const occupiedRooms = document.querySelectorAll('.rooms .room.reserved, .rooms .room.pending').length;

  document.getElementById('total-rooms').textContent = totalRooms;
  document.getElementById('available-rooms').textContent = availableRooms;
  document.getElementById('occupied-rooms').textContent = occupiedRooms;
}

// Call initially
updateRoomCounters();

// Optional: update live every 5 seconds (if rooms change dynamically)
setInterval(updateRoomCounters, 5000);

  </script>
</body>
</html>
