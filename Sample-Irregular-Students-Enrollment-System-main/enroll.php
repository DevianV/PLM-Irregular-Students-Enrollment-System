<?php
/**
 * Enrollment Page
 * Subject selection with real-time validation
 */
require_once 'config.php';
requireLogin();

$student_id = getCurrentStudentId();
$pdo = getDBConnection();

// Check if already enrolled
$stmt = $pdo->prepare("SELECT enrollment_status FROM students WHERE student_id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    session_destroy();
    header('Location: login.php');
    exit;
}

if ($student['enrollment_status'] === 'Enrolled') {
    header('Location: dashboard.php');
    exit;
}

// Load available subjects for current semester
require_once 'php/load_subjects.php';
$available_subjects = loadAvailableSubjects($student_id, CURRENT_SEMESTER);

// Get student's program
$stmt = $pdo->prepare("SELECT program FROM students WHERE student_id = ?");
$stmt->execute([$student_id]);
$student_info = $stmt->fetch();
$student_program = $student_info['program'];

// No need to filter - loadAvailableSubjects now includes cross-program prerequisites
$filtered_subjects = $available_subjects;

// Get selected subjects from session
startSession();
$selected_subjects = $_SESSION['selected_subjects'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enroll - PLM Enrollment System</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- PLM Header -->
    <header class="plm-header">
        <div class="plm-header-content">
            <div class="plm-logo-section">
                <?php if (file_exists('images/plm-logo.png')): ?>
                    <img src="images/plm-logo.png" alt="PLM Logo" class="plm-logo">
                <?php elseif (file_exists('images/plm-logo.jpg')): ?>
                    <img src="images/plm-logo.jpg" alt="PLM Logo" class="plm-logo">
                <?php elseif (file_exists('images/plm-logo.svg')): ?>
                    <img src="images/plm-logo.svg" alt="PLM Logo" class="plm-logo">
                <?php endif; ?>
                <div class="plm-title">
                    <h1 class="plm-main-title">PAMANTASAN NG LUNGSOD NG MAYNILA</h1>
                    <p class="plm-subtitle">University of the City of Manila</p>
                </div>
            </div>
            <div class="plm-header-actions">
                <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        </div>
        <div class="plm-header-line"></div>
    </header>

    <div class="container">
        <header class="dashboard-header">
            <h1>Subject Enrollment</h1>
        </header>
        
        <div class="enrollment-container" data-max-units="<?php echo MAX_UNITS_PER_SEMESTER; ?>">
            <div class="enrollment-header">
                <h2>Select Subjects for <?php echo CURRENT_SEMESTER; ?> Semester</h2>
                <p>Program: <?php echo sanitize($student_program); ?></p>
            </div>
            
            <!-- Selected Subjects -->
            <?php if (!empty($selected_subjects)): ?>
                <div class="selected-subjects">
                    <h3>Selected Subjects</h3>
                    <ul class="subject-list">
                        <?php 
                        $total_units = 0;
                        foreach ($selected_subjects as $item): 
                            $total_units += $item['units'];
                        ?>
                            <li class="subject-item">
                                <div class="subject-item-info">
                                    <strong><?php echo sanitize($item['subject_code']); ?></strong> - 
                                    <?php echo sanitize($item['subject_name']); ?>
                                    <br>
                                    <small>
                                        Section: <?php echo sanitize($item['section_day']); ?> 
                                        <?php echo date('g:i A', strtotime($item['section_time_start'])); ?> - 
                                        <?php echo date('g:i A', strtotime($item['section_time_end'])); ?> 
                                        (<?php echo sanitize($item['section_room']); ?>)
                                    </small>
                                    <br>
                                    <small>Units: <?php echo sanitize($item['units']); ?></small>
                                </div>
                                <div class="subject-item-actions">
                                    <button class="btn btn-danger" onclick="removeSubject('<?php echo sanitize($item['subject_code']); ?>', '<?php echo sanitize($item['section_id']); ?>')">Remove</button>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="total-units">
                        Total Units: <?php echo $total_units; ?> / <?php echo MAX_UNITS_PER_SEMESTER; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="selected-subjects">
                    <p class="no-data">No subjects selected yet.</p>
                </div>
            <?php endif; ?>
            
            <!-- Available Subjects -->
            <div class="available-subjects">
                <div class="subjects-header">
                    <h3>Available Subjects</h3>
                    <div class="search-filter-container">
                        <input type="text" id="subjectSearch" placeholder="Search subjects..." class="search-input" onkeyup="filterSubjects()">
                        <select id="yearFilter" class="filter-select" onchange="filterSubjects()">
                            <option value="">All Year Levels</option>
                            <option value="1">Year 1</option>
                            <option value="2">Year 2</option>
                            <option value="3">Year 3</option>
                            <option value="4">Year 4</option>
                        </select>
                        <select id="unitsFilter" class="filter-select" onchange="filterSubjects()">
                            <option value="">All Units</option>
                            <option value="3">3 Units</option>
                            <option value="4">4 Units</option>
                            <option value="5">5 Units</option>
                        </select>
                    </div>
                </div>
                <?php if (empty($filtered_subjects)): ?>
                    <p class="no-data">No available subjects for your program this semester.</p>
                <?php else: ?>
                    <?php foreach ($filtered_subjects as $subject): 
                        // Determine year level from subject code
                        $year_level = 1;
                        if (preg_match('/^[A-Z]+([0-9])/', $subject['subject_code'], $matches)) {
                            $first_digit = intval($matches[1]);
                            if ($first_digit == 2) $year_level = 2;
                            elseif ($first_digit == 3) $year_level = 3;
                            elseif ($first_digit == 4) $year_level = 4;
                        }
                    ?>
                        <div class="subject-card" data-subject-code="<?php echo strtolower($subject['subject_code']); ?>" 
                             data-subject-name="<?php echo strtolower($subject['subject_name']); ?>"
                             data-year-level="<?php echo $year_level; ?>"
                             data-units="<?php echo $subject['units']; ?>"
                             data-program="<?php echo isset($subject['is_cross_program']) && $subject['is_cross_program'] ? 'cross' : 'main'; ?>">
                            <div class="subject-card-header">
                                <div class="subject-card-title">
                                    <h4>
                                        <?php echo sanitize($subject['subject_code']); ?> - <?php echo sanitize($subject['subject_name']); ?>
                                        <?php if (isset($subject['is_cross_program']) && $subject['is_cross_program']): ?>
                                            <span class="cross-program-badge" title="Cross-program prerequisite">Cross-Program</span>
                                        <?php endif; ?>
                                    </h4>
                                    <p><?php echo sanitize($subject['subject_name']); ?></p>
                                </div>
                                <div class="subject-card-actions">
                                    <button class="btn btn-info btn-sm" onclick="showSubjectDetails('<?php echo sanitize($subject['subject_code']); ?>')">View Details</button>
                                    <span class="units-badge"><?php echo sanitize($subject['units']); ?> Units</span>
                                </div>
                            </div>
                            
                            <?php if (!empty($subject['sections'])): ?>
                                <div class="section-list">
                                    <?php foreach ($subject['sections'] as $section): ?>
                                        <?php
                                        $is_selected = false;
                                        foreach ($selected_subjects as $selected) {
                                            if ($selected['subject_code'] === $subject['subject_code'] && 
                                                $selected['section_id'] == $section['section_id']) {
                                                $is_selected = true;
                                                break;
                                            }
                                        }
                                        ?>
                                        <div class="section-item">
                                            <div class="section-info">
                                                <span><strong>Day:</strong> <?php echo sanitize($section['day']); ?></span>
                                                <span><strong>Time:</strong> 
                                                    <?php echo date('g:i A', strtotime($section['time_start'])); ?> - 
                                                    <?php echo date('g:i A', strtotime($section['time_end'])); ?>
                                                </span>
                                                <span><strong>Room:</strong> <?php echo sanitize($section['room']); ?></span>
                                                <span><strong>Capacity:</strong> <?php echo sanitize($section['capacity']); ?></span>
                                            </div>
                                            <?php if ($is_selected): ?>
                                                <button class="btn btn-disabled" disabled>Selected</button>
                                            <?php else: ?>
                                                <button class="btn btn-primary" 
                                                        onclick="addSubject('<?php echo sanitize($subject['subject_code']); ?>', '<?php echo $section['section_id']; ?>')">
                                                    Add
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="no-data">No sections available for this subject.</p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Enrollment Actions -->
            <?php if (!empty($selected_subjects)): ?>
                <div class="enrollment-actions">
                    <button class="btn btn-secondary" onclick="clearSelection()">Clear Selection</button>
                    <button class="btn btn-primary" onclick="finalizeEnrollment()">Finalize Enrollment</button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Custom Confirmation Modal -->
    <div id="confirmModal" class="modal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h3>Confirm Enrollment</h3>
            </div>
            <div class="modal-body">
                <p class="enrollment-warning"><strong>Please review your enrollment before finalizing.</strong></p>
                <div id="enrollmentSummary">
                    <!-- Will be populated by JavaScript -->
                </div>
                <p class="enrollment-note"><strong>Note:</strong> This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('confirmModal')">Cancel</button>
                <button class="btn btn-primary" id="finalizeBtn" onclick="submitEnrollment()">Yes, Finalize Enrollment</button>
            </div>
        </div>
    </div>
    
    <!-- Subject Details Modal -->
    <div id="subjectDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Subject Details</h3>
            </div>
            <div class="modal-body" id="subjectDetailsContent">
                <!-- Will be populated by JavaScript -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('subjectDetailsModal')">Close</button>
            </div>
        </div>
    </div>
    
    <script src="js/main.js"></script>
    <script src="js/enroll.js"></script>
    <script src="js/enroll_filters.js"></script>
</body>
</html>

