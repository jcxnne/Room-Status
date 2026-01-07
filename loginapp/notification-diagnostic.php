<?php
/**
 * Diagnostic and Fix Script for Notification System
 * Run this file once to check and fix notification issues
 */

// Database configuration
$host = 'localhost';
$notification_db = 'notification';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$notification_db", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database connected successfully<br><br>";
} catch(PDOException $e) {
    die("❌ Connection failed: " . $e->getMessage());
}

echo "<h2>📊 Notification System Diagnostics</h2>";

// 1. Check notifications table structure
echo "<h3>1. Checking notifications table structure...</h3>";
$sql = "DESCRIBE notifications";
$stmt = $pdo->query($sql);
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "Columns in notifications table: " . implode(', ', $columns) . "<br>";

$has_is_read = in_array('is_read', $columns);
echo $has_is_read ? "⚠️ WARNING: 'is_read' column exists (old system)<br>" : "✅ No 'is_read' column (correct for new system)<br>";
echo "<br>";

// 2. Count total notifications
echo "<h3>2. Counting notifications...</h3>";
$sql = "SELECT COUNT(*) as total FROM notifications";
$stmt = $pdo->query($sql);
$total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
echo "Total notifications: <strong>{$total}</strong><br>";

// Count by target_role
$sql = "SELECT target_role, COUNT(*) as count FROM notifications GROUP BY target_role";
$stmt = $pdo->query($sql);
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Target Role</th><th>Count</th></tr>";
foreach ($roles as $role) {
    echo "<tr><td>{$role['target_role']}</td><td>{$role['count']}</td></tr>";
}
echo "</table><br>";

// 3. Check tracking tables
echo "<h3>3. Checking tracking tables...</h3>";

// Check notification_reads
$sql = "SELECT COUNT(*) as total FROM notification_reads";
$stmt = $pdo->query($sql);
$reads = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
echo "Total read records: <strong>{$reads}</strong><br>";

// Check notification_deletes
$sql = "SELECT COUNT(*) as total FROM notification_deletes";
$stmt = $pdo->query($sql);
$deletes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
echo "Total delete records: <strong>{$deletes}</strong><br><br>";

// 4. Test queries for each user type
echo "<h3>4. Testing queries for each user type...</h3>";

$user_types = ['admin', 'faculty', 'student'];
$test_user_id = 1; // Test with user_id = 1

foreach ($user_types as $user_type) {
    echo "<strong>Testing for {$user_type} (user_id={$test_user_id}):</strong><br>";
    
    $sql = "SELECT COUNT(*) as count
            FROM notifications n
            LEFT JOIN notification_reads nr ON n.id = nr.notification_id 
                AND nr.user_id = ? AND nr.user_type = ?
            LEFT JOIN notification_deletes nd ON n.id = nd.notification_id
                AND nd.user_id = ? AND nd.user_type = ?
            WHERE (n.target_role = ? OR n.target_role = 'all')
            AND nd.id IS NULL";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$test_user_id, $user_type, $test_user_id, $user_type, $user_type]);
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "→ Visible notifications: <strong>{$count}</strong><br>";
    
    // Count unread
    $sql = "SELECT COUNT(*) as count
            FROM notifications n
            LEFT JOIN notification_reads nr ON n.id = nr.notification_id 
                AND nr.user_id = ? AND nr.user_type = ?
            LEFT JOIN notification_deletes nd ON n.id = nd.notification_id
                AND nd.user_id = ? AND nd.user_type = ?
            WHERE (n.target_role = ? OR n.target_role = 'all')
            AND nr.id IS NULL
            AND nd.id IS NULL";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$test_user_id, $user_type, $test_user_id, $user_type, $user_type]);
    $unread = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "→ Unread notifications: <strong>{$unread}</strong><br><br>";
}

// 5. Show sample notifications
echo "<h3>5. Sample notifications (first 5):</h3>";
$sql = "SELECT id, type, title, target_role, created_at FROM notifications ORDER BY created_at DESC LIMIT 5";
$stmt = $pdo->query($sql);
$samples = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Type</th><th>Title</th><th>Target Role</th><th>Created At</th></tr>";
foreach ($samples as $notif) {
    echo "<tr>";
    echo "<td>{$notif['id']}</td>";
    echo "<td>{$notif['type']}</td>";
    echo "<td>{$notif['title']}</td>";
    echo "<td>{$notif['target_role']}</td>";
    echo "<td>{$notif['created_at']}</td>";
    echo "</tr>";
}
echo "</table><br>";

// 6. OPTIONAL FIX: Remove is_read column if it exists
echo "<h3>6. Fixes Available:</h3>";

if ($has_is_read) {
    echo "<div style='background: #fff3cd; padding: 10px; border: 1px solid #ffc107; margin: 10px 0;'>";
    echo "⚠️ <strong>WARNING:</strong> Your notifications table has an 'is_read' column from the old system.<br>";
    echo "The new unified system doesn't use this column.<br><br>";
    echo "<strong>Options:</strong><br>";
    echo "1. Keep it (won't cause issues, just unused)<br>";
    echo "2. Remove it (run the SQL below in phpMyAdmin):<br>";
    echo "<code>ALTER TABLE notifications DROP COLUMN is_read;</code><br>";
    echo "</div>";
}

// 7. Summary and recommendations
echo "<h3>7. Summary & Recommendations:</h3>";

if ($total == 0) {
    echo "<div style='background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb;'>";
    echo "❌ <strong>No notifications found!</strong><br>";
    echo "Your notifications table is empty. You need to create some notifications.<br><br>";
    echo "<strong>To create test notifications, run this SQL:</strong><br>";
    echo "<textarea style='width:100%; height:150px; font-family:monospace;'>";
    echo "INSERT INTO notifications (type, title, message, target_role, icon) VALUES
('announcement', 'Test Admin Notification', 'This is a test notification for admins', 'admin', 'megaphone'),
('announcement', 'Test Faculty Notification', 'This is a test notification for faculty', 'faculty', 'megaphone'),
('announcement', 'Test Student Notification', 'This is a test notification for students', 'student', 'megaphone'),
('announcement', 'Test All Users Notification', 'This notification is visible to everyone', 'all', 'megaphone'),
('alert', 'System Alert', 'This is an important system alert', 'all', 'alert-triangle');";
    echo "</textarea>";
    echo "</div>";
} else {
    echo "<div style='background: #d4edda; padding: 10px; border: 1px solid #c3e6cb;'>";
    echo "✅ You have {$total} notification(s) in the database.<br>";
    echo "✅ Tracking tables are in place.<br><br>";
    
    if ($reads == 0 && $deletes == 0) {
        echo "ℹ️ No read/delete tracking records yet (normal for new users).<br>";
    }
    
    echo "<strong>If notifications are still not showing:</strong><br>";
    echo "1. Check your user session - make sure you're logged in<br>";
    echo "2. Check user_id and user_type in your session<br>";
    echo "3. Clear your browser cache<br>";
    echo "4. Check browser console for JavaScript errors<br>";
    echo "</div>";
}

echo "<br><h3>✅ Diagnostic Complete!</h3>";
echo "<p>If you see issues above, fix them and refresh your notification pages.</p>";
?>

<style>
body { font-family: Arial, sans-serif; padding: 20px; }
h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 5px; }
h3 { color: #555; margin-top: 20px; }
table { border-collapse: collapse; margin: 10px 0; }
th { background: #007bff; color: white; padding: 8px; }
td { padding: 8px; }
code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
</style>