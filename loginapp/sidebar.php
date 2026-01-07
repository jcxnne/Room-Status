<?php
// includes/sidebar.php - Include this in the body of every page
// Make sure to include session.php before this file
?>
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
    <a href="sdashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'sdashboard.php' ? 'active' : ''; ?>">
      <i data-lucide="layout-dashboard"></i><span>Dashboard</span>
    </a>
    <a href="snotification.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'snotification.php' ? 'active' : ''; ?>">
      <i data-lucide="bell"></i><span>Notification</span>
    </a>
    <a href="sschedule.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'sschedule.php' ? 'active' : ''; ?>">
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