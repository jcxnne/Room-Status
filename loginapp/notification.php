<?php
/**
 * Enhanced Unified Synchronized Notification System
 * Handles real-time notifications across Admin, Faculty, and Student
 * with automatic triggers for schedules, reservations, and flagged cases
 */

class UnifiedNotificationSystem {
    private $pdo;
    private $user_id;
    private $user_type;
    
    public function __construct($pdo, $user_id, $user_type) {
        $this->pdo = $pdo;
        $this->user_id = $user_id;
        $this->user_type = $user_type;
    }
    
    /**
     * Create a new notification with automatic distribution
     */
    public static function createNotification($pdo, $data) {
        try {
            $sql = "INSERT INTO notifications (
                        title, message, type, target_role, icon,
                        reference_id, reference_type, created_by, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $data['title'],
                $data['message'],
                $data['type'] ?? 'info',
                $data['target_role'] ?? 'all',
                $data['icon'] ?? 'bell',
                $data['reference_id'] ?? null,
                $data['reference_type'] ?? null,
                $data['created_by'] ?? null
            ]);
            
            return $pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Create notification failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Notify when schedule is added/updated
     */
    public static function notifyScheduleChange($pdo, $schedule_id, $action, $created_by, $details = []) {
        $title = $action === 'created' ? 'New Schedule Added' : 'Schedule Updated';
        $message = $action === 'created' 
            ? "A new schedule has been added: {$details['course_name']} on {$details['day']} at {$details['time']}"
            : "Schedule has been updated: {$details['course_name']} on {$details['day']} at {$details['time']}";
        
        return self::createNotification($pdo, [
            'title' => $title,
            'message' => $message,
            'type' => 'update',
            'target_role' => 'all', // Notify everyone
            'icon' => 'calendar',
            'reference_id' => $schedule_id,
            'reference_type' => 'schedule',
            'created_by' => $created_by
        ]);
    }
    
    /**
     * Notify when reservation is created
     */
    public static function notifyReservation($pdo, $reservation_id, $created_by, $user_type, $details = []) {
        // Notify admin when faculty/student creates reservation
        if ($user_type !== 'admin') {
            self::createNotification($pdo, [
                'title' => 'New Reservation Request',
                'message' => "New reservation request from {$details['user_name']} for {$details['room_name']} on {$details['date']} at {$details['time']}",
                'type' => 'alert',
                'target_role' => 'admin',
                'icon' => 'clipboard-check',
                'reference_id' => $reservation_id,
                'reference_type' => 'reservation',
                'created_by' => $created_by
            ]);
        }
        
        // Notify faculty when admin creates reservation
        if ($user_type === 'admin' && isset($details['faculty_id'])) {
            self::createNotification($pdo, [
                'title' => 'Reservation Confirmed',
                'message' => "Your reservation for {$details['room_name']} on {$details['date']} at {$details['time']} has been confirmed",
                'type' => 'update',
                'target_role' => 'faculty',
                'icon' => 'check-circle',
                'reference_id' => $reservation_id,
                'reference_type' => 'reservation',
                'created_by' => $created_by
            ]);
        }
        
        return true;
    }
    
    /**
     * Notify when reservation status changes
     */
    public static function notifyReservationStatus($pdo, $reservation_id, $status, $created_by, $details = []) {
        $statusMessages = [
            'approved' => [
                'title' => 'Reservation Approved',
                'message' => "Your reservation for {$details['room_name']} on {$details['date']} has been approved",
                'type' => 'update',
                'icon' => 'check-circle'
            ],
            'rejected' => [
                'title' => 'Reservation Rejected',
                'message' => "Your reservation for {$details['room_name']} on {$details['date']} has been rejected. Reason: {$details['reason']}",
                'type' => 'alert',
                'icon' => 'x-circle'
            ],
            'cancelled' => [
                'title' => 'Reservation Cancelled',
                'message' => "Reservation for {$details['room_name']} on {$details['date']} has been cancelled",
                'type' => 'alert',
                'icon' => 'alert-circle'
            ]
        ];
        
        if (!isset($statusMessages[$status])) return false;
        
        $msg = $statusMessages[$status];
        
        return self::createNotification($pdo, [
            'title' => $msg['title'],
            'message' => $msg['message'],
            'type' => $msg['type'],
            'target_role' => $details['target_role'] ?? 'faculty',
            'icon' => $msg['icon'],
            'reference_id' => $reservation_id,
            'reference_type' => 'reservation_status',
            'created_by' => $created_by
        ]);
    }
    
