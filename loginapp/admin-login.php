<?php
// Start session at the very beginning
session_start();

// Include database connection
require_once "user_db.php";

$message = "";

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // ===== LOGIN =====
    if (isset($_POST['login'])) {
        $username_or_email = trim($_POST['login_username'] ?? '');
        $password = trim($_POST['login_password'] ?? '');

        if ($username_or_email && $password) {
            // Check in ADMINS table
            $stmt = $conn->prepare("SELECT * FROM admins WHERE (username = ? OR email = ?) AND status = 'active' LIMIT 1");
            $stmt->bind_param("ss", $username_or_email, $username_or_email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 1) {
                $admin = $result->fetch_assoc();
                if (password_verify($password, $admin['password'])) {
                    // Set session variables
                    $_SESSION['username'] = $admin['username'];
                    $_SESSION['role'] = 'admin';
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['full_name'] = $admin['full_name'];
                    
                    // Update last login
                    $update_stmt = $conn->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
                    $update_stmt->bind_param("i", $admin['id']);
                    $update_stmt->execute();
                    $update_stmt->close();
                    
                    // Close statement and connection before redirect
                    $stmt->close();
                    $conn->close();
                    
                    // Redirect to admin dashboard
                    header("Location: admin-dashboard.php");
                    exit();
                } else {
                    $message = "Invalid username/email or password";
                }
            } else {
                $message = "Invalid username/email or password";
            }
            $stmt->close();
        } else {
            $message = "Please enter username/email and password";
        }
    }

    // ===== REGISTER =====
    if (isset($_POST['register'])) {
        $username = trim($_POST['register_username'] ?? '');
        $email = trim($_POST['register_email'] ?? '');
        $password = trim($_POST['register_password'] ?? '');
        $confirm_password = trim($_POST['register_confirm_password'] ?? '');
        $full_name = trim($_POST['register_full_name'] ?? '');

        if ($username && $email && $password && $confirm_password && $full_name) {
            if ($password !== $confirm_password) {
                $message = "Passwords do not match!";
            } else {
                // Check if username or email exists
                $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ? OR email = ? LIMIT 1");
                $stmt->bind_param("ss", $username, $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $message = "Username or email already taken!";
                } else {
                    // Insert new admin
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO admins (username, email, password, full_name, status) VALUES (?, ?, ?, ?, 'active')");
                    $stmt->bind_param("ssss", $username, $email, $hashed_password, $full_name);

                    if ($stmt->execute()) {
                        $message = "Admin account created successfully! Please login.";
                    } else {
                        $message = "Error creating account!";
                    }
                }
                $stmt->close();
            }
        } else {
            $message = "Please fill in all fields!";
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin - UniSched</title>
<link rel="stylesheet" href="login-signup.css">
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
<div class="container">
    <!-- LOGIN FORM -->
    <div class="form-box login">
        <form action="" method="POST">
            <h1>Admin Login</h1>
            <div class="input-box">
                <input type="text" name="login_username" placeholder="Username or Email" required>
                <i class='bx bxs-user'></i>
            </div>
            <div class="input-box">
                <input type="password" name="login_password" placeholder="Password" id="login-password" required>
                <i class='bx bxs-lock-alt' id="login-lock-icon"></i>
                <i class='bx bx-show' id="toggle-login-password" style="cursor:pointer; display:none;"></i>
            </div>
            <div class="forgot-link">
                <a href="#">Forgot Password?</a>
            </div>
            <button type="submit" name="login" class="btn">Login</button>
        </form>
    </div>

    <!-- REGISTER FORM -->
    <div class="form-box register">
        <form action="" method="POST">
            <h1>Admin Sign Up</h1>
            <div class="input-box">
                <input type="email" name="register_email" placeholder="Email" required>
                <i class='bx bxs-envelope'></i>
            </div>
            <div class="input-box">
                <input type="text" name="register_username" placeholder="Username" required>
                <i class='bx bxs-user'></i>
            </div>
            <div class="input-box">
                <input type="text" name="register_full_name" placeholder="Full Name" required>
                <i class='bx bxs-id-card'></i>
            </div>
            <div class="input-box">
                <input type="password" name="register_password" placeholder="Password" id="register-password" required>
                <i class='bx bxs-lock-alt' id="register-lock-icon"></i>
                <i class='bx bx-show' id="toggle-register-password" style="cursor:pointer; display:none;"></i>
            </div>
            <div class="input-box">
                <input type="password" name="register_confirm_password" placeholder="Confirm Password" id="register-confirm-password" required>
                <i class='bx bxs-lock-alt' id="confirm-lock-icon"></i>
                <i class='bx bx-show' id="toggle-register-confirm-password" style="cursor:pointer; display:none;"></i>
            </div>
            <button type="submit" name="register" class="btn">Sign Up</button>
        </form>
    </div>

    <!-- TOGGLE BOX -->
    <div class="toggle-box">
        <div class="toggle-panel toggle-left">
            <h1>Hello, Admin!</h1>
            <p>Don't have an account?</p>
            <button class="btn register-btn">Register</button>
        </div>
        <div class="toggle-panel toggle-right">
            <h1>Welcome Back!</h1>
            <p>Already have an account?</p>
            <button class="btn login-btn">Login</button>
        </div>
    </div>
</div>

<script src="login-signup.js"></script>

<script>
function setupPasswordField(inputId, lockIconId, toggleIconId) {
    const input = document.getElementById(inputId);
    const lockIcon = document.getElementById(lockIconId);
    const toggleIcon = document.getElementById(toggleIconId);

    // Show eye icon when user starts typing
    input.addEventListener('input', () => {
        if (input.value.length > 0) {
            lockIcon.style.display = 'none';
            toggleIcon.style.display = 'block';
        } else {
            lockIcon.style.display = 'block';
            toggleIcon.style.display = 'none';
        }
    });

    // Toggle password visibility
    toggleIcon.addEventListener('click', () => {
        if (input.type === 'password') {
            input.type = 'text';
            toggleIcon.classList.replace('bx-show', 'bx-hide');
        } else {
            input.type = 'password';
            toggleIcon.classList.replace('bx-hide', 'bx-show');
        }
    });
}

// Apply to all password fields
setupPasswordField('login-password', 'login-lock-icon', 'toggle-login-password');
setupPasswordField('register-password', 'register-lock-icon', 'toggle-register-password');
setupPasswordField('register-confirm-password', 'confirm-lock-icon', 'toggle-register-confirm-password');
</script>

<?php
if (!empty($message)) {
    echo "<script>alert('" . addslashes($message) . "');</script>";
}
?>
</body>
</html>