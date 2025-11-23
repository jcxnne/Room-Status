<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
  <link rel="stylesheet" href="client/styles/dashboard.css">
</head>

<body>
  <main class="main">
    <div class="main-header">
      <button class="mobile-menu-btn" id="mobileMenuBtn">☰</button>
      <h1>Dashboard</h1>
      <div class="search-container">
        <div class="search-wrapper">
          <div class="search-icon">
            <svg viewBox="0 0 24 24">
              <circle cx="11" cy="11" r="8"></circle>
              <path d="m21 21-4.35-4.35"></path>
            </svg>
          </div>
          <input type="text" id="searchBar" placeholder="Search" class="search-bar">
        </div>
        <div class="logo">US</div>
      </div>
    </div>

    <div class="cards">
      <div class="card">
        <div class="card-content">
          <h3>Reservations</h3>
          <div class="card-value">
            <div class="card-icon">
              <svg viewBox="0 0 24 24">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
              </svg>
            </div>
            <p>10</p>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-content">
          <h3>Available Rooms</h3>
          <div class="card-value">
            <div class="card-icon">
              <svg viewBox="0 0 24 24">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
              </svg>
            </div>
            <p id="availableCount">12</p>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-content">
          <h3>Occupied Rooms</h3>
          <div class="card-value">
            <div class="card-icon">
              <svg viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="15" y1="9" x2="9" y2="15"></line>
                <line x1="9" y1="9" x2="15" y2="15"></line>
              </svg>
            </div>
            <p>48</p>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-content">
          <h3>Room Usage</h3>
          <div class="card-value">
            <svg class="usage-circle" viewBox="0 0 100 100">
              <circle cx="50" cy="50" r="40" fill="none" stroke="#e5e7eb" stroke-width="8" />
              <circle cx="50" cy="50" r="40" fill="none" stroke="#1e40af" stroke-width="8" stroke-dasharray="251.2"
                stroke-dashoffset="17.584" transform="rotate(-90 50 50)" />
              <text x="50" y="58" text-anchor="middle" font-size="24" font-weight="700" fill="#1a1a1a">93%</text>
            </svg>
          </div>
        </div>
      </div>
    </div>

    <div class="content-grid">
      <div class="chart-card">
        <h2>Daily Room Usage</h2>
        <div class="chart-container">
          <canvas id="usageChart"></canvas>
        </div>
      </div>

      <div class="room-status">
        <div class="room-status-header">
          <h2>Room Status</h2>
          <div class="building-filter">
            <button class="filter-button" id="filterButton">All Buildings</button>
            <div class="filter-dropdown" id="filterDropdown">
              <div class="filter-option" data-value="all">
                <span>All Buildings</span>
              </div>
              <div class="filter-option" data-value="old">
                <span>Old Building</span>
              </div>
              <div class="filter-option" data-value="new">
                <span>New Building</span>
              </div>
              <div class="filter-option add-option">
                <span>Add more</span>
                <svg viewBox="0 0 24 24">
                  <line x1="12" y1="5" x2="12" y2="19"></line>
                  <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
              </div>
            </div>
          </div>
        </div>

        <div class="room-status-content">
          <div class="rooms" id="roomsContainer">
            <div class="room available" data-room="Room 101">Room 101</div>
            <div class="room available" data-room="Room 102">Room 102</div>
            <div class="room available" data-room="Room 103">Room 103</div>
            <div class="room reserved" data-room="Room 104">Room 104</div>
            <div class="room reserved" data-room="Room 105">Room 105</div>
            <div class="room pending" data-room="Room 106">Room 106</div>
            <div class="room reserved" data-room="Room 107">Room 107</div>
            <div class="room reserved" data-room="Room 108">Room 108</div>
            <div class="room reserved" data-room="Room 109">Room 109</div>
            <div class="room available" data-room="Room 110">Room 110</div>
            <div class="room reserved" data-room="Room 111">Room 111</div>
            <div class="room reserved" data-room="Room 112">Room 112</div>
            <div class="room reserved" data-room="Room 113">Room 113</div>
            <div class="room reserved" data-room="Room 114">Room 114</div>
            <div class="room reserved" data-room="Room 115">Room 115</div>
            <div class="room available" data-room="Room 116">Room 116</div>
            <div class="room reserved" data-room="Room 117">Room 117</div>
            <div class="room available" data-room="Room 118">Room 118</div>
            <div class="room pending" data-room="Room 119">Room 119</div>
            <div class="room available" data-room="Room 120">Room 120</div>
          </div>

          <div class="vertical-filters">
            <div class="filter-tab available-tab active" data-filter="available">Available</div>
            <div class="filter-tab reserved-tab active" data-filter="reserved">Reserved</div>
            <div class="filter-tab pending-tab active" data-filter="pending">Pending</div>
          </div>
        </div>

        <div class="legend">
          <div class="legend-item">
            <div class="dot available"></div>
            Available
          </div>
          <div class="legend-item">
            <div class="dot reserved"></div>
            Reserved
          </div>
          <div class="legend-item">
            <div class="dot pending"></div>
            Pending
          </div>
        </div>
      </div>
    </div>
  </main>

  <script>
    const ctx = document.getElementById('usageChart').getContext('2d');
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
        datasets: [{
          label: 'Usage Hours',
          data: [3, 7, 5, 3, 6, 2],
          backgroundColor: '#93c5fd',
          borderRadius: 6,
          barPercentage: 0.6,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false }
        },
        scales: {
          x: {
            grid: { display: false }
          },
          y: {
            beginAtZero: true,
            max: 8,
            ticks: { stepSize: 2 },
            grid: { color: '#f3f4f6' }
          }
        }
      }
    });

    const rooms = Array.from(document.querySelectorAll('.room'));
    const filterTabs = document.querySelectorAll('.filter-tab');
    const searchBar = document.getElementById('searchBar');
    const activeFilters = new Set(['available', 'reserved', 'pending']);
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');

    const roomData = rooms.map(room => ({
      element: room,
      name: room.dataset.room.toLowerCase(),
      status: room.classList.contains('available') ? 'available' :
        room.classList.contains('reserved') ? 'reserved' : 'pending'
    }));

    function updateRooms() {
      const searchTerm = searchBar.value.toLowerCase().trim();

      roomData.forEach(({ element, name, status }) => {
        const matchesFilter = activeFilters.has(status);
        const matchesSearch = !searchTerm || name.includes(searchTerm);

        if (matchesFilter && matchesSearch) {
          element.classList.remove('hidden');
        } else {
          element.classList.add('hidden');
        }
      });

      document.getElementById('availableCount').textContent =
        roomData.filter(r => r.status === 'available' && !r.element.classList.contains('hidden')).length;
    }

    filterTabs.forEach(tab => {
      tab.addEventListener('click', () => {
        const filter = tab.dataset.filter;

        if (activeFilters.has(filter)) {
          activeFilters.delete(filter);
          tab.classList.remove('active');
        } else {
          activeFilters.add(filter);
          tab.classList.add('active');
        }

        updateRooms();
      });
    });

    searchBar.addEventListener('input', updateRooms);

    const filterButton = document.getElementById('filterButton');
    const filterDropdown = document.getElementById('filterDropdown');

    filterButton.addEventListener('click', (e) => {
      e.stopPropagation();
      filterDropdown.classList.toggle('active');
    });

    document.addEventListener('click', () => {
      filterDropdown.classList.remove('active');
    });

    filterDropdown.addEventListener('click', (e) => {
      e.stopPropagation();
      const option = e.target.closest('.filter-option');
      if (option && !option.classList.contains('add-option')) {
        filterButton.textContent = option.querySelector('span').textContent;
        filterDropdown.classList.remove('active');
      }
    });

    // Mobile search toggle
    mobileMenuBtn.addEventListener('click', () => {
      document.body.classList.toggle('mobile-search-active');
    });

    // Close search when clicking outside on mobile
    document.addEventListener('click', (e) => {
      if (!e.target.closest('.search-container') && !e.target.closest('.mobile-menu-btn')) {
        document.body.classList.remove('mobile-search-active');
      }
    });

    window.addEventListener('message', (e) => {
      if (e.data.darkMode !== undefined) {
        document.body.classList.toggle('dark', e.data.darkMode);
      }
    });

    // Handle window resize
    window.addEventListener('resize', () => {
      if (window.innerWidth > 768) {
        document.body.classList.remove('mobile-search-active');
      }
    });
  </script>
</body>

</html>