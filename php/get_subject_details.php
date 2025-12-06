<?php
/**
 * Get Subject Details
 * Returns subject information including prerequisites and corequisites
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
    
    $subject_code = trim($_POST['subject_code'] ?? '');
    
    if (empty($subject_code)) {
        echo json_encode(['success' => false, 'message' => 'Subject code is required.']);
        exit;
    }
    
    $pdo = getDBConnection();
    
    // Get subject info
    $stmt = $pdo->prepare("SELECT * FROM subjects WHERE subject_code = ?");
    $stmt->execute([$subject_code]);
    $subject = $stmt->fetch();
    
    if (!$subject) {
        echo json_encode(['success' => false, 'message' => 'Subject not found.']);
        exit;
    }
    
    // Get prerequisites
    $stmt = $pdo->prepare("SELECT p.prerequisite_code, s.subject_name as prerequisite_name
                          FROM prerequisites p
                          JOIN subjects s ON p.prerequisite_code = s.subject_code
                          WHERE p.subject_code = ?");
    $stmt->execute([$subject_code]);
    $prerequisites = $stmt->fetchAll();
    
    // Get corequisites
    $stmt = $pdo->prepare("SELECT c.coreq_code, s.subject_name as coreq_name
                          FROM corequisites c
                          JOIN subjects s ON c.coreq_code = s.subject_code
                          WHERE c.subject_code = ?");
    $stmt->execute([$subject_code]);
    $corequisites = $stmt->fetchAll();
    
    // Get sections
    $stmt = $pdo->prepare("SELECT * FROM sections WHERE subject_code = ? ORDER BY day, time_start");
    $stmt->execute([$subject_code]);
    $sections = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'subject' => $subject,
        'prerequisites' => $prerequisites,
        'corequisites' => $corequisites,
        'sections' => $sections
    ]);
    
} catch (Exception $e) {
    error_log("Subject details error: " . $e->getMessage());
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'An error occurred while loading subject details.']);
}