    /**
     * Notify about flagged cases
     */
    public static function notifyFlaggedCase($pdo, $case_id, $severity, $created_by, $details = []) {
        $severityConfig = [
            'high' => ['icon' => 'alert-triangle', 'type' => 'alert'],
            'medium' => ['icon' => 'alert-circle', 'type' => 'alert'],
            'low' => ['icon' => 'info', 'type' => 'alert']
        ];
        
        $config = $severityConfig[$severity] ?? $severityConfig['medium'];
        
        // Notify admin
        self::createNotification($pdo, [
            'title' => 'Case Flagged: ' . ucfirst($severity) . ' Priority',
            'message' => "A {$severity} priority case has been flagged: {$details['description']}. Location: {$details['location']}",
            'type' => $config['type'],
            'target_role' => 'admin',
            'icon' => $config['icon'],
            'reference_id' => $case_id,
            'reference_type' => 'flagged_case',
            'created_by' => $created_by
        ]);
        
        // Notify faculty if relevant
        if (isset($details['notify_faculty']) && $details['notify_faculty']) {
            self::createNotification($pdo, [
                'title' => 'Security Alert: ' . ucfirst($severity),
                'message' => "Security alert in {$details['location']}: {$details['description']}",
                'type' => $config['type'],
                'target_role' => 'faculty',
                'icon' => $config['icon'],
                'reference_id' => $case_id,
                'reference_type' => 'flagged_case',
                'created_by' => $created_by
            ]);
        }
        
        return true;
    }
    
