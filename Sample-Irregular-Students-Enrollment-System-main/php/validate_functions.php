<?php
/**
 * Validation Engine Functions
 * Contains all validation logic functions
 */

require_once __DIR__ . '/../config.php';

/**
 * Validate adding a subject to enrollment
 * @param string $student_id
 * @param string $subject_code
 * @param int $section_id
 * @return array ['valid' => bool, 'message' => string]
 */
function validateSubjectAddition($student_id, $subject_code, $section_id) {
    $pdo = getDBConnection();
    
    try {
        // Get student info
        $stmt = $pdo->prepare("SELECT program, year_level FROM students WHERE student_id = ?");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch();
        
        if (!$student) {
            return ['valid' => false, 'message' => 'Student not found.'];
        }
        
        // Get subject info
        $stmt = $pdo->prepare("SELECT * FROM subjects WHERE subject_code = ?");
        $stmt->execute([$subject_code]);
        $subject = $stmt->fetch();
        
        if (!$subject) {
            return ['valid' => false, 'message' => 'Subject not found.'];
        }
        
        // Get section info
        $stmt = $pdo->prepare("SELECT * FROM sections WHERE section_id = ? AND subject_code = ?");
        $stmt->execute([$section_id, $subject_code]);
        $section = $stmt->fetch();
        
        if (!$section) {
            return ['valid' => false, 'message' => 'Section not found.'];
        }
        
        // Get current selected subjects from session
        startSession();
        $selected_subjects = $_SESSION['selected_subjects'] ?? [];
        
        // 1. Already Taken Check
        $already_taken = checkAlreadyTaken($pdo, $student_id, $subject_code);
        if ($already_taken) {
            return ['valid' => false, 'message' => 'You have already taken this subject.'];
        }
        
        // 2. Prerequisite Check
        $prereq_result = checkPrerequisites($pdo, $student_id, $subject_code);
        if (!$prereq_result['valid']) {
            return ['valid' => false, 'message' => $prereq_result['message']];
        }
        
        // 2.5. Corequisite Check (informational - doesn't block, but returns info)
        $coreq_result = checkCorequisites($pdo, $student_id, $subject_code, $selected_subjects);
        
        // 3. Schedule Conflict Check
        $conflict_result = checkScheduleConflict($pdo, $section, $selected_subjects);
        if (!$conflict_result['valid']) {
            return ['valid' => false, 'message' => $conflict_result['message']];
        }
        
        // 4. Section Capacity Check
        $capacity_result = checkSectionCapacity($pdo, $section_id, CURRENT_SEMESTER);
        if (!$capacity_result['valid']) {
            return ['valid' => false, 'message' => $capacity_result['message']];
        }
        
        // 5. Unit Limits Check
        $unit_result = checkUnitLimits($pdo, $student_id, $selected_subjects, $subject['units']);
        if (!$unit_result['valid']) {
            return ['valid' => false, 'message' => $unit_result['message']];
        }
        
        // 6. Check if already in selected list
        foreach ($selected_subjects as $selected) {
            if ($selected['subject_code'] === $subject_code) {
                return ['valid' => false, 'message' => 'Subject already in your selection.'];
            }
        }
        
        $result = ['valid' => true, 'message' => 'Subject can be added.'];
        
        // Add corequisite info if found
        if (!empty($coreq_result['corequisites'])) {
            $result['has_corequisites'] = true;
            $result['corequisites'] = $coreq_result['corequisites'];
        }
        
        return $result;
        
    } catch (PDOException $e) {
        error_log("Validation error: " . $e->getMessage());
        return ['valid' => false, 'message' => 'An error occurred during validation.'];
    }
}

/**
 * Check if subject is already taken
 * @param PDO $pdo
 * @param string $student_id
 * @param string $subject_code
 * @return bool
 */
