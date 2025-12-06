<?php
/**
 * Login Page
 * PLM Irregular Student Enrollment System
 */
require_once 'config.php';
startSession();

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = trim($_POST['student_id'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($student_id) || empty($password)) {
        $error = 'Please enter both Student ID and Password.';
    } else {
        require_once 'php/auth.php';
        $result = authenticateStudent($student_id, $password);
        
        if ($result['success']) {
            $_SESSION['student_id'] = $student_id;
            $_SESSION['logged_in'] = true;
            $_SESSION['full_name'] = $result['full_name'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PLM Irregular Student Enrollment System</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="login-page">
    <!-- PLM Header -->
    <header class="plm-header">
        <div class="plm-header-content">
            <div class="plm-logo-section">
                <?php if (file_exists('images/plm-logo.png')): ?>
                    <img src="images/plm-logo.png" alt="PLM Logo" class="plm-logo">
                <?php elseif (file_exists('images/plm-logo.jpg')): ?>
                    <img src="images/plm-logo.jpg" alt="PLM Logo" class="plm-logo">
                <?php elseif (file_exists('images/plm-logo.svg')): ?>
                    <img src="images/plm-logo.svg" alt="PLM Logo" class="plm-logo">
                <?php endif; ?>
                <div class="plm-title">
                    <h1 class="plm-main-title">PAMANTASAN NG LUNGSOD NG MAYNILA</h1>
                    <p class="plm-subtitle">University of the City of Manila</p>
                </div>
            </div>
        </div>
        <div class="plm-header-line"></div>
    </header>

    <div class="container">
        <div class="login-box">
            <div class="login-logo">
                <?php if (file_exists('images/plm-logo.png')): ?>
                    <img src="images/plm-logo.png" alt="PLM Logo">
                <?php elseif (file_exists('images/plm-logo.jpg')): ?>
                    <img src="images/plm-logo.jpg" alt="PLM Logo">
                <?php elseif (file_exists('images/plm-logo.svg')): ?>
                    <img src="images/plm-logo.svg" alt="PLM Logo">
                <?php endif; ?>
            </div>
            <h2 class="login-title">Irregular Student Enrollment System</h2>
            <h3 class="login-subtitle">Student Login</h3>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo sanitize($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="login.php">
                <div class="form-group">
                    <label for="student_id">Student ID:</label>
                    <input type="text" id="student_id" name="student_id" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Login</button>
            </form>
            
            <p class="note">Note: This system is only for Irregular students.</p>
        </div>
    </div>
</body>
</html>

