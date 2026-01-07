<?php
// Include session and user data
include 'fsession.php';

$success_message = '';
$error_message = '';

// Handle password change form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // IMPORTANT: Fetch fresh user data from database
    $sql = "SELECT * FROM faculty WHERE user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $current_password = trim($_POST['current_password']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = "All fields are required!";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "New passwords do not match!";
    } elseif (strlen($new_password) < 6) {
        $error_message = "New password must be at least 6 characters!";
    } elseif ($current_password === $new_password) {
        $error_message = "New password must be different from current password!";
    } else {
        // Check if password field exists
        if (!isset($user['password'])) {
            $error_message = "Password field not found in database!";
        } 
        // Check if password is hashed (bcrypt hashes start with $2y$ and are 60 chars)
        elseif (strlen($user['password']) < 50) {
            $error_message = "Password in database is not properly hashed! Please contact administrator.";
        }
        // Verify current password
        elseif (password_verify($current_password, $user['password'])) {
            // Hash new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            try {
                // Update password in database
                $sql = "UPDATE faculty SET password = ? WHERE user_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$hashed_password, $user_id]);
                
                $success_message = "Password updated successfully!";
                
                // Clear form
                $_POST = array();
                
            } catch(PDOException $e) {
                $error_message = "Error updating password: " . $e->getMessage();
            }
        } else {
            $error_message = "Current password is incorrect!";
        }
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
  <title>Change Password - Faculty Account Settings</title>
</head>
<body>
  <!-- SIDEBAR -->
  <div class="sidebar">
    <!-- Profile Section - Always Centered -->
    <div class="profile">
      <a href="fprofile.php" class="profile-link">
        <img src="<?php echo htmlspecialchars($user['profile_picture'] ?? 'https://freesvg.org/img/abstract-user-flat-3.png'); ?>" alt="Profile" />
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

  <!-- MAIN CONTENT -->
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
            <img src="<?php echo htmlspecialchars($user['profile_picture'] ?? 'https://i.pravatar.cc/150?img=12'); ?>" 
                 alt="Profile Picture" 
                 class="profile-picture" />
          </div>
          <div class="profile-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
          <div class="profile-role">Faculty</div>
         

          <div class="profile-nav">
            <a href="fprofile.php" class="profile-nav-item">
              <i data-lucide="user"></i>
              <span>Personal Information</span>
            </a>
            <a href="fpassword.php" class="profile-nav-item active">
              <i data-lucide="lock"></i>
              <span>Password</span>
            </a>
          </div>
        </div>

        <div class="profile-content">
          <div class="password-info">
            <h3><i data-lucide="shield-check" style="width: 18px; height: 18px; display: inline; vertical-align: middle;"></i> Password Security Tips</h3>
            <p>Keep your account secure by following these guidelines:</p>
            <ul>
              <li>Use at least 6 characters (longer is better)</li>
              <li>Mix uppercase and lowercase letters</li>
              <li>Include numbers and special characters</li>
              <li>Avoid common words or personal information</li>
              <li>Don't reuse passwords from other accounts</li>
            </ul>
          </div>

          <form method="POST" id="passwordForm">
            <div class="form-group">
              <label class="form-label">
                <i data-lucide="key" style="width: 16px; height: 16px;"></i>
                Current Password
              </label>
              <div class="password-input-wrapper">
                <input type="password" 
                       name="current_password" 
                       id="currentPassword"
                       class="form-input" 
                       required 
                       autocomplete="current-password" />
                <button type="button" class="toggle-password" onclick="togglePasswordVisibility('currentPassword', this)">
                  <i data-lucide="eye" style="width: 20px; height: 20px;"></i>
                </button>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">
                <i data-lucide="lock" style="width: 16px; height: 16px;"></i>
                New Password
              </label>
              <div class="password-input-wrapper">
                <input type="password" 
                       name="new_password" 
                       id="newPassword"
                       class="form-input" 
                       required 
                       minlength="6"
                       autocomplete="new-password"
                       oninput="checkPasswordStrength(this.value)" />
                <button type="button" class="toggle-password" onclick="togglePasswordVisibility('newPassword', this)">
                  <i data-lucide="eye" style="width: 20px; height: 20px;"></i>
                </button>
              </div>
              <div id="passwordStrength"></div>
            </div>

            <div class="form-group">
              <label class="form-label">
                <i data-lucide="lock" style="width: 16px; height: 16px;"></i>
                Confirm New Password
              </label>
              <div class="password-input-wrapper">
                <input type="password" 
                       name="confirm_password" 
                       id="confirmPassword"
                       class="form-input" 
                       required 
                       minlength="6"
                       autocomplete="new-password" />
                <button type="button" class="toggle-password" onclick="togglePasswordVisibility('confirmPassword', this)">
                  <i data-lucide="eye" style="width: 20px; height: 20px;"></i>
                </button>
              </div>
            </div>

            <button type="submit" class="update-btn">Update Password</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script>
    lucide.createIcons();

    // Toggle password visibility
    function togglePasswordVisibility(inputId, button) {
      const input = document.getElementById(inputId);
      const icon = button.querySelector('i');
      
      if (input.type === 'password') {
        input.type = 'text';
        icon.setAttribute('data-lucide', 'eye-off');
      } else {
        input.type = 'password';
        icon.setAttribute('data-lucide', 'eye');
      }
      
      lucide.createIcons();
    }

    // Password strength checker
    function checkPasswordStrength(password) {
      const strengthDiv = document.getElementById('passwordStrength');
      
      if (password.length === 0) {
        strengthDiv.innerHTML = '';
        return;
      }
      
      let strength = 0;
      
      // Length check
      if (password.length >= 6) strength++;
      if (password.length >= 8) strength++;
      if (password.length >= 12) strength++;
      
      // Complexity checks
      if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
      if (/\d/.test(password)) strength++;
      if (/[^a-zA-Z\d]/.test(password)) strength++;
      
      // Determine strength level
      let strengthClass = '';
      let strengthText = '';
      let barWidth = '0%';
      let barColor = '#e0e0e0';
      
      if (strength <= 2) {
        strengthClass = 'strength-weak';
        strengthText = 'Weak';
        barWidth = '33%';
        barColor = '#dc2626';
      } else if (strength <= 4) {
        strengthClass = 'strength-medium';
        strengthText = 'Medium';
        barWidth = '66%';
        barColor = '#f59e0b';
      } else {
        strengthClass = 'strength-strong';
        strengthText = 'Strong';
        barWidth = '100%';
        barColor = '#16a34a';
      }
      
      strengthDiv.innerHTML = `
        <div class="password-strength ${strengthClass}">
          Password Strength: ${strengthText}
        </div>
        <div class="strength-bar">
          <div class="strength-bar-fill" style="width: ${barWidth}; background: ${barColor};"></div>
        </div>
      `;
    }

    // Form validation
    document.getElementById('passwordForm').addEventListener('submit', function(e) {
      const newPassword = document.getElementById('newPassword').value;
      const confirmPassword = document.getElementById('confirmPassword').value;
      
      if (newPassword !== confirmPassword) {
        e.preventDefault();
        alert('New passwords do not match!');
        return false;
      }
    });

    // ============================================
    // UNIVERSAL DARK MODE FUNCTION
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