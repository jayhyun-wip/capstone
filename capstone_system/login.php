<?php
/**
 * BayanTap – Login Page
 */
session_start();
require_once 'config.php';

// Redirect if already logged in
if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

// ── Handle Login Form Submission ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        $pdo  = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :u LIMIT 1");
        $stmt->execute([':u' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];
            $_SESSION['login_time']= time();
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BayanTap – Login</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
</head>
<body class="login-page">

    <div class="login-wrapper">
        <!-- Left decorative panel -->
        <div class="login-panel">
            <div class="login-brand">
                <div class="login-logo">
                    <svg viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M30 5C30 5 10 22 10 36C10 47.046 19.402 56 30 56C40.598 56 50 47.046 50 36C50 22 30 5 30 5Z" fill="white" fill-opacity="0.9"/>
                        <path d="M30 18C30 18 18 28.5 18 36C18 42.627 23.373 48 30 48C36.627 48 42 42.627 42 36C42 28.5 30 18 30 18Z" fill="#1a5fa8" fill-opacity="0.5"/>
                    </svg>
                </div>
                <h1>BayanTap</h1>
                <p>Marcos Village Water District</p>
            </div>

            <div class="login-features">
                <div class="feature-item">
                    <span class="feature-icon">📊</span>
                    <span>Real-time billing dashboard</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon">🧾</span>
                    <span>Instant receipt generation</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon">🏘️</span>
                    <span>Household management</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon">🔒</span>
                    <span>Secure treasurer portal</span>
                </div>
            </div>

            <div class="login-wave">
                <svg viewBox="0 0 500 80" preserveAspectRatio="none">
                    <path d="M0,40 C150,80 350,0 500,40 L500,80 L0,80 Z" fill="rgba(255,255,255,0.08)"/>
                    <path d="M0,55 C200,15 300,65 500,55 L500,80 L0,80 Z" fill="rgba(255,255,255,0.05)"/>
                </svg>
            </div>
        </div>

        <!-- Right form panel -->
        <div class="login-form-panel">
            <div class="login-form-inner">
                <h2>Treasurer Portal</h2>
                <p class="login-subtitle">Sign in to manage billing and receipts</p>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <span>⚠️</span> <?= esc($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php" class="login-form">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <div class="input-wrapper">
                            <span class="input-icon">👤</span>
                            <input
                                type="text"
                                id="username"
                                name="username"
                                placeholder="Enter your username"
                                value="<?= esc($_POST['username'] ?? '') ?>"
                                autocomplete="username"
                                required
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <span class="input-icon">🔑</span>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Enter your password"
                                autocomplete="current-password"
                                required
                            >
                            <button type="button" class="toggle-password" onclick="togglePassword()">👁</button>
                        </div>
                    </div>

                    <button type="submit" class="btn-login">
                        Sign In to Portal
                    </button>
                </form>

                <div class="login-hint">
                    <small>Default credentials: <code>treasurer</code> / <code>password</code></small>
                </div>

                <div class="login-footer">
                    <p>BayanTap v1.0 &nbsp;·&nbsp; Marcos Village Water District</p>
                </div>
            </div>
        </div>
    </div>

    <script>
    function togglePassword() {
        const input = document.getElementById('password');
        input.type = input.type === 'password' ? 'text' : 'password';
    }
    </script>
</body>
</html>
