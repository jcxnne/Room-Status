<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Profile</title>
  <link rel="stylesheet" href="sprofile.css">
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
        <a href="snotification.php"><i data-lucide="bell"></i><span>Notification</span></a>
        <a href="sschedule.php"><i data-lucide="calendar"></i><span>Schedule</span></a>
      </div>

      <div class="nav-links nav-bottom">
        <a id="darkModeToggle" href="#"><i data-lucide="moon"></i><span>Dark Mode</span></a>
        <a href="login.php"><i data-lucide="log-out"></i><span>Log Out</span></a>
      </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">
      <h2>Account</h2>
      <div class="profile-wrapper">
        <!-- LEFT CARD -->
       <div class="left-card">
  <div class="avatar-section">
    <img id="profile-avatar" src="your-img.jpg" alt="avatar">
    <button id="change-avatar-btn" class="change-avatar-btn">
     Edit
    </button>
    <input type="file" id="avatar-input" accept="image/*" style="display:none">
  </div>
  <h3>Joyce Anne Halili</h3>
  <p class="role">Student</p>
  <div class="menu-options">
    <button class="option active"><i data-lucide="user"></i> Personal Information</button>
    <button class="option"><i data-lucide="lock"></i> Password</button>
  </div>
</div>

        <!-- RIGHT CARD -->
        <div class="right-card">
          <form>
            <div class="row">
              <div class="input-group">
                <label>First name</label>
                <input type="text" value="Joyce Anne">
              </div>
              <div class="input-group">
                <label>Last name</label>
                <input type="text" value="Halili">
              </div>
            </div>

            <div class="row">
              <div class="input-group">
                <label>Cell No.</label>
                <input type="text" value="09256485237">
              </div>
              <div class="input-group">
                <label>Date of Birth</label>
                <input type="text" value="July 7, 2003">
              </div>
            </div>

            <div class="row-full">
              <div class="input-group">
                <label>Email</label>
                <input type="email" value="joycehalili@gmail.com">
              </div>
            </div>

            <button class="save-btn">Save</button>
          </form>
        </div>
      </div>
    </div>
  </div>

<script>
  lucide.createIcons();

  const changeBtn = document.getElementById('change-avatar-btn');
  const avatarInput = document.getElementById('avatar-input');
  const avatarImg = document.getElementById('profile-avatar');

  changeBtn.addEventListener('click', () => {
    avatarInput.click();
  });

  avatarInput.addEventListener('change', (e) => {
    const file = e.target.files[0];
    if(file) {
      const reader = new FileReader();
      reader.onload = () => {
        avatarImg.src = reader.result; // preview new image
      }
      reader.readAsDataURL(file);
    }
  });
</script>


</body>
</html>
