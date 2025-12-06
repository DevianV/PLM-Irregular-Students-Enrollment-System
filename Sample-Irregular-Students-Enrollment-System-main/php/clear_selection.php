<?php
/**
 * Clear Selection
 */
require_once __DIR__ . '/../config.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

startSession();
$_SESSION['selected_subjects'] = [];

echo json_encode(['success' => true, 'message' => 'Selection cleared successfully.']);

