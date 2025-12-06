<?php
/**
 * Logout handler
 */
require_once 'config.php';
startSession();

session_destroy();
header('Location: login.php');
exit;