function checkAlreadyTaken($pdo, $student_id, $subject_code) {
    // Check in grades table (passed subjects)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM grades 
                          WHERE student_id = ? AND subject_code = ? AND passed = 1");
    $stmt->execute([$student_id, $subject_code]);
    if ($stmt->fetchColumn() > 0) {
        return true;
    }
    
    // Check in enrollment_subjects (currently enrolled in any semester)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollment_subjects es
                          JOIN enrollments e ON es.enrollment_id = e.enrollment_id
                          WHERE e.student_id = ? AND es.subject_code = ?");
    $stmt->execute([$student_id, $subject_code]);
    if ($stmt->fetchColumn() > 0) {
        return true;
    }
    
    // Check if currently enrolled in same subject in current semester
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollment_subjects es
                          JOIN enrollments e ON es.enrollment_id = e.enrollment_id
                          WHERE e.student_id = ? AND e.semester = ? AND es.subject_code = ?");
    $stmt->execute([$student_id, CURRENT_SEMESTER, $subject_code]);
    if ($stmt->fetchColumn() > 0) {
        return true;
    }
    
    return false;
}

/**
 * Check prerequisites
 * @param PDO $pdo
 * @param string $student_id
 * @param string $subject_code
 * @return array
 */
function checkPrerequisites($pdo, $student_id, $subject_code) {
    // Get all prerequisites for this subject
    $stmt = $pdo->prepare("SELECT prerequisite_code FROM prerequisites WHERE subject_code = ?");
    $stmt->execute([$subject_code]);
    $prerequisites = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($prerequisites)) {
        return ['valid' => true, 'message' => ''];
    }
    
    // Get subject names for better error messages
    $stmt = $pdo->prepare("SELECT subject_code, subject_name FROM subjects WHERE subject_code = ?");
    
    // Check if student has passed all prerequisites
    $missing_prereqs = [];
    foreach ($prerequisites as $prereq_code) {
        $stmt->execute([$prereq_code]);
        $prereq_subject = $stmt->fetch();
        $prereq_name = $prereq_subject ? $prereq_subject['subject_name'] : $prereq_code;
        
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM grades 
                              WHERE student_id = ? AND subject_code = ? AND passed = 1");
        $check_stmt->execute([$student_id, $prereq_code]);
        
        if ($check_stmt->fetchColumn() == 0) {
            $missing_prereqs[] = $prereq_code . ' (' . $prereq_name . ')';
        }
    }
    
    if (!empty($missing_prereqs)) {
        if (count($missing_prereqs) == 1) {
            return ['valid' => false, 'message' => 'Pre-requisite not completed: ' . $missing_prereqs[0] . '.'];
        } else {
            return ['valid' => false, 'message' => 'Pre-requisites not completed: ' . implode(', ', $missing_prereqs) . '.'];
        }
    }
    
    return ['valid' => true, 'message' => ''];
}

/**
 * Check schedule conflict
 * @param PDO $pdo
 * @param array $new_section
 * @param array $selected_subjects
 * @return array
 */
function checkScheduleConflict($pdo, $new_section, $selected_subjects) {
    if (empty($selected_subjects)) {
        return ['valid' => true, 'message' => ''];
    }
    
    foreach ($selected_subjects as $selected) {
        // Ensure section_id exists
        if (!isset($selected['section_id']) || empty($selected['section_id'])) {
            continue;
        }
        
        // Get section details for selected subject
        $stmt = $pdo->prepare("SELECT * FROM sections WHERE section_id = ?");
        $stmt->execute([$selected['section_id']]);
        $existing_section = $stmt->fetch();
        
        if (!$existing_section) {
            continue;
        }
        
        // Check if same day
        if ($new_section['day'] === $existing_section['day']) {
            // Check time overlap
            $new_start = strtotime($new_section['time_start']);
            $new_end = strtotime($new_section['time_end']);
            $existing_start = strtotime($existing_section['time_start']);
            $existing_end = strtotime($existing_section['time_end']);
            
            // Check for overlap
            if (($new_start >= $existing_start && $new_start < $existing_end) ||
                ($new_end > $existing_start && $new_end <= $existing_end) ||
                ($new_start <= $existing_start && $new_end >= $existing_end)) {
                return [
                    'valid' => false, 
                    'message' => "Schedule conflict detected with {$selected['subject_code']}."
                ];
            }
        }
    }
    
    return ['valid' => true, 'message' => ''];
}

/**
 * Check unit limits
 * @param PDO $pdo
 * @param string $student_id
 * @param array $selected_subjects
 * @param int $new_subject_units
 * @return array
 */
