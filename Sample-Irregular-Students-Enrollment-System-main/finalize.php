<?php
/**
 * Finalize Enrollment
 * Processes enrollment finalization with transaction
 */
require_once 'config.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$student_id = getCurrentStudentId();
startSession();

$selected_subjects = $_SESSION['selected_subjects'] ?? [];

if (empty($selected_subjects)) {
    echo json_encode(['success' => false, 'message' => 'No subjects selected for enrollment.']);
    exit;
}

require_once 'php/save_enrollment.php';
$result = saveEnrollment($student_id, $selected_subjects, CURRENT_SEMESTER);

if ($result['success']) {
    // Clear selection from session
    $_SESSION['selected_subjects'] = [];
}

echo json_encode($result);

