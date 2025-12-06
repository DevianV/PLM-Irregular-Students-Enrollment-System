<?php
/**
 * Add Subject to Selection
 * Adds a subject to the session-based selection
 */

// Start output buffering
ob_start();

require_once __DIR__ . '/../config.php';

// Clear any output
ob_clean();

header('Content-Type: application/json');

try {
    requireLogin();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
        exit;
    }
    
    $student_id = getCurrentStudentId();
    if (!$student_id) {
        echo json_encode(['success' => false, 'message' => 'Not logged in.']);
        exit;
    }
    
    $subject_code = trim($_POST['subject_code'] ?? '');
    $section_id = intval($_POST['section_id'] ?? 0);
    
    if (empty($subject_code) || $section_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
        exit;
    }
    
    $pdo = getDBConnection();
    // Get subject and section info
    $stmt = $pdo->prepare("SELECT s.*, sub.subject_name, sub.units 
                           FROM sections s 
                           JOIN subjects sub ON s.subject_code = sub.subject_code 
                           WHERE s.section_id = ? AND s.subject_code = ?");
    $stmt->execute([$section_id, $subject_code]);
    $section = $stmt->fetch();
    
    if (!$section) {
        echo json_encode(['success' => false, 'message' => 'Section not found.']);
        exit;
    }
    
    // Validate before adding
    require_once __DIR__ . '/validate_functions.php';
    $validation = validateSubjectAddition($student_id, $subject_code, $section_id);
    
    if (!$validation['valid']) {
        echo json_encode(['success' => false, 'message' => $validation['message']]);
        exit;
    }
    
    // Add to session
    startSession();
    if (!isset($_SESSION['selected_subjects'])) {
        $_SESSION['selected_subjects'] = [];
    }
    
    // Check if already exists
    foreach ($_SESSION['selected_subjects'] as $item) {
        if ($item['subject_code'] === $subject_code) {
            echo json_encode(['success' => false, 'message' => 'Subject already in selection.']);
            exit;
        }
    }
    
    // Add to selection
    $_SESSION['selected_subjects'][] = [
        'subject_code' => $subject_code,
        'subject_name' => $section['subject_name'],
        'section_id' => $section_id,
        'section_day' => $section['day'],
        'section_time_start' => $section['time_start'],
        'section_time_end' => $section['time_end'],
        'section_room' => $section['room'],
        'units' => $section['units']
    ];
    
    echo json_encode(['success' => true, 'message' => 'Subject added successfully.']);
    
} catch (PDOException $e) {
    // Log detailed error server-side for debugging
    error_log("Error adding subject (PDO): " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    // Return generic error message to client (no sensitive information)
    echo json_encode(['success' => false, 'message' => 'An error occurred while adding the subject. Please try again.']);
} catch (Exception $e) {
    // Log detailed error server-side for debugging
    error_log("Error adding subject: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    // Return generic error message to client (no sensitive information)
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}

