<?php
session_start();

// Clear all session variables
$_SESSION = [];

// Destroy the session
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/', '', false, true);
}

session_destroy();

// Redirect to login
header('Location: login.php?logged_out=1', true, 302);
exit();
