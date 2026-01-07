<?php
// Include session and user data
include 'session.php';

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $cell_no = trim($_POST['cell_no']);
    $email = trim($_POST['email']);
    $date_of_birth = $_POST['date_of_birth'];
    
    // Handle profile picture upload
    $profile_picture = null;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
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
        }
    }
    
    try {
        if ($profile_picture) {
            $sql = "UPDATE students SET first_name = ?, last_name = ?, cell_no = ?, email = ?, date_of_birth = ?, profile_picture = ? WHERE user_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$first_name, $last_name, $cell_no, $email, $date_of_birth, $profile_picture, $user_id]);
        } else {
            $sql = "UPDATE students SET first_name = ?, last_name = ?, cell_no = ?, email = ?, date_of_birth = ? WHERE user_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$first_name, $last_name, $cell_no, $email, $date_of_birth, $user_id]);
        }
        
        $success_message = "Profile updated successfully!";
        
        // Refresh user data
        $sql = "SELECT * FROM students WHERE user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        $error_message = "Error updating profile: " . $e->getMessage();
    }
}
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
  <?php include 'sidebar.php'; ?>

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
            <img src="<?php echo htmlspecialchars($user['profile_picture'] ?? 'https://freesvg.org/img/abstract-user-flat-3.png'); ?>" 
                 alt="Profile Picture" 
                 class="profile-picture"
                 id="profilePicturePreview" />
            <button type="button" class="edit-picture-btn" onclick="document.getElementById('profilePictureInput').click()">
              <i data-lucide="pencil" style="width: 20px; height: 20px;"></i>
            </button>
          </div>
          <div class="profile-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
          <div class="profile-role">Student</div>

          <div class="profile-nav">
            <a href="sprofile.php" class="profile-nav-item active">
              <i data-lucide="user"></i>
              <span>Personal Information</span>
            </a>
            <a href="password.php" class="profile-nav-item">
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
                   accept="image/*"
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