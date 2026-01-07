<?php
session_start();

// Database configuration
$host = 'localhost';
$profile_db = 'profile';
$notification_db = 'notification';
$username = 'root';
$password = '';

try {
    // Connection for user profile
    $pdo_profile = new PDO("mysql:host=$host;dbname=$profile_db", $username, $password);
    $pdo_profile->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Connection for notifications
    $pdo = new PDO("mysql:host=$host;dbname=$notification_db", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login-signup.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch faculty user data
$sql = "SELECT * FROM faculty WHERE user_id = ?";
$stmt = $pdo_profile->prepare($sql);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: login-signup.php");
    exit();
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'];
    
    try {
        switch ($action) {
            case 'mark_read':
                $notification_id = $_POST['notification_id'];
                
                // Insert or update read status
                $sql = "INSERT INTO notification_reads (notification_id, user_id, user_type) 
                        VALUES (?, ?, 'faculty') 
                        ON DUPLICATE KEY UPDATE read_at = CURRENT_TIMESTAMP";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$notification_id, $user_id]);
                
                echo json_encode(['success' => true]);
                break;
                
            case 'mark_all_read':
                // Mark all unread notifications as read for this user
                $sql = "SELECT n.id FROM notifications n
                        LEFT JOIN notification_reads nr ON n.id = nr.notification_id 
                            AND nr.user_id = ? AND nr.user_type = 'faculty'
                        WHERE n.target_role IN ('all', 'faculty')
                        AND nr.id IS NULL";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$user_id]);
                $unread_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                foreach ($unread_ids as $notif_id) {
                    $sql = "INSERT INTO notification_reads (notification_id, user_id, user_type) 
                            VALUES (?, ?, 'faculty')";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$notif_id, $user_id]);
                }
                
                echo json_encode(['success' => true, 'count' => count($unread_ids)]);
                break;
                
            case 'delete':
                $notification_id = $_POST['notification_id'];
                
                // Soft delete by marking as deleted
                $sql = "INSERT INTO notification_deletes (notification_id, user_id, user_type) 
                        VALUES (?, ?, 'faculty')
                        ON DUPLICATE KEY UPDATE deleted_at = CURRENT_TIMESTAMP";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$notification_id, $user_id]);
                
                echo json_encode(['success' => true]);
                break;
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// Fetch notifications for faculty (excluding deleted ones)
$sql = "SELECT n.*, 
        CASE 
            WHEN nr.id IS NOT NULL THEN 1 
            ELSE 0 
        END as is_read,
        DATE_FORMAT(n.created_at, '%b %d, %Y') as formatted_date,
        TIME_FORMAT(n.created_at, '%h:%i %p') as formatted_time
        FROM notifications n
        LEFT JOIN notification_reads nr ON n.id = nr.notification_id 
            AND nr.user_id = ? AND nr.user_type = 'faculty'
        LEFT JOIN notification_deletes nd ON n.id = nd.notification_id
            AND nd.user_id = ? AND nd.user_type = 'faculty'
        WHERE n.target_role IN ('all', 'faculty')
        AND nd.id IS NULL
        ORDER BY n.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id, $user_id]);
$all_notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count unread notifications
$unread_count = count(array_filter($all_notifications, function($n) { return !$n['is_read']; }));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notifications - Faculty</title>
  <link rel="stylesheet" href="notification.css">
  <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body>
  <!-- SIDEBAR -->
  <div class="sidebar">
    <!-- Profile Section -->
    <div class="profile">
      <a href="fprofile.php" class="profile-link">
        <?php
        $profile_pic = $user['profile_picture'] ?? 'https://freesvg.org/img/abstract-user-flat-3.png';
        ?>
        <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile" />
      </a>
      <div class="profile-info">
        <?php
        $full_name = $user['first_name'] . ' ' . $user['last_name'];
        $email = $user['email'];
        ?>
        <h3><?php echo htmlspecialchars($full_name); ?></h3>
        <p><?php echo htmlspecialchars($email); ?><br>Faculty</p>
      </div>
    </div>

    <!-- Main Navigation Links -->
    <div class="nav-links nav-top">
      <a href="fdashboard.php">
        <i data-lucide="layout-dashboard"></i><span>Dashboard</span>
      </a>
      <a href="fnotification.php" class="active">
        <i data-lucide="bell"></i><span>Notification</span>
      </a>
      <a href="fschedule.php">
        <i data-lucide="calendar"></i><span>Schedule</span>
      </a>
      <a href="reservation.php">
        <i data-lucide="clipboard-check"></i><span>Reservation</span>
      </a>
    </div>

    <!-- Bottom Navigation -->
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
    <!-- Header with Search -->
    <div class="main-header">
      <h2>Notifications</h2>
      <div class="search-container">
        <div class="search-wrapper">
          <i data-lucide="search" class="search-icon"></i>
          <input type="text" class="search-bar" id="searchBar" placeholder="Search notifications...">
        </div>
      </div>
    </div>

    <!-- Notification Controls -->
    <div class="notification-controls">
      <div class="filter-buttons">
        <button class="filter-btn active" data-filter="all">
          <i data-lucide="inbox"></i>
          <span>All (<?php echo count($all_notifications); ?>)</span>
        </button>
        <button class="filter-btn" data-filter="announcement">
          <i data-lucide="megaphone"></i>
          <span>Announcements</span>
        </button>
        <button class="filter-btn" data-filter="alert">
          <i data-lucide="alert-triangle"></i>
          <span>Alerts</span>
        </button>
        <button class="filter-btn" data-filter="update">
          <i data-lucide="refresh-cw"></i>
          <span>Updates</span>
        </button>
        <button class="filter-btn" data-filter="reminder">
          <i data-lucide="clock"></i>
          <span>Reminders</span>
        </button>
      </div>

      <div class="action-buttons">
        <button class="action-btn" id="markAllReadBtn">
          <i data-lucide="check-check"></i>
          <span>Mark All Read</span>
        </button>
      </div>
    </div>

    <!-- Notifications Container -->
    <div class="notifications-container">
      <?php if (empty($all_notifications)): ?>
        <div class="empty-state">
          <i data-lucide="inbox" class="empty-icon"></i>
          <h2>No notifications yet</h2>
          <p>You're all caught up! Check back later for updates.</p>
        </div>
      <?php else: ?>
        <?php foreach ($all_notifications as $notification): ?>
          <div class="notification-item <?php echo !$notification['is_read'] ? 'unread' : 'read'; ?>" 
               data-type="<?php echo htmlspecialchars($notification['type']); ?>"
               data-id="<?php echo htmlspecialchars($notification['id']); ?>">
            
            <div class="notification-icon type-<?php echo htmlspecialchars($notification['type']); ?>">
              <i data-lucide="<?php echo htmlspecialchars($notification['icon'] ?? 'bell'); ?>"></i>
            </div>
            
            <div class="notification-content">
              <div class="notification-header">
                <h3><?php echo htmlspecialchars($notification['title']); ?></h3>
                <span class="notification-time"><?php echo htmlspecialchars($notification['formatted_date']); ?></span>
              </div>
              <p><?php echo htmlspecialchars($notification['message']); ?></p>
            </div>
            
            <div class="notification-controls-item">
              <?php if (!$notification['is_read']): ?>
                <button class="mark-read" 
                        data-id="<?php echo htmlspecialchars($notification['id']); ?>"
                        title="Mark as read">
                  <i data-lucide="check"></i>
                </button>
              <?php endif; ?>
              <button class="delete-notification" 
                      data-id="<?php echo htmlspecialchars($notification['id']); ?>"
                      title="Delete">
                <i data-lucide="trash-2"></i>
              </button>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </div>

  <script>
    // Initialize Lucide Icons
    lucide.createIcons();

    // Dark Mode Toggle
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
          moonIcon.style.display = isDark ? 'none' : 'inline';
          sunIcon.style.display = isDark ? 'inline' : 'none';
        }

        if (modeText) {
          modeText.textContent = isDark ? 'Light Mode' : 'Dark Mode';
        }

        lucide.createIcons();
      }

      function toggleDarkMode(e) {
        e.preventDefault();
        const isDark = document.body.classList.toggle('dark');
        localStorage.setItem('darkMode', isDark);
        updateDarkModeUI(isDark);
      }

      initDarkMode();
      const toggle = document.getElementById('darkModeToggle');
      if (toggle) {
        toggle.addEventListener('click', toggleDarkMode);
      }
    })();

    // Filter Functionality
    const filterBtns = document.querySelectorAll('.filter-btn');
    const notificationItems = document.querySelectorAll('.notification-item');

    filterBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        // Update active button
        filterBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        
        const filter = btn.dataset.filter;
        
        // Filter notifications
        notificationItems.forEach(item => {
          const type = item.dataset.type;
          if (filter === 'all' || type === filter) {
            item.style.display = 'flex';
          } else {
            item.style.display = 'none';
          }
        });
      });
    });

    // Search Functionality
    const searchBar = document.getElementById('searchBar');
    if (searchBar) {
      searchBar.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        
        notificationItems.forEach(item => {
          const title = item.querySelector('.notification-header h3').textContent.toLowerCase();
          const message = item.querySelector('.notification-content p').textContent.toLowerCase();
          
          if (title.includes(searchTerm) || message.includes(searchTerm)) {
            item.style.display = 'flex';
          } else {
            item.style.display = 'none';
          }
        });
      });
    }

    // Mark as Read
    document.querySelectorAll('.mark-read').forEach(btn => {
      btn.addEventListener('click', async function() {
        const notificationId = this.dataset.id;
        const notifItem = this.closest('.notification-item');
        
        const formData = new FormData();
        formData.append('action', 'mark_read');
        formData.append('notification_id', notificationId);
        
        try {
          const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
          });
          
          const result = await response.json();
          
          if (result.success) {
            // Update UI
            notifItem.classList.remove('unread');
            notifItem.classList.add('read');
            this.remove(); // Remove mark as read button
          }
        } catch (error) {
          console.error('Error:', error);
        }
      });
    });

    // Mark All as Read
    document.getElementById('markAllReadBtn').addEventListener('click', async function() {
      if (!confirm('Mark all notifications as read?')) return;
      
      const formData = new FormData();
      formData.append('action', 'mark_all_read');
      
      try {
        const response = await fetch(window.location.href, {
          method: 'POST',
          body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
          location.reload();
        }
      } catch (error) {
        console.error('Error:', error);
      }
    });

    // Delete Notification
    document.querySelectorAll('.delete-notification').forEach(btn => {
      btn.addEventListener('click', async function() {
        if (!confirm('Delete this notification?')) return;
        
        const notificationId = this.dataset.id;
        const notifItem = this.closest('.notification-item');
        
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('notification_id', notificationId);
        
        try {
          const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
          });
          
          const result = await response.json();
          
          if (result.success) {
            // Animate out
            notifItem.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
              notifItem.remove();
              
              // Check if empty
              const remaining = document.querySelectorAll('.notification-item').length;
              if (remaining === 0) {
                location.reload();
              }
            }, 300);
          }
        } catch (error) {
          console.error('Error:', error);
        }
      });
    });
  </script>
</body>
</html>