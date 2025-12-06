<?php
/**
 * Entry point - Redirects to login or dashboard
 */
require_once 'config.php';
startSession();

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
} else {
    header('Location: login.php');
    exit;
}

