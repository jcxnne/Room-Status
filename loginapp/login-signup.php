<?php
// Start session at the very beginning
session_start();

// Include database connection for user_db
require_once "user_db.php";

// Create connection to profile database
$profile_host = 'localhost';
$profile_dbname = 'profile';
$profile_username = 'root';
$profile_password = '';

try {
    $profile_pdo = new PDO("mysql:host=$profile_host;dbname=$profile_dbname", $profile_username, $profile_password);
    $profile_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Profile database connection failed: " . $e->getMessage());
}

$message = "";

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // ===== LOGIN =====
    if (isset($_POST['login'])) {
        $username_or_email = trim($_POST['login_username'] ?? '');
        $password = trim($_POST['login_password'] ?? '');

        if ($username_or_email && $password) {
            // Check username OR email
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
            $stmt->bind_param("ss", $username_or_email, $username_or_email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    // Set session variables
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['user_id'] = $user['id'];
                    
                    // Close statement and connection before redirect
                    $stmt->close();
                    $conn->close();
                    
                    // Redirect based on role - ADMIN FIRST
                    if ($user['role'] === 'admin') {
                        header("Location: admin-dashboard.php");
                        exit();
                    } elseif ($user['role'] === 'student') {
                        header("Location: sdashboard.php");
                        exit();
                    } elseif ($user['role'] === 'faculty') {
                        header("Location: fdashboard.php");
                        exit();
                    } else {
                        header("Location: dashboard.php");
                        exit();
                    }
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
        $role = trim($_POST['register_role'] ?? '');

        if ($username && $email && $password && $confirm_password && $role) {
            if ($password !== $confirm_password) {
                $message = "Passwords do not match!";
            } elseif (!in_array($role, ['student', 'faculty'])) {
                $message = "Invalid role selected!";
            } else {
                // Check if username or email exists in user_db
                $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
                $stmt->bind_param("ss", $username, $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $message = "Username or email already taken!";
                } else {
                    // Start transaction for both databases
                    $conn->begin_transaction();
                    
                    try {
                        // Insert into user_db (users table)
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
                        $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);
                        
                        if ($stmt->execute()) {
                            $user_id = $conn->insert_id; // Get the inserted user ID
                            
                            // Insert into profile database based on role
                            if ($role === 'student') {
                                $profile_stmt = $profile_pdo->prepare("INSERT INTO students (user_id, email, first_name, last_name) VALUES (?, ?, '', '')");
                                $profile_stmt->execute([$user_id, $email]);
                            } elseif ($role === 'faculty') {
                                $profile_stmt = $profile_pdo->prepare("INSERT INTO faculty (user_id, email, first_name, last_name) VALUES (?, ?, '', '')");
                                $profile_stmt->execute([$user_id, $email]);
                            }
                            
                            $conn->commit();
                            $message = "Account created successfully! Please login.";
                        } else {
                            throw new Exception("Error creating user account");
                        }
                        
                    } catch (Exception $e) {
                        $conn->rollback();
                        $message = "Error creating account: " . $e->getMessage();
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
<title>UniSched</title>
<link rel="stylesheet" href="login-signup.css">
<link rel="stylesheet" href="dashboard.css">
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
<div class="container">
    <!-- LOGIN FORM -->
    <div class="form-box login">
        <form action="" method="POST">
            <h1>Login</h1>
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
            <h1>Sign Up</h1>
            <div class="input-box">
                <input type="email" name="register_email" placeholder="Email" required>
                <i class='bx bxs-envelope'></i>
            </div>
            <div class="input-box">
                <input type="text" name="register_username" placeholder="Username" required>
                <i class='bx bxs-user'></i>
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
            <div class="input-box">
                <select name="register_role" required>
                    <option value="">Select Role</option>
                    <option value="student">Student</option>
                    <option value="faculty">Faculty</option>
                </select>
                <i class='bx bxs-id-card'></i>
            </div>
            <button type="submit" name="register" class="btn">Sign Up</button>
        </form>
    </div>

    <!-- TOGGLE BOX -->
    <div class="toggle-box">
        <div class="toggle-panel toggle-left">
            <h1>Hello, Welcome!</h1>
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