<?php
session_start();

// Database configuration
$host = 'localhost';
$user_db = 'user_db';
$notification_db = 'notification';
$username = 'root';
$password = '';

try {
    // Connection for user authentication
    $pdo_user = new PDO("mysql:host=$host;dbname=$user_db", $username, $password);
    $pdo_user->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Connection for notifications
    $pdo_notification = new PDO("mysql:host=$host;dbname=$notification_db", $username, $password);
    $pdo_notification->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin-login.php");
    exit();
}

$user_id = $_SESSION['admin_id'];

// Fetch admin user data
$sql = "SELECT * FROM admins WHERE id = ?";
$stmt = $pdo_user->prepare($sql);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: admin-login.php");
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
                $sql = "UPDATE notifications SET is_read = 1 WHERE id = ?";
                $stmt = $pdo_notification->prepare($sql);
                $stmt->execute([$notification_id]);
                echo json_encode(['success' => true, 'message' => 'Marked as read']);
                break;
                
            case 'mark_all_read':
                $sql = "UPDATE notifications SET is_read = 1 WHERE target_role IN ('admin', 'all') AND is_read = 0";
                $stmt = $pdo_notification->prepare($sql);
                $stmt->execute();
                echo json_encode(['success' => true, 'message' => 'All notifications marked as read']);
                break;
                
            case 'delete':
                $notification_id = $_POST['notification_id'];
                $sql = "DELETE FROM notifications WHERE id = ?";
                $stmt = $pdo_notification->prepare($sql);
                $stmt->execute([$notification_id]);
                echo json_encode(['success' => true, 'message' => 'Notification deleted']);
                break;
                
            case 'delete_all':
                $sql = "DELETE FROM notifications WHERE target_role IN ('admin', 'all')";
                $stmt = $pdo_notification->prepare($sql);
                $stmt->execute();
                echo json_encode(['success' => true, 'message' => 'All notifications cleared']);
                break;
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// Fetch notifications for admin
$sql = "SELECT * FROM notifications 
        WHERE target_role IN ('admin', 'all') 
        ORDER BY created_at DESC 
        LIMIT 100";
$stmt = $pdo_notification->query($sql);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count unread notifications
$sql = "SELECT COUNT(*) as unread_count FROM notifications 
        WHERE target_role IN ('admin', 'all') AND is_read = 0";
