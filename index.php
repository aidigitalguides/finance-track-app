<?php
// index.php — entry point, redirect based on session
require_once 'config.php';
require_once 'includes/Database.php';
require_once 'includes/Auth.php';

Auth::startSession();

if (!empty($_SESSION['logged_in']) && !empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
