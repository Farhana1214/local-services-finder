<?php
// database_connection.php handles session_start() automatically
include 'database_connection.php';
include 'config.php';
include 'helpers.php';

$error = '';
$success = '';
$debugInfo = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and sanitize input
    $username = sanitize_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Debug output
    $debugInfo .= "Username: " . htmlspecialchars($username) . "<br>";
    $debugInfo .= "Password entered: " . htmlspecialchars($password) . "<br>";
    
    // Validate input
    if (empty($username) || empty($password)) {
        $error = 'Username and password are required';
    } else {
        // Prepare statement to prevent SQL injection
        $query = "SELECT user_id, username, email, password, first_name, last_name, user_type, is_verified FROM users WHERE username = ? OR email = ? LIMIT 1";
        $user = getSingleResult($conn, $query, 'ss', [$username, $username]);
        
        $debugInfo .= "<hr><strong>Database Lookup:</strong><br>";
        if ($user) {
            $debugInfo .= "✓ User found<br>";
            $debugInfo .= "Stored password hash length: " . strlen($user['password']) . "<br>";
            $debugInfo .= "Stored password (first 30 chars): " . htmlspecialchars(substr($user['password'], 0, 30)) . "<br>";
            
            if (strlen($user['password']) < 10) {
                $debugInfo .= "<span style='color:red;'><strong>⚠️ ERROR: Password field appears EMPTY!</strong></span><br>";
            }
            
            $passwordMatch = verifyPassword($password, $user['password']);
            $debugInfo .= "Password verification result: " . ($passwordMatch ? "✓ MATCH" : "✗ NO MATCH") . "<br>";
        } else {
            $debugInfo .= "✗ User not found in database!<br>";
        }
        
        if ($user && verifyPassword($password, $user['password'])) {
            // Password verification successful
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['is_verified'] = $user['is_verified'];
            $_SESSION['login_time'] = time();
            
            // Redirect based on user type
            if ($user['user_type'] === 'service_provider') {
                header("Location: provider-dashboard.php");
            } else {
                header("Location: dashboard.php");
            }
            exit();
        } else {
            $error = 'Invalid username/email or password';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Service Finder (DEBUG)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .login-header h1 {
            font-weight: bold;
            margin: 0;
        }
        .login-form {
            padding: 2rem;
        }
        .debug-box {
            background: #f0f0f0;
            border: 2px solid #d00;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
            font-family: monospace;
            font-size: 12px;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🐛 Login - DEBUG Mode</h1>
        </div>
        
        <div class="login-form">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($debugInfo): ?>
                <div class="debug-box">
                    <strong style="color: #d00;">DEBUG OUTPUT:</strong><br>
                    <?php echo $debugInfo; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label for="username" class="form-label">Username or Email</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password" required>
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 btn-login">Login</button>
            </form>

            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center;">
                <p>Don't have an account? <a href="user_registeration.php">Register here</a></p>
                <p><small>This is DEBUG MODE. Password hashes will be shown for troubleshooting.</small></p>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordField = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                passwordField.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });
    </script>
</body>
</html>
