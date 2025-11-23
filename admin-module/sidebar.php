<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sidebar</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="client/styles/sidebar.css">
</head>

<body>
  <aside class="sidebar">
    <div>
      <div class="profile">
        <img src="https://i.pravatar.cc/100?img=47" alt="Profile">
        <div class="profile-info">
          <h3>Joyce Anne Halili</h3>
          <p>joycehalili@gmail.com</p>
          <p>Admin</p>
        </div>
      </div>

      <nav class="nav-links">
        <a href="#" class="active" data-page="dashboard.php">
          <div class="nav-icon">
            <svg viewBox="0 0 24 24">
              <rect x="3" y="3" width="7" height="7" rx="1"></rect>
              <rect x="14" y="3" width="7" height="7" rx="1"></rect>
              <rect x="14" y="14" width="7" height="7" rx="1"></rect>
              <rect x="3" y="14" width="7" height="7" rx="1"></rect>
            </svg>
          </div>
          <span class="nav-text">Dashboard</span>
        </a>

        <a href="#" data-page="notifications.php">
          <div class="nav-icon">
            <svg viewBox="0 0 24 24">
              <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
              <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
            </svg>
          </div>
          <span class="nav-text">Notification</span>
        </a>

        <a href="#" data-page="schedule.php">
          <div class="nav-icon">
            <svg viewBox="0 0 24 24">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
              <line x1="16" y1="2" x2="16" y2="6"></line>
              <line x1="8" y1="2" x2="8" y2="6"></line>
              <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
          </div>
          <span class="nav-text">Schedule</span>
        </a>

        <a href="#" data-page="reservation.php">
          <div class="nav-icon">
            <svg viewBox="0 0 24 24">
              <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
              <polyline points="17 21 17 13 7 13 7 21"></polyline>
              <polyline points="7 3 7 8 15 8"></polyline>
            </svg>
          </div>
          <span class="nav-text">Reservation</span>
        </a>

        <a href="#" data-page="audit.php">
          <div class="nav-icon">
            <svg viewBox="0 0 24 24">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
              <polyline points="14 2 14 8 20 8"></polyline>
              <line x1="16" y1="13" x2="8" y2="13"></line>
              <line x1="16" y1="17" x2="8" y2="17"></line>
              <polyline points="10 9 9 9 8 9"></polyline>
            </svg>
          </div>
          <span class="nav-text">Audit Log</span>
        </a>

        <a href="#" data-page="report.php">
          <div class="nav-icon">
            <svg viewBox="0 0 24 24">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
              <polyline points="14 2 14 8 20 8"></polyline>
              <line x1="12" y1="18" x2="12" y2="12"></line>
              <line x1="9" y1="15" x2="15" y2="15"></line>
            </svg>
          </div>
          <span class="nav-text">Report</span>
        </a>
      </nav>
    </div>

    <nav class="nav-links nav-bottom">
      <a href="#" id="darkModeToggle">
        <div class="nav-icon">
          <svg viewBox="0 0 24 24">
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
          </svg>
        </div>
        <span class="nav-text">Dark Mode</span>
      </a>

      <a href="alogin.php" id="logoutLink">
        <div class="nav-icon">
          <svg viewBox="0 0 24 24">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
            <polyline points="16 17 21 12 16 7"></polyline>
            <line x1="21" y1="12" x2="9" y2="12"></line>
          </svg>
        </div>
        <span class="nav-text">Log Out</span>
      </a>
    </nav>
  </aside>

  <script>
    const darkModeToggle = document.getElementById('darkModeToggle');
    const logoutLink = document.getElementById('logoutLink');
    const body = document.body;
    const navLinks = document.querySelectorAll('.nav-links a[data-page]');

    darkModeToggle.addEventListener('click', (e) => {
      e.preventDefault();
      body.classList.toggle('dark');
      const isDark = body.classList.contains('dark');
      localStorage.setItem('darkMode', isDark);
      window.parent.postMessage({ darkMode: isDark }, '*');
    });

    logoutLink.addEventListener('click', (e) => {
      e.preventDefault();
      localStorage.removeItem('darkMode');
      window.top.location.href = 'alogin.php';
    });

    if (localStorage.getItem('darkMode') === 'true') {
      body.classList.add('dark');
    }

    navLinks.forEach(link => {
      link.addEventListener('click', (e) => {
        e.preventDefault();
        const targetPage = link.dataset.page;

        navLinks.forEach(l => l.classList.remove('active'));
        link.classList.add('active');

        window.parent.postMessage({ navigate: targetPage }, '*');
      });
    });
  </script>
</body>

</html>