<?php
$host = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'tiksumadb';

$conn = new mysqli($host, $dbUser, $dbPass, $dbName);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// ✅ Add location column if not exists
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS location VARCHAR(100)");

session_start();

function isLoggedIn() {
    return isset($_SESSION['username']);
}

function handleLogin($username, $password) {
    global $conn;

    // ✅ Login for users, fetch role from DB
    $stmt = $conn->prepare("SELECT username, password, user_type FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($dbUsername, $dbPassword, $user_type);
        $stmt->fetch();

        $passwordValid = false;
        if (password_verify($password, $dbPassword)) {
            $passwordValid = true;
        } elseif ($password === $dbPassword) {
            // Plain text password, hash it for future logins
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
            $updateStmt->bind_param("ss", $hashedPassword, $username);
            $updateStmt->execute();
            $updateStmt->close();
            $passwordValid = true;
        }

        if ($passwordValid) {
            $_SESSION['username'] = $dbUsername;
            $_SESSION['user_type'] = $user_type;

            if ($user_type === 'Admin') {
                $_SESSION['location'] = 'it-dashboard.php';
                echo "<html><head>
                      <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                      </head><body>
                      <script>
                          Swal.fire({
                              title: 'Welcome IT Staff!',
                              text: 'You are successfully logged in.',
                              icon: 'success',
                              showConfirmButton: false
                          });
                          setTimeout(() => {
                              window.location.href = 'it-dashboard.php';
                          }, 2000);
                      </script>
                      </body></html>";
                exit;
            } elseif ($user_type === 'User') {
                $_SESSION['location'] = 'my-ticket.php';
                echo "<html><head>
                      <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                      </head><body>
                      <script>
                          Swal.fire({
                              title: 'Welcome Clarendon Staff!',
                              text: 'You are successfully logged in.',
                              icon: 'success',
                              showConfirmButton: false
                          });
                          setTimeout(() => {
                              window.location.href = 'clarc-my-ticket.php';
                          }, 2000);
                      </script>
                      </body></html>";
                exit;
            } else {
                echo "<script>alert('Invalid user type.');</script>";
            }
        } else {
            echo "<script>alert('Invalid password.');</script>";
        }
    } else {
        echo "<script>alert('User not found.');</script>";
    }

    $stmt->close();
}

function handleLogout() {
    session_destroy();
    header("Location: index.php");
}

function handleReservation($username, $email, $password, $confirmPassword) {
    global $conn;

    if ($password === $confirmPassword) {
        if ($username === 'admin') {
            echo "<script>alert('Cannot register as admin. This user already exists.');</script>";
            return;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT); // Hash the password
        $user_type = 'User'; // Default to User for new registrations
        $location = 'clarc-my-ticket.php'; // Default location for User

        $stmt = $conn->prepare("INSERT INTO users (username, email, password, user_type, location) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $username, $email, $hashedPassword, $user_type, $location);

        if ($stmt->execute()) {
            $_SESSION['username'] = $username;
            $_SESSION['user_type'] = $user_type;
            $_SESSION['location'] = $location;

            header("Location: $location");
            exit;
        } else {
            echo "<script>alert('Registration failed.');</script>";
        }

        $stmt->close();
    } else {
        echo "<script>alert('Passwords do not match.');</script>";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['login'])) {
        handleLogin($_POST['username'], $_POST['password']);
    } elseif (isset($_POST['logout'])) {
        handleLogout();
    } elseif (isset($_POST['register'])) {
        handleReservation(
            $_POST['signupUsername'],
            $_POST['signupEmail'],
            $_POST['signupPassword'],
            $_POST['signupConfirmPassword']
        );
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TIKSUMA Login</title>
    <link rel="stylesheet" href="./src/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" type= "image/x-icon" href="./png/logo-favicon.ico" />

</head>
<body>
<div class="login-container">
    <div class="login-card">
        <img src="./png/logo.png" class="logo-img" alt="Logo">
        <h2>TIKSUMA</h2>
        <p class="subtext" id="formTitle">LOG IN</p>

        <!-- Login Form -->
        <form method="POST" id="loginForm">
            <div class="input-group">
                <input type="text" name="username" placeholder="Username" required>
                <span class="icon"><i class="fas fa-envelope"></i></span>
            </div>
            <div class="input-group">
                <input type="password" name="password" placeholder="Password" required>
                <span class="icon"><i class="fas fa-lock"></i></span>
            </div>
            <button type="submit" name="login" class="login-btn">LOG IN</button>
            <p class="register-link">Don't have an account? <a href="#" onclick="toggleForms()">REGISTER</a></p>
        </form>

        <!-- Register Form -->
        <form method="POST" id="registerForm" style="display:none;">
            <div class="input-group">
                <input type="text" name="signupUsername" placeholder="Username" required>
                <span class="icon"><i class="fas fa-user"></i></span>
            </div>
            <div class="input-group">
                <input type="email" name="signupEmail" placeholder="Email" required>
                <span class="icon"><i class="fas fa-envelope"></i></span>
            </div>
            <div class="input-group">
                <input type="password" name="signupPassword" placeholder="Password" required>
                <span class="icon"><i class="fas fa-lock"></i></span>
            </div>
            <div class="input-group">
                <input type="password" name="signupConfirmPassword" placeholder="Confirm Password" required>
                <span class="icon"><i class="fas fa-lock"></i></span>
            </div>
            <button type="submit" name="register" class="login-btn">CREATE ACCOUNT</button>
            <p class="register-link">Already have an account? <a href="#" onclick="toggleForms()">LOG IN</a></p>
        </form>

        <!-- Google Login -->
        <div class="google-login">
            <a href="google-login.php" class="google-btn">
                <img src="./png/google-icon.webp" alt="Google" style="width: 20px; vertical-align: middle; margin-right: 8px;">
                GOOGLE
            </a>
        </div>
    </div>
</div>

<script src="./src/index.js"></script>
<script>
    function toggleForms() {
        const loginForm = document.getElementById("loginForm");
        const registerForm = document.getElementById("registerForm");
        loginForm.style.display = loginForm.style.display === "none" ? "block" : "none";
        registerForm.style.display = registerForm.style.display === "none" ? "block" : "none";
    }


</script>

</body>
</html>