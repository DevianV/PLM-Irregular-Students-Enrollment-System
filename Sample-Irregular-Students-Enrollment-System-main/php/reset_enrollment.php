<?php
/**
 * Reset Enrollment Status
 * For testing purposes - resets a student's enrollment status
 */

require_once __DIR__ . '/../config.php';

// Only allow in development/testing - add password protection in production
$reset_password = $_POST['reset_password'] ?? '';
$expected_password = 'reset123'; // Change this in production

if ($reset_password !== $expected_password) {
    die('Access denied. Invalid reset password.');
}

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
    
    $pdo = getDBConnection();
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Delete enrollments for current semester
    $stmt = $pdo->prepare("DELETE e FROM enrollments e 
                          WHERE e.student_id = ? AND e.semester = ?");
    $stmt->execute([$student_id, CURRENT_SEMESTER]);
    
    // Update enrollment status
    $stmt = $pdo->prepare("UPDATE students SET enrollment_status = 'Not Enrolled' WHERE student_id = ?");
    $stmt->execute([$student_id]);
    
    // Clear session selection
    startSession();
    $_SESSION['selected_subjects'] = [];
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Enrollment status reset successfully. You can now enroll again.'
    ]);
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Reset enrollment error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while resetting enrollment.']);
}

