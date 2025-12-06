<?php
/**
 * Load Available Subjects Module
 * Loads subjects available for enrollment
 */

require_once __DIR__ . '/../config.php';

/**
 * Load available subjects for a student
 * @param string $student_id
 * @param string $semester
 * @return array
 */
function loadAvailableSubjects($student_id, $semester) {
    $pdo = getDBConnection();
    
    try {
        // Get student's program
        $stmt = $pdo->prepare("SELECT program FROM students WHERE student_id = ?");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch();
        
        if (!$student) {
            return [];
        }
        
        // Get all subjects for the semester and program
        $stmt = $pdo->prepare("SELECT s.* FROM subjects s 
                              WHERE s.semester = ? AND s.program = ?
                              ORDER BY s.subject_code");
        $stmt->execute([$semester, $student['program']]);
        $subjects = $stmt->fetchAll();
        
        // Get cross-program prerequisite subjects (subjects from other programs that are prerequisites)
        // Find subjects that are prerequisites for subjects in student's program
        $stmt = $pdo->prepare("SELECT DISTINCT s.* 
                              FROM subjects s
                              INNER JOIN prerequisites p ON s.subject_code = p.prerequisite_code
                              INNER JOIN subjects target_subj ON p.subject_code = target_subj.subject_code
                              WHERE target_subj.program = ? 
                              AND target_subj.semester = ?
                              AND s.semester = ?
                              AND s.program != ?
                              ORDER BY s.subject_code");
        $stmt->execute([$student['program'], $semester, $semester, $student['program']]);
        $cross_program_subjects = $stmt->fetchAll();
        
        // Merge and remove duplicates
        $all_subjects = [];
        $subject_codes = [];
        
        foreach ($subjects as $subject) {
            $all_subjects[] = $subject;
            $subject_codes[] = $subject['subject_code'];
        }
        
        foreach ($cross_program_subjects as $subject) {
            if (!in_array($subject['subject_code'], $subject_codes)) {
                $subject['is_cross_program'] = true; // Mark as cross-program
                $all_subjects[] = $subject;
                $subject_codes[] = $subject['subject_code'];
            }
        }
        
        // Get sections for each subject
        foreach ($all_subjects as &$subject) {
            $stmt = $pdo->prepare("SELECT * FROM sections 
                                  WHERE subject_code = ? 
                                  ORDER BY day, time_start");
            $stmt->execute([$subject['subject_code']]);
            $subject['sections'] = $stmt->fetchAll();
        }
        
        return $all_subjects;
        
    } catch (PDOException $e) {
        error_log("Error loading subjects: " . $e->getMessage());
        return [];
    }
}

