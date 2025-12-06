<?php
/**
 * Remove Subject from Selection
 */
require_once __DIR__ . '/../config.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$subject_code = trim($_POST['subject_code'] ?? '');
$section_id = intval($_POST['section_id'] ?? 0);

if (empty($subject_code) || $section_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
    exit;
}

startSession();

if (!isset($_SESSION['selected_subjects'])) {
    echo json_encode(['success' => false, 'message' => 'No subjects selected.']);
    exit;
}

// Remove from selection
$_SESSION['selected_subjects'] = array_filter($_SESSION['selected_subjects'], function($item) use ($subject_code, $section_id) {
    return !($item['subject_code'] === $subject_code && $item['section_id'] == $section_id);
});

// Re-index array
$_SESSION['selected_subjects'] = array_values($_SESSION['selected_subjects']);

echo json_encode(['success' => true, 'message' => 'Subject removed successfully.']);