$stmt = $pdo_notification->query($sql);
$unread_count = $stmt->fetch(PDO::FETCH_ASSOC)['unread_count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="admin-dashboard.css"> 
  <link rel="stylesheet" href="admin-notification.css"> 
  <script src="https://unpkg.com/lucide@latest"></script>
  <title>Notifications - Admin Dashboard</title>
</head>
<body>
  <div class="sidebar">
    <!-- Profile Section -->
    <div class="profile">
      <a href="admin-profile.php" class="profile-link">
        <img src="<?php echo htmlspecialchars($user['profile_picture'] ?? 'https://freesvg.org/img/abstract-user-flat-3.png'); ?>" alt="Profile" />
      </a>
      <div class="profile-info">
        <h3><?php echo htmlspecialchars($user['full_name']); ?></h3>
        <p><?php echo htmlspecialchars($user['email']); ?><br>Administrator</p>
      </div>
    </div>

    <!-- Main Navigation Links -->
    <div class="nav-links nav-top">
      <a href="admin-dashboard.php">
        <i data-lucide="layout-dashboard"></i><span>Dashboard</span>
      </a>
      <a href="admin-notification.php" class="active">
        <i data-lucide="bell"></i><span>Notification</span>
        <?php if ($unread_count > 0): ?>
          <span class="notification-badge"><?php echo $unread_count; ?></span>
        <?php endif; ?>
      </a>
      <a href="admin-schedule.php">
        <i data-lucide="calendar"></i><span>Schedule</span>
      </a>
      <a href="admin-reservation.php">
        <i data-lucide="clipboard-check"></i><span>Reservation</span>
      </a>
      <a href="admin-audit.php">
        <i data-lucide="file-search"></i><span>Audit</span>
      </a>
      <a href="admin-report.php">
        <i data-lucide="bar-chart-3"></i><span>Reports</span>
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

  <div class="main">
    <div class="main-header">
      <h1>Notifications</h1>
      <div class="logo"><?php echo strtoupper(substr($user['full_name'], 0, 2)); ?></div>
    </div>

    <!-- Notification Controls -->
    <div class="notification-controls">
      <div class="filter-buttons">
        <button class="filter-btn active" data-filter="all">
          <i data-lucide="inbox"></i> All
        </button>
        <button class="filter-btn" data-filter="announcement">
          <i data-lucide="megaphone"></i> Announcements
        </button>
        <button class="filter-btn" data-filter="alert">
          <i data-lucide="alert-triangle"></i> Alerts
        </button>
        <button class="filter-btn" data-filter="update">
          <i data-lucide="refresh-cw"></i> Updates
        </button>
        <button class="filter-btn" data-filter="reminder">
          <i data-lucide="clock"></i> Reminders
        </button>
      </div>
      
      <div class="action-buttons">
        <button class="action-btn" id="markAllReadBtn">
          <i data-lucide="check-check"></i> Mark All Read
        </button>
        <button class="action-btn" id="deleteAllBtn">
          <i data-lucide="trash-2"></i> Clear All
        </button>
      </div>
    </div>

    <!-- Notifications List -->
    <div class="notifications-container">
      <?php if (empty($notifications)): ?>
        <div class="empty-state">
          <i data-lucide="bell-off" class="empty-icon"></i>
          <h2>No Notifications</h2>
          <p>You're all caught up! No new notifications at this time.</p>
        </div>
      <?php else: ?>
        <?php foreach ($notifications as $notification): ?>
          <div class="notification-item <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>" 
               data-id="<?php echo $notification['id']; ?>"
               data-type="<?php echo $notification['type']; ?>">
            <div class="notification-icon type-<?php echo $notification['type']; ?>">
              <i data-lucide="<?php echo htmlspecialchars($notification['icon']); ?>"></i>
            </div>
            <div class="notification-content">
              <div class="notification-header">
                <h3><?php echo htmlspecialchars($notification['title']); ?></h3>
                <span class="notification-time">
                  <?php 
                    $time = strtotime($notification['created_at']);
                    $now = time();
                    $diff = $now - $time;
                    
                    if ($diff < 60) {
                      echo 'Just now';
                    } elseif ($diff < 3600) {
                      echo floor($diff / 60) . ' mins ago';
                    } elseif ($diff < 86400) {
                      echo floor($diff / 3600) . ' hours ago';
                    } elseif ($diff < 172800) {
                      echo 'Yesterday';
                    } else {
                      echo date('M d, Y', $time);
                    }
                  ?>
                </span>
              </div>
              <p><?php echo htmlspecialchars($notification['message']); ?></p>
            </div>
            <div class="notification-controls-item">
              <?php if (!$notification['is_read']): ?>
                <button class="mark-read" title="Mark as read" onclick="markAsRead(<?php echo $notification['id']; ?>)">
                  <i data-lucide="check"></i>
                </button>
              <?php endif; ?>
              <button class="delete-notification" title="Delete" onclick="deleteNotification(<?php echo $notification['id']; ?>)">
                <i data-lucide="trash-2"></i>
              </button>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <script>
    lucide.createIcons();

    // Dark Mode
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

    // Filter Notifications
    const filterBtns = document.querySelectorAll('.filter-btn');
    const notificationItems = document.querySelectorAll('.notification-item');

    filterBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        // Update active button
        filterBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        // Filter notifications
        const filter = btn.dataset.filter;
        notificationItems.forEach(item => {
          if (filter === 'all' || item.dataset.type === filter) {
            item.style.display = 'flex';
          } else {
            item.style.display = 'none';
          }
        });

        lucide.createIcons();
      });
    });

    // Mark as Read
    async function markAsRead(notificationId) {
      const formData = new FormData();
      formData.append('action', 'mark_read');
      formData.append('notification_id', notificationId);

      try {
        const response = await fetch('admin-notification.php', {
          method: 'POST',
          body: formData
        });

        const result = await response.json();
        if (result.success) {
          const item = document.querySelector(`[data-id="${notificationId}"]`);
          item.classList.remove('unread');
          item.classList.add('read');
          const markBtn = item.querySelector('.mark-read');
          if (markBtn) markBtn.remove();
          
          // Update badge
          updateNotificationBadge();
        }
      } catch (error) {
        console.error('Error:', error);
        alert('Failed to mark notification as read');
      }
    }

    // Delete Notification
    async function deleteNotification(notificationId) {
      if (!confirm('Delete this notification?')) return;

      const formData = new FormData();
      formData.append('action', 'delete');
      formData.append('notification_id', notificationId);

      try {
        const response = await fetch('admin-notification.php', {
          method: 'POST',
          body: formData
        });

        const result = await response.json();
        if (result.success) {
          const item = document.querySelector(`[data-id="${notificationId}"]`);
          item.classList.add('removing');
          setTimeout(() => {
            item.remove();
            checkEmpty();
            updateNotificationBadge();
          }, 300);
        }
      } catch (error) {
        console.error('Error:', error);
        alert('Failed to delete notification');
      }
    }

    // Mark All Read
    document.getElementById('markAllReadBtn').addEventListener('click', async () => {
      const formData = new FormData();
      formData.append('action', 'mark_all_read');

      try {
        const response = await fetch('admin-notification.php', {
          method: 'POST',
          body: formData
        });

        const result = await response.json();
        if (result.success) {
          location.reload();
        }
      } catch (error) {
        console.error('Error:', error);
        alert('Failed to mark all as read');
      }
    });

    // Delete All
    document.getElementById('deleteAllBtn').addEventListener('click', async () => {
      if (!confirm('Delete all notifications? This cannot be undone.')) return;

      const formData = new FormData();
      formData.append('action', 'delete_all');

      try {
        const response = await fetch('admin-notification.php', {
          method: 'POST',
          body: formData
        });

        const result = await response.json();
        if (result.success) {
          location.reload();
        }
      } catch (error) {
        console.error('Error:', error);
        alert('Failed to delete all notifications');
      }
    });

    // Check if empty
    function checkEmpty() {
      const container = document.querySelector('.notifications-container');
      const items = container.querySelectorAll('.notification-item');
      if (items.length === 0) {
        container.innerHTML = `
          <div class="empty-state">
            <i data-lucide="bell-off" class="empty-icon"></i>
            <h2>No Notifications</h2>
            <p>You're all caught up! No new notifications at this time.</p>
          </div>
        `;
        lucide.createIcons();
      }
    }

    // Update notification badge
    function updateNotificationBadge() {
      const unreadCount = document.querySelectorAll('.notification-item.unread').length;
      const badge = document.querySelector('.notification-badge');
      if (badge) {
        if (unreadCount > 0) {
          badge.textContent = unreadCount;
        } else {
          badge.remove();
        }
      }
    }
  </script>
</body>
</html>