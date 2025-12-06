<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notifications</title>
  <link rel="stylesheet" href="snotification.css">
  <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body>
  <div class="container">

    <!-- SIDEBAR -->
    <div class="sidebar">
      <div class="profile">
        <a href="sprofile.php" class="profile-link">
          <img src="https://i.pravatar.cc/100?img=47" alt="Profile" />
        </a>
        <div class="profile-info">
          <h3>Joyce Anne Halili</h3>
          <p>joycehalili@gmail.com<br>Student</p>
        </div>
      </div>

      <div class="nav-links nav-top">
        <a href="sdashboard.php"><i data-lucide="layout-dashboard"></i><span>Dashboard</span></a>
        <a class="active" href="snotification.php"><i data-lucide="bell"></i><span>Notifications</span></a>
        <a href="sschedule.php"><i data-lucide="calendar"></i><span>Schedule</span></a>
      </div>

      <div class="nav-links nav-bottom">
        <a id="darkModeToggle" href="#"><i data-lucide="moon"></i><span>Dark Mode</span></a>
        <a href="login.php"><i data-lucide="log-out"></i><span>Log Out</span></a>
      </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">

<h2>Notifications</h2>

  <!-- WHITE NOTIFICATION CONTAINER -->
  <div class="notif-container">

    <!-- TABS -->
    <div class="tabs">
      <button class="tab active">All</button>
      <button class="tab">Announcements</button>
      <button class="tab">Updates</button>
      <button class="tab">Reminders</button>
    </div>

    <!-- NOTIFICATION LIST -->
    <div class="notif-list">

      <div class="notif-item highlight">
        <input type="checkbox">
        <p><strong>New semester schedule uploaded by Admin Santos.</strong></p>
        <span class="date">Oct 24</span>
      </div>

      <div class="notif-item">
        <input type="checkbox">
        <p>Room 210 reservation request denied due to overlapping schedule.</p>
        <span class="date">Oct 24</span>
      </div>

      <div class="notif-item">
        <input type="checkbox">
        <p>New schedule added for Room 105 – Web Development, 1:00–3:00 PM.</p>
        <span class="date">Oct 21</span>
      </div>

      <div class="notif-item highlight">
        <input type="checkbox">
        <p><strong>Room 204 schedule updated by Prof. Cruz.</strong></p>
        <span class="date">Oct 20</span>
      </div>

      <div class="notif-item highlight">
        <input type="checkbox">
        <p><strong>Room 301 marked as Under Maintenance until further notice.</strong></p>
        <span class="date">Oct 18</span>
      </div>

      <div class="notif-item">
        <input type="checkbox">
        <p>Scheduling conflict detected between Room 302 and Room 303.</p>
        <span class="date">Oct 18</span>
      </div>

      <div class="notif-item highlight">
        <input type="checkbox">
        <p><strong>Room 102 reservation approved for Networking class.</strong></p>
        <span class="date">Oct 18</span>
      </div>

      <div class="notif-item highlight">
        <input type="checkbox">
        <p><strong>Room 108: Class schedule extended by Prof. Santos until 3:30 PM.</strong></p>
        <span class="date">Oct 17</span>
      </div>

      <div class="notif-item">
        <input type="checkbox">
        <p>Room 404 left idle for over 3 hours — marked as Available.</p>
        <span class="date">Oct 16</span>
      </div>

    </div>
  </div> <!-- END notif-container -->

</div>

<script>
  lucide.createIcons();
</script>

</body>
</html>
