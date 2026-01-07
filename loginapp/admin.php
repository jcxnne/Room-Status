<?php
/**
 * ONE-TIME ADMIN ACCOUNT CREATOR
 * Run this script ONCE to create an admin account, then DELETE this file for security
 */

require_once "user_db.php";

// Admin account details - CHANGE THESE!
$admin_username = "admin";
$admin_email = "admin@gmail.com";
$admin_password = "admin123"; // Change this to a strong password
$admin_role = "admin";

// Hash the password
$hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);

// Check if admin already exists
$check_stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ? OR role = 'admin' LIMIT 1");
$check_stmt->bind_param("ss", $admin_username, $admin_email);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows > 0) {
    echo "Admin account already exists!";
} else {
    // Insert admin account
    $insert_stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    $insert_stmt->bind_param("ssss", $admin_username, $admin_email, $hashed_password, $admin_role);
    
    if ($insert_stmt->execute()) {
        echo "✅ Admin account created successfully!<br>";
        echo "Username: " . $admin_username . "<br>";
        echo "Email: " . $admin_email . "<br>";
        echo "Password: " . $admin_password . "<br><br>";
        echo "⚠️ IMPORTANT: Delete this file immediately after creating the admin account!";
    } else {
        echo "❌ Error creating admin account: " . $insert_stmt->error;
    }
    
    $insert_stmt->close();
}

$check_stmt->close();
$conn->close();
?>