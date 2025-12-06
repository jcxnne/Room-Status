<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Schedules</title>
  <link rel="stylesheet" href="sschedule.css">
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
        <a href="snotification.php"><i data-lucide="bell"></i><span>Notifications</span></a>
        <a class="active" href="sschedule.php"><i data-lucide="calendar"></i><span>Schedule</span></a>
      </div>

      <div class="nav-links nav-bottom">
        <a id="darkModeToggle" href="#"><i data-lucide="moon"></i><span>Dark Mode</span></a>
        <a href="login.php"><i data-lucide="log-out"></i><span>Log Out</span></a>
      </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">
      <h2>Schedules</h2>

      <div class="schedule-container">
        <!-- Search and Filter -->
        <div class="search-filter">
          <input type="text" placeholder="Search section">
          <select>
            <option>Department</option>
            <option>BSCS</option>
            <option>BSIT</option>
          </select>
        </div>

        <!-- Schedule Buttons -->
        <div class="schedule-buttons">
          <!-- BSCS Sections -->
          <button class="bscs">BSCS 1-1</button>
          <button class="bscs">BSCS 1-2</button>
          <button class="bscs">BSCS 1-3</button>
          <button class="bscs">BSCS 1-4</button>
          <button class="bscs">BSCS 1-5</button>

          <button class="bscs">BSCS 2-1</button>
          <button class="bscs">BSCS 2-2</button>
          <button class="bscs">BSCS 2-3</button>
          <button class="bscs">BSCS 2-4</button>
          <button class="bscs">BSCS 2-5</button>

          <button class="bscs">BSCS 3-1</button>
          <button class="bscs">BSCS 3-2</button>
          <button class="bscs">BSCS 3-3</button>
          <button class="bscs">BSCS 3-4</button>
          <button class="bscs">BSCS 4-1</button>

          <button class="bscs">BSCS 4-2</button>
          <button class="bscs">BSCS 4-3</button>

          <!-- BSIT Sections -->
          <button class="bsit">BSIT 1-1</button>
          <button class="bsit">BSIT 1-2</button>
          <button class="bsit">BSIT 1-3</button>
        </div>
      </div>
    </div>
  </div>

<script>
  lucide.createIcons();
</script>

</body>
</html>
