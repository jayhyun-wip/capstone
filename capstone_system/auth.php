<?php
/**
 * BayanTap – Authentication Guard
 * Include this at the top of every protected page.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config.php';

// Check login
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Session timeout check
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > SESSION_TIMEOUT) {
    session_unset();
    session_destroy();
    header('Location: login.php?timeout=1');
    exit;
}

// Refresh session timestamp on activity
$_SESSION['login_time'] = time();

// Convenience variable
$currentUser = [
    'id'        => $_SESSION['user_id'],
    'username'  => $_SESSION['username'],
    'full_name' => $_SESSION['full_name'],
    'role'      => $_SESSION['role'],
];