function checkUnitLimits($pdo, $student_id, $selected_subjects, $new_subject_units) {
    // Calculate current total units
    $current_units = 0;
    if (!empty($selected_subjects)) {
        foreach ($selected_subjects as $subject) {
            if (isset($subject['units'])) {
                $current_units += intval($subject['units']);
            }
        }
    }
    
    $total_units = $current_units + intval($new_subject_units);
    
    // Check maximum limit
    if ($total_units > MAX_UNITS_PER_SEMESTER) {
        return [
            'valid' => false, 
            'message' => 'Maximum unit limit reached.'
        ];
    }
    
    return ['valid' => true, 'message' => ''];
}

/**
 * Check section capacity
 * @param PDO $pdo
 * @param int $section_id
 * @param string $semester
 * @return array
 */
function checkSectionCapacity($pdo, $section_id, $semester) {
    // Get section capacity
    $stmt = $pdo->prepare("SELECT capacity FROM sections WHERE section_id = ?");
    $stmt->execute([$section_id]);
    $section = $stmt->fetch();
    
    if (!$section) {
        return ['valid' => false, 'message' => 'Section not found.'];
    }
    
    $capacity = intval($section['capacity']);
    
    // Count enrolled students in this section for current semester
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT es.id) as enrolled_count
                          FROM enrollment_subjects es
                          JOIN enrollments e ON es.enrollment_id = e.enrollment_id
                          WHERE es.section_id = ? AND e.semester = ?");
    $stmt->execute([$section_id, $semester]);
    $result = $stmt->fetch();
    $enrolled_count = intval($result['enrolled_count']);
    
    if ($enrolled_count >= $capacity) {
        return [
            'valid' => false,
            'message' => 'Section is full. Capacity: ' . $capacity . ', Enrolled: ' . $enrolled_count . '.'
        ];
    }
    
    return ['valid' => true, 'message' => ''];
}

/**
 * Check corequisites
 * @param PDO $pdo
 * @param string $student_id
 * @param string $subject_code
 * @param array $selected_subjects
 * @return array ['corequisites' => array]
 */
function checkCorequisites($pdo, $student_id, $subject_code, $selected_subjects) {
    // Get all corequisites for this subject
    $stmt = $pdo->prepare("SELECT coreq_code FROM corequisites WHERE subject_code = ?");
    $stmt->execute([$subject_code]);
    $coreq_codes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($coreq_codes)) {
        return ['corequisites' => []];
    }
    
    $coreq_info = [];
    
    foreach ($coreq_codes as $coreq_code) {
        // Check if already taken
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM grades 
                              WHERE student_id = ? AND subject_code = ? AND passed = 1");
        $stmt->execute([$student_id, $coreq_code]);
        
        if ($stmt->fetchColumn() > 0) {
            continue; // Already passed, no need to add
        }
        
        // Check if already in selected subjects
        $already_selected = false;
        foreach ($selected_subjects as $selected) {
            if ($selected['subject_code'] === $coreq_code) {
                $already_selected = true;
                break;
            }
        }
        
        if (!$already_selected) {
            // Get subject info
            $stmt = $pdo->prepare("SELECT subject_code, subject_name, units FROM subjects WHERE subject_code = ?");
            $stmt->execute([$coreq_code]);
            $coreq_subject = $stmt->fetch();
            
            if ($coreq_subject) {
                // Get first available section
                $stmt = $pdo->prepare("SELECT section_id, day, time_start, time_end, room 
                                      FROM sections 
                                      WHERE subject_code = ? 
                                      ORDER BY day, time_start 
                                      LIMIT 1");
                $stmt->execute([$coreq_code]);
                $section = $stmt->fetch();
                
                if ($section) {
                    $coreq_info[] = [
                        'subject_code' => $coreq_subject['subject_code'],
                        'subject_name' => $coreq_subject['subject_name'],
                        'units' => $coreq_subject['units'],
                        'section_id' => $section['section_id'],
                        'section_day' => $section['day'],
                        'section_time_start' => $section['time_start'],
                        'section_time_end' => $section['time_end'],
                        'section_room' => $section['room']
                    ];
                }
            }
        }
    }
    
    return ['corequisites' => $coreq_info];
}

