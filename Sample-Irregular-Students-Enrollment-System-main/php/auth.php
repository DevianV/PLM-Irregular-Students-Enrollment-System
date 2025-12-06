<?php
/**
 * Authentication Module
 * Handles student authentication and status validation
 */

require_once __DIR__ . '/../config.php';

/**
 * Authenticate student login
 * @param string $student_id
 * @param string $password
 * @return array ['success' => bool, 'message' => string, 'full_name' => string|null]
 */
function authenticateStudent($student_id, $password) {
    $pdo = getDBConnection();
    
    try {
        // Check user credentials
        $stmt = $pdo->prepare("SELECT u.password_hash, s.status, s.full_name 
                               FROM users u 
                               LEFT JOIN students s ON u.student_id = s.student_id 
                               WHERE u.student_id = ?");
        $stmt->execute([$student_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Invalid Student ID or Password.',
                'full_name' => null
            ];
        }
        
        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            return [
                'success' => false,
                'message' => 'Invalid Student ID or Password.',
                'full_name' => null
            ];
        }
        
        // Check if student is Regular
        if ($user['status'] === 'Regular') {
            return [
                'success' => false,
                'message' => 'Access denied. This system is only for Irregular Students.',
                'full_name' => null
            ];
        }
        
        // Check if student is Irregular
        if ($user['status'] !== 'Irregular') {
            return [
                'success' => false,
                'message' => 'Invalid student status.',
                'full_name' => null
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Login successful.',
            'full_name' => $user['full_name']
        ];
        
    } catch (PDOException $e) {
        error_log("Authentication error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred. Please try again later.',
            'full_name' => null
        ];
    }
}

