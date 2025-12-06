<?php
/**
 * Save Enrollment Module
 * Handles enrollment finalization with database transaction
 */

require_once __DIR__ . '/../config.php';

/**
 * Save enrollment with transaction
 * @param string $student_id
 * @param array $selected_subjects
 * @param string $semester
 * @return array ['success' => bool, 'message' => string]
 */
function saveEnrollment($student_id, $selected_subjects, $semester) {
    $pdo = getDBConnection();
    
    try {
        // Check for duplicate enrollment in same semester
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments 
                              WHERE student_id = ? AND semester = ?");
        $stmt->execute([$student_id, $semester]);
        if ($stmt->fetchColumn() > 0) {
            return [
                'success' => false,
                'message' => 'You are already enrolled for this semester. Cannot create duplicate enrollment.'
            ];
        }
        
        // Validate minimum units
        $total_units = 0;
        foreach ($selected_subjects as $subject) {
            $total_units += $subject['units'];
        }
        
        // Check minimum units (unless graduating or special case)
        if ($total_units < MIN_UNITS_PER_SEMESTER) {
            // Check if student is graduating (year 4 or higher)
            $stmt = $pdo->prepare("SELECT year_level FROM students WHERE student_id = ?");
            $stmt->execute([$student_id]);
            $student = $stmt->fetch();
            
            if ($student && $student['year_level'] < 4) {
                return [
                    'success' => false,
                    'message' => "Unit quantity is below the minimum allowed ({$total_units} < " . MIN_UNITS_PER_SEMESTER . ")."
                ];
            }
        }
        
        // Begin transaction
        $pdo->beginTransaction();
        
        // Insert into enrollments table
        $stmt = $pdo->prepare("INSERT INTO enrollments (student_id, semester, date_submitted, status) 
                               VALUES (?, ?, NOW(), 'Enrolled')");
        $stmt->execute([$student_id, $semester]);
        $enrollment_id = $pdo->lastInsertId();
        
        // Insert all subjects into enrollment_subjects
        $stmt = $pdo->prepare("INSERT INTO enrollment_subjects (enrollment_id, subject_code, section_id, units) 
                               VALUES (?, ?, ?, ?)");
        
        foreach ($selected_subjects as $subject) {
            $stmt->execute([
                $enrollment_id,
                $subject['subject_code'],
                $subject['section_id'],
                $subject['units']
            ]);
        }
        
        // Update student enrollment status
        $stmt = $pdo->prepare("UPDATE students SET enrollment_status = 'Enrolled' WHERE student_id = ?");
        $stmt->execute([$student_id]);
        
        // Commit transaction
        $pdo->commit();
        
        return [
            'success' => true,
            'message' => 'Enrollment Success',
            'enrollment_id' => $enrollment_id
        ];
        
    } catch (PDOException $e) {
        // Rollback on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        error_log("Enrollment error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred during enrollment. Please try again.'
        ];
    }
}

