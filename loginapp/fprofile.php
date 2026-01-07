<?php
// Include faculty session
include 'fsession.php';

$success_message = '';
$error_message = '';

// Default profile picture - gray user icon from TransparentPNG
$default_profile_picture = 'https://freesvg.org/img/abstract-user-flat-3.png';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $cell_no = trim($_POST['cell_no']);
    $email = trim($_POST['email']);
    $date_of_birth = $_POST['date_of_birth'];
    $department = trim($_POST['department']);
    $position = trim($_POST['position']);
    
    // Handle profile picture upload
    $profile_picture = null;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
        $file_type = $_FILES['profile_picture']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            $upload_dir = 'uploads/profiles/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $new_filename = $user_id . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                $profile_picture = $upload_path;
            }
        } else {
            $error_message = "Invalid file type. Please upload a JPG, PNG, or GIF image.";
        }
    }
    
    if (!$error_message) {
        try {
            if ($profile_picture) {
                $sql = "UPDATE faculty SET first_name = ?, last_name = ?, cell_no = ?, email = ?, date_of_birth = ?, department = ?, position = ?, profile_picture = ? WHERE user_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$first_name, $last_name, $cell_no, $email, $date_of_birth, $department, $position, $profile_picture, $user_id]);
            } else {
                $sql = "UPDATE faculty SET first_name = ?, last_name = ?, cell_no = ?, email = ?, date_of_birth = ?, department = ?, position = ? WHERE user_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$first_name, $last_name, $cell_no, $email, $date_of_birth, $department, $position, $user_id]);
            }
            
            $success_message = "Profile updated successfully!";
            
            // Refresh user data
            $sql = "SELECT * FROM faculty WHERE user_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch(PDOException $e) {
            $error_message = "Error updating profile: " . $e->getMessage();
        }
    }
}

// Use default profile picture if none exists
$profile_pic_src = (!empty($user['profile_picture']) && file_exists($user['profile_picture'])) 
    ? htmlspecialchars($user['profile_picture']) 
    : $default_profile_picture;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="dashboard.css">
  <link rel="stylesheet" href="profile.css">
  <script src="https://unpkg.com/lucide@latest"></script>
  <title>Profile - Account Settings</title>
