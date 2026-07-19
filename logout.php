<?php
session_start();

$_SESSION = array();

if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

session_destroy();

if (isset($_COOKIE['user_email'])) {
    setcookie('user_email', '', time() - 3600, '/');
}

if (isset($_COOKIE['user_password'])) {
    setcookie('user_password', '', time() - 3600, '/');
}

header("Location: index.php?logout=success");
exit();