    /**
     * Get all notifications for the current user
     */
    public function getNotifications($limit = 100, $offset = 0) {
        try {
            $sql = "SELECT n.*, 
                    CASE WHEN nr.id IS NOT NULL THEN 1 ELSE 0 END as is_read,
                    TIMESTAMPDIFF(SECOND, n.created_at, NOW()) as seconds_ago
                    FROM notifications n
                    LEFT JOIN notification_reads nr ON n.id = nr.notification_id 
                        AND nr.user_id = ? AND nr.user_type = ?
                    LEFT JOIN notification_deletes nd ON n.id = nd.notification_id
                        AND nd.user_id = ? AND nd.user_type = ?
                    WHERE (n.target_role = ? OR n.target_role = 'all')
                    AND nd.id IS NULL
                    ORDER BY n.created_at DESC 
                    LIMIT ? OFFSET ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $this->user_id, $this->user_type,
                $this->user_id, $this->user_type,
                $this->user_type,
                $limit, $offset
            ]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get notifications failed: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get new notifications since last check (for real-time sync)
     */
    public function getNewNotifications($last_notification_id = 0) {
        try {
            $sql = "SELECT n.*, 
                    CASE WHEN nr.id IS NOT NULL THEN 1 ELSE 0 END as is_read,
                    TIMESTAMPDIFF(SECOND, n.created_at, NOW()) as seconds_ago
                    FROM notifications n
                    LEFT JOIN notification_reads nr ON n.id = nr.notification_id 
                        AND nr.user_id = ? AND nr.user_type = ?
                    LEFT JOIN notification_deletes nd ON n.id = nd.notification_id
                        AND nd.user_id = ? AND nd.user_type = ?
                    WHERE (n.target_role = ? OR n.target_role = 'all')
                    AND nd.id IS NULL
                    AND n.id > ?
                    ORDER BY n.created_at DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $this->user_id, $this->user_type,
                $this->user_id, $this->user_type,
                $this->user_type,
                $last_notification_id
            ]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get new notifications failed: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get unread notification count
     */
    public function getUnreadCount() {
        try {
            $sql = "SELECT COUNT(*) as count
                    FROM notifications n
                    LEFT JOIN notification_reads nr ON n.id = nr.notification_id 
                        AND nr.user_id = ? AND nr.user_type = ?
                    LEFT JOIN notification_deletes nd ON n.id = nd.notification_id
                        AND nd.user_id = ? AND nd.user_type = ?
                    WHERE (n.target_role = ? OR n.target_role = 'all')
                    AND nr.id IS NULL AND nd.id IS NULL";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $this->user_id, $this->user_type,
                $this->user_id, $this->user_type,
                $this->user_type
            ]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] ?? 0;
        } catch (PDOException $e) {
            error_log("Get unread count failed: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead($notification_id) {
        try {
            $sql = "INSERT INTO notification_reads (notification_id, user_id, user_type, read_at) 
                    VALUES (?, ?, ?, NOW()) 
                    ON DUPLICATE KEY UPDATE read_at = NOW()";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$notification_id, $this->user_id, $this->user_type]);
            return true;
        } catch (PDOException $e) {
            error_log("Mark as read failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark all notifications as read
     */
    public function markAllAsRead() {
        try {
            $sql = "SELECT n.id FROM notifications n
                    LEFT JOIN notification_reads nr ON n.id = nr.notification_id 
                        AND nr.user_id = ? AND nr.user_type = ?
                    LEFT JOIN notification_deletes nd ON n.id = nd.notification_id
                        AND nd.user_id = ? AND nd.user_type = ?
                    WHERE (n.target_role = ? OR n.target_role = 'all')
                    AND nr.id IS NULL AND nd.id IS NULL";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $this->user_id, $this->user_type,
                $this->user_id, $this->user_type,
                $this->user_type
            ]);
            $unread_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($unread_ids as $notif_id) {
                $this->markAsRead($notif_id);
            }
            
            return count($unread_ids);
        } catch (PDOException $e) {
            error_log("Mark all as read failed: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Delete notification (soft delete)
     */
    public function deleteNotification($notification_id) {
        try {
            $sql = "INSERT INTO notification_deletes (notification_id, user_id, user_type, deleted_at) 
                    VALUES (?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE deleted_at = NOW()";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$notification_id, $this->user_id, $this->user_type]);
            return true;
        } catch (PDOException $e) {
            error_log("Delete notification failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Handle AJAX requests
     */
    public function handleAjaxRequest() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
            return null;
        }
        
        header('Content-Type: application/json');
        $action = $_POST['action'];
        
        try {
            switch ($action) {
                case 'sync':
                    $last_id = isset($_POST['last_notification_id']) ? (int)$_POST['last_notification_id'] : 0;
                    $new_notifications = $this->getNewNotifications($last_id);
                    echo json_encode([
                        'success' => true,
                        'notifications' => $new_notifications,
                        'unread_count' => $this->getUnreadCount(),
                        'has_new' => count($new_notifications) > 0
                    ]);
                    break;
                
                case 'get_all':
                    $notifications = $this->getNotifications();
                    echo json_encode([
                        'success' => true,
                        'notifications' => $notifications,
                        'unread_count' => $this->getUnreadCount()
                    ]);
                    break;
                    
                case 'mark_read':
                    if (!isset($_POST['notification_id'])) {
                        echo json_encode(['success' => false, 'message' => 'Missing notification_id']);
                        exit();
                    }
                    $result = $this->markAsRead($_POST['notification_id']);
                    echo json_encode([
                        'success' => $result,
                        'unread_count' => $this->getUnreadCount()
                    ]);
                    break;
                    
                case 'mark_all_read':
                    $count = $this->markAllAsRead();
                    echo json_encode([
                        'success' => true,
                        'count' => $count,
                        'unread_count' => 0
                    ]);
                    break;
                    
                case 'delete':
                    if (!isset($_POST['notification_id'])) {
                        echo json_encode(['success' => false, 'message' => 'Missing notification_id']);
                        exit();
                    }
                    $result = $this->deleteNotification($_POST['notification_id']);
                    echo json_encode([
                        'success' => $result,
                        'unread_count' => $this->getUnreadCount()
                    ]);
                    break;
                    
                case 'get_unread_count':
                    echo json_encode([
                        'success' => true,
                        'unread_count' => $this->getUnreadCount()
                    ]);
                    break;
                    
                default:
                    echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        
        exit();
    }
    
    /**
     * Format relative time
     */
    public static function formatRelativeTime($seconds) {
        if ($seconds < 60) return 'Just now';
        if ($seconds < 3600) {
            $mins = floor($seconds / 60);
            return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
        }
        if ($seconds < 86400) {
            $hours = floor($seconds / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        }
        if ($seconds < 172800) return 'Yesterday';
        return date('M d, Y', time() - $seconds);
    }
    
    /**
     * Get JavaScript for real-time sync
     */
    public static function getSyncScript($checkInterval = 5000) {
        return <<<JAVASCRIPT
<script>
class NotificationSync {
    constructor(checkInterval = {$checkInterval}) {
        this.checkInterval = checkInterval;
        this.lastNotificationId = 0;
        this.isRunning = false;
        this.badge = document.querySelector('.notification-badge');
        this.initializeLastId();
    }
    
    initializeLastId() {
        const items = document.querySelectorAll('.notification-item[data-id]');
        if (items.length > 0) {
            this.lastNotificationId = Math.max(...Array.from(items).map(i => parseInt(i.dataset.id)));
        }
    }
    
    start() {
        if (this.isRunning) return;
        this.isRunning = true;
        this.sync();
        this.intervalId = setInterval(() => this.sync(), this.checkInterval);
    }
    
    stop() {
        this.isRunning = false;
        if (this.intervalId) clearInterval(this.intervalId);
    }
    
    async sync() {
        try {
            const formData = new FormData();
            formData.append('action', 'sync');
            formData.append('last_notification_id', this.lastNotificationId);
            
            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.updateBadge(data.unread_count);
                
                if (data.has_new && data.notifications.length > 0) {
                    this.lastNotificationId = Math.max(...data.notifications.map(n => n.id));
                    this.handleNewNotifications(data.notifications);
                }
            }
        } catch (error) {
            console.error('Notification sync error:', error);
        }
    }
    
    updateBadge(count) {
        if (this.badge) {
            if (count > 0) {
                this.badge.textContent = count > 99 ? '99+' : count;
                this.badge.style.display = 'inline-block';
            } else {
                this.badge.style.display = 'none';
            }
        }
    }
    
    handleNewNotifications(notifications) {
        // Show browser notification
        if ('Notification' in window && Notification.permission === 'granted') {
            const notif = notifications[0];
            new Notification(notif.title, {
                body: notif.message,
                icon: '/path/to/icon.png',
                tag: 'notification-' + notif.id
            });
        }
        
        // Dispatch custom event
        window.dispatchEvent(new CustomEvent('newNotifications', {
            detail: { notifications }
        }));
        
        // Add visual indicator if on notification page
        if (window.location.pathname.includes('notification')) {
            this.prependNotifications(notifications);
        }
    }
    
    prependNotifications(notifications) {
        const container = document.querySelector('.notifications-container');
        if (!container) return;
        
        // Remove empty state if exists
        const emptyState = container.querySelector('.empty-state');
        if (emptyState) emptyState.remove();
        
        notifications.reverse().forEach(notif => {
            const html = this.createNotificationHTML(notif);
            container.insertAdjacentHTML('afterbegin', html);
        });
        
        // Reinitialize icons and event listeners
        if (typeof lucide !== 'undefined') lucide.createIcons();
        this.attachEventListeners();
    }
    
    createNotificationHTML(notif) {
        const timeAgo = this.formatTime(notif.seconds_ago);
        return `
            <div class="notification-item unread" data-type="${notif.type}" data-id="${notif.id}" style="animation: slideIn 0.3s ease;">
                <div class="notification-icon type-${notif.type}">
                    <i data-lucide="${notif.icon}"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-header">
                        <h3>${notif.title}</h3>
                        <span class="notification-time">${timeAgo}</span>
                    </div>
                    <p>${notif.message}</p>
                </div>
                <div class="notification-controls-item">
                    <button class="mark-read" data-id="${notif.id}" title="Mark as read">
                        <i data-lucide="check"></i>
                    </button>
                    <button class="delete-notification" data-id="${notif.id}" title="Delete">
                        <i data-lucide="trash-2"></i>
                    </button>
                </div>
            </div>
        `;
    }
    
    formatTime(seconds) {
        if (seconds < 60) return 'Just now';
        if (seconds < 3600) return Math.floor(seconds / 60) + ' mins ago';
        if (seconds < 86400) return Math.floor(seconds / 3600) + ' hours ago';
        return 'Recently';
    }
    
    attachEventListeners() {
        // Re-attach event listeners to new notification items
        document.querySelectorAll('.mark-read').forEach(btn => {
            btn.replaceWith(btn.cloneNode(true));
        });
        document.querySelectorAll('.delete-notification').forEach(btn => {
            btn.replaceWith(btn.cloneNode(true));
        });
    }
    
    requestPermission() {
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
    }
}

// Initialize
const notificationSync = new NotificationSync();
notificationSync.start();
notificationSync.requestPermission();

// Pause when tab is hidden
document.addEventListener('visibilitychange', () => {
    document.hidden ? notificationSync.stop() : notificationSync.start();
});
</script>
JAVASCRIPT;
    }
}
?>