</head>
<body>
  <div class="sidebar">
    <!-- Profile Section - Always Centered -->
    <div class="profile">
      <a href="fprofile.php" class="profile-link">
        <img src="<?php echo $profile_pic_src; ?>" alt="Profile" />
      </a>
      <div class="profile-info">
        <h3><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
        <p><?php echo htmlspecialchars($user['email']); ?><br>Faculty</p>
      </div>
    </div>

    <!-- Main Navigation Links - Left Aligned -->
    <div class="nav-links nav-top">
      <a href="fdashboard.php">
        <i data-lucide="layout-dashboard"></i><span>Dashboard</span>
      </a>
      <a href="fnotification.php">
        <i data-lucide="bell"></i><span>Notification</span>
      </a>
      <a href="fschedule.php">
        <i data-lucide="calendar"></i><span>Schedule</span>
      </a>
      <a href="reservation.php">
        <i data-lucide="clipboard-check"></i><span>Reservation</span>
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

  <div class="main">
    <div class="profile-container">
      <h1 class="profile-header">Account</h1>

      <?php if ($success_message): ?>
        <div class="alert alert-success">
          <i data-lucide="check-circle" style="width: 20px; height: 20px;"></i>
          <?php echo htmlspecialchars($success_message); ?>
        </div>
      <?php endif; ?>

      <?php if ($error_message): ?>
        <div class="alert alert-error">
          <i data-lucide="alert-circle" style="width: 20px; height: 20px;"></i>
          <?php echo htmlspecialchars($error_message); ?>
        </div>
      <?php endif; ?>

      <div class="profile-card">
        <div class="profile-sidebar">
          <div class="profile-picture-container">
            <img src="<?php echo $profile_pic_src; ?>" 
                 alt="Profile Picture" 
                 class="profile-picture"
                 id="profilePicturePreview" />
            <button type="button" class="edit-picture-btn" onclick="document.getElementById('profilePictureInput').click()">
              <i data-lucide="pencil" style="width: 20px; height: 20px;"></i>
            </button>
          </div>
          <div class="profile-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
          <div class="profile-role">Faculty</div>

          <div class="profile-nav">
            <a href="fprofile.php" class="profile-nav-item active">
              <i data-lucide="user"></i>
              <span>Personal Information</span>
            </a>
            <a href="fpassword.php" class="profile-nav-item">
              <i data-lucide="lock"></i>
              <span>Password</span>
            </a>
          </div>
        </div>

        <div class="profile-content">
          <form method="POST" enctype="multipart/form-data" id="profileForm">
            <input type="file" 
                   name="profile_picture" 
                   id="profilePictureInput" 
                   class="profile-picture-input"
                   accept="image/jpeg,image/png,image/gif,image/jpg"
                   onchange="previewProfilePicture(event)" />

            <div class="form-row">
              <div class="form-group">
                <label class="form-label">First name</label>
                <input type="text" 
                       name="first_name" 
                       class="form-input" 
                       value="<?php echo htmlspecialchars($user['first_name']); ?>" 
                       required />
              </div>
              <div class="form-group">
                <label class="form-label">Last name</label>
                <input type="text" 
                       name="last_name" 
                       class="form-input" 
                       value="<?php echo htmlspecialchars($user['last_name']); ?>" 
                       required />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Cell No.</label>
                <input type="text" 
                       name="cell_no" 
                       class="form-input" 
                       value="<?php echo htmlspecialchars($user['cell_no']); ?>" 
                       required />
              </div>
              <div class="form-group">
                <label class="form-label">Date of Birth</label>
                <input type="date" 
                       name="date_of_birth" 
                       class="form-input" 
                       value="<?php echo htmlspecialchars($user['date_of_birth']); ?>" 
                       required />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Department</label>
                <input type="text" 
                       name="department" 
                       class="form-input" 
                       value="<?php echo htmlspecialchars($user['department'] ?? ''); ?>" 
                       required />
              </div>
              <div class="form-group">
                <label class="form-label">Position</label>
                <input type="text" 
                       name="position" 
                       class="form-input" 
                       value="<?php echo htmlspecialchars($user['position'] ?? ''); ?>" 
                       required />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group full-width">
                <label class="form-label">Email</label>
                <input type="email" 
                       name="email" 
                       class="form-input" 
                       value="<?php echo htmlspecialchars($user['email']); ?>" 
                       required />
              </div>
            </div>

            <button type="submit" class="save-btn">Save</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script>
    lucide.createIcons();

    // Profile picture preview
    function previewProfilePicture(event) {
      const file = event.target.files[0];
      if (file) {
        // Validate file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
          alert('File is too large. Maximum size is 5MB.');
          event.target.value = '';
          return;
        }

        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
        if (!allowedTypes.includes(file.type)) {
          alert('Invalid file type. Please upload a JPG, PNG, or GIF image.');
          event.target.value = '';
          return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
          document.getElementById('profilePicturePreview').src = e.target.result;
        };
        reader.readAsDataURL(file);
      }
    }

    // Dark Mode Implementation
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

        lucide.createIcons();
      }

      function toggleDarkMode(e) {
        e.preventDefault();
        const body = document.body;
        const isDark = body.classList.toggle('dark');
        localStorage.setItem('darkMode', isDark);
        updateDarkModeUI(isDark);
      }

      initDarkMode();
      
      const toggle = document.getElementById('darkModeToggle');
      if (toggle) {
        toggle.addEventListener('click', toggleDarkMode);
      }
    })();
  </script>
</body>
</html>