<?php
require_once "database.php";
session_start();
$message = "";

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // ===== LOGIN =====
    if (isset($_POST['login'])) {
        $username_or_email = trim($_POST['login_username'] ?? '');
        $password = trim($_POST['login_password'] ?? '');

        if ($username_or_email && $password) {
            // Check username OR email
            $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ? OR email = ? LIMIT 1");
            $stmt->bind_param("ss", $username_or_email, $username_or_email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    $_SESSION['username'] = $user['username'];
                    header("Location: adashboard.php");
                    exit;
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

        if ($username && $email && $password && $confirm_password) {
            if ($password !== $confirm_password) {
                $message = "Passwords do not match!";
            } else {
                // Check if username or email exists
                $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ? OR email = ? LIMIT 1");
                $stmt->bind_param("ss", $username, $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $message = "Username or email already taken!";
                } else {
                    // Insert new user
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO admin (username, email, password) VALUES (?, ?, ?)");
                    $stmt->bind_param("sss", $username, $email, $hashed_password);

                    if ($stmt->execute()) {
                        $message = "Account created successfully! Your user ID is " . $stmt->insert_id;
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
<title>UniSched</title>
<link rel="stylesheet" href="client/styles/login.css">
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
<div class="container">
    <!-- LOGIN FORM -->
    <div class="form-box login">
        <form action="#" method="POST">
            <h1>Login</h1>
            <div class="input-box">
                <input type="text" name="login_username" placeholder="Username or Email" required>
                <i class='bx bxs-user'></i>
            </div>
            <div class="input-box">
                <input type="password" name="login_password" placeholder="Password" id="login-password" required>
                <i class='bx bxs-lock-alt'></i>
                <i class='bx bx-show' id="toggle-login-password" style="cursor:pointer;"></i>
            </div>
            <div class="forgot-link">
                <a href="#">Forgot Password?</a>
            </div>
            <button type="submit" name="login" class="btn">Login</button>
        </form>
    </div>

    <!-- REGISTER FORM -->
    <div class="form-box register">
        <form action="#" method="POST">
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
                <i class='bx bxs-lock-alt'></i>
                <i class='bx bx-show' id="toggle-register-password" style="cursor:pointer;"></i>
            </div>
            <div class="input-box">
                <input type="password" name="register_confirm_password" placeholder="Confirm Password" id="register-confirm-password" required>
                <i class='bx bxs-lock-alt'></i>
                <i class='bx bx-show' id="toggle-register-confirm-password" style="cursor:pointer;"></i>
            </div>
            <button type="submit" name="register" class="btn">Sign Up</button>
        </form>
    </div>

    <!-- TOGGLE BOX -->
    <div class="toggle-box">
        <div class="toggle-panel toggle-left">
            <h1>Welcome, Admin!</h1>
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


<script src="client/login.js"></script>

<script> function togglePassword(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);

    icon.addEventListener('click', () => {
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('bx-show', 'bx-hide');
        } else {
            input.type = 'password';
            icon.classList.replace('bx-hide', 'bx-show');
        }
    });
}

// Apply toggle to all password fields
togglePassword('login-password', 'toggle-login-password');
togglePassword('register-password', 'toggle-register-password');
togglePassword('register-confirm-password', 'toggle-register-confirm-password');
</script>


<?php
if (!empty($message)) {
    echo "<script>alert('$message');</script>";
}
?>
</body>
</html>
