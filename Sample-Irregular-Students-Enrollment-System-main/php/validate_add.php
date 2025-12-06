<?php
/**
 * Validation Engine API Endpoint
 * Validates subject addition via AJAX
 */

// Start output buffering to catch any unwanted output
ob_start();

require_once __DIR__ . '/../config.php';

// Clear any output that might have been generated
ob_clean();

// Set JSON header
header('Content-Type: application/json');

try {
    requireLogin();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['valid' => false, 'message' => 'Invalid request method.']);
        exit;
    }
    
    $student_id = getCurrentStudentId();
    if (!$student_id) {
        echo json_encode(['valid' => false, 'message' => 'Not logged in.']);
        exit;
    }
    
    $subject_code = trim($_POST['subject_code'] ?? '');
    $section_id = intval($_POST['section_id'] ?? 0);
    
    if (empty($subject_code) || $section_id <= 0) {
        echo json_encode(['valid' => false, 'message' => 'Invalid parameters.']);
        exit;
    }
    
    require_once __DIR__ . '/validate_functions.php';
    
    $result = validateSubjectAddition($student_id, $subject_code, $section_id);
    echo json_encode($result);
    
} catch (Exception $e) {
    // Log detailed error server-side for debugging
    error_log("Validation endpoint error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    ob_clean(); // Clear any output
    // Return generic error message to client (no sensitive information)
    echo json_encode(['valid' => false, 'message' => 'An error occurred during validation. Please try again.']);
}
