<?php
/**
 * Enlistment Page
 * Subject selection with real-time validation
 */
require_once 'config.php';
requireLogin();

$student_id = getCurrentStudentId();
$pdo = getDBConnection();

// Check if already enrolled and get student info
$stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Check if student already has an enrollment record (this is what matters, not just status)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE student_id = ?");
$stmt->execute([$student_id]);
$has_enrollment = $stmt->fetchColumn() > 0;

if ($has_enrollment) {
    header('Location: dashboard.php');
    exit;
}

// Load available subjects for current semester
require_once 'php/load_subjects.php';
$available_subjects = loadAvailableSubjects($student_id, CURRENT_SEMESTER);

// Get student's program (already loaded in $student)
$student_program = $student['program'];

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
    <title>Enlist - PLM Enlistment System</title>
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
            <h1>Subject Enlistment</h1>
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
                                        TBA (TBA)
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
            <div class="available-subjects-container">
                <div class="subjects-header">
                    <h3>Available Subjects</h3>
                    <div class="search-filter-container">
                        <input type="text" id="subjectSearch" placeholder="Search subjects..." class="search-input" onkeyup="filterSubjects()" oninput="filterSubjects()">
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
                    <div class="subjects-table-container">
                        <table class="subjects-table" id="availableSubjectsTable">
                            <thead>
                                <tr>
                                    <th>Subject Code</th>
                                    <th>Course Title</th>
                                    <th>Units</th>
                                    <th>Pre/Co-Requisites</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($filtered_subjects as $subject): 
                                    // Determine year level from subject code
                                    $year_level = 1;
                                    if (preg_match('/^[A-Z]+([0-9])/', $subject['subject_code'], $matches)) {
                                        $first_digit = intval($matches[1]);
                                        if ($first_digit == 2) $year_level = 2;
                                        elseif ($first_digit == 3) $year_level = 3;
                                        elseif ($first_digit == 4) $year_level = 4;
                                    }
                                    
                                    // Build prerequisites and corequisites string
                                    $preco_reqs = [];
                                    if (!empty($subject['prerequisites'])) {
                                        foreach ($subject['prerequisites'] as $prereq) {
                                            $preco_reqs[] = 'Pre: ' . $prereq['prerequisite_code'];
                                        }
                                    }
                                    if (!empty($subject['corequisites'])) {
                                        foreach ($subject['corequisites'] as $coreq) {
                                            $preco_reqs[] = 'Co: ' . $coreq['coreq_code'];
                                        }
                                    }
                                    $preco_reqs_str = !empty($preco_reqs) ? implode(', ', $preco_reqs) : 'None';
                                    
                                    // If subject has sections, create a row for each section
                                    if (!empty($subject['sections'])): 
                                        foreach ($subject['sections'] as $section):
                                            $is_selected = false;
                                            foreach ($selected_subjects as $selected) {
                                                if ($selected['subject_code'] === $subject['subject_code'] && 
                                                    $selected['section_id'] == $section['section_id']) {
                                                    $is_selected = true;
                                                    break;
                                                }
                                            }
                                ?>
                                    <tr data-subject-code="<?php echo strtolower($subject['subject_code']); ?>" 
                                        data-subject-name="<?php echo strtolower($subject['subject_name']); ?>"
                                        data-year-level="<?php echo $year_level; ?>"
                                        data-units="<?php echo $subject['units']; ?>"
                                        data-program="<?php echo isset($subject['is_cross_program']) && $subject['is_cross_program'] ? 'cross' : 'main'; ?>">
                                        <td>
                                            <?php echo sanitize($subject['subject_code']); ?>
                                            <?php if (isset($subject['is_cross_program']) && $subject['is_cross_program']): ?>
                                                <span class="cross-program-badge" title="Cross-program prerequisite">Cross-Program</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo sanitize($subject['subject_name']); ?></td>
                                        <td><?php echo sanitize($subject['units']); ?></td>
                                        <td class="preco-reqs"><?php echo $preco_reqs_str; ?></td>
                                        <td class="enlist-action">
                                            <?php if ($is_selected): ?>
                                                <button class="btn btn-disabled" disabled>Selected</button>
                                            <?php else: ?>
                                                <button class="btn btn-primary btn-sm" 
                                                        onclick="addSubject('<?php echo sanitize($subject['subject_code']); ?>', '<?php echo $section['section_id']; ?>')">
                                                    Add
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php 
                                        endforeach;
                                    else: 
                                        // Subject with no sections - still show it
                                ?>
                                    <tr data-subject-code="<?php echo strtolower($subject['subject_code']); ?>" 
                                        data-subject-name="<?php echo strtolower($subject['subject_name']); ?>"
                                        data-year-level="<?php echo $year_level; ?>"
                                        data-units="<?php echo $subject['units']; ?>"
                                        data-program="<?php echo isset($subject['is_cross_program']) && $subject['is_cross_program'] ? 'cross' : 'main'; ?>">
                                        <td>
                                            <?php echo sanitize($subject['subject_code']); ?>
                                            <?php if (isset($subject['is_cross_program']) && $subject['is_cross_program']): ?>
                                                <span class="cross-program-badge" title="Cross-program prerequisite">Cross-Program</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo sanitize($subject['subject_name']); ?></td>
                                        <td><?php echo sanitize($subject['units']); ?></td>
                                        <td class="preco-reqs"><?php echo $preco_reqs_str; ?></td>
                                        <td class="enlist-action">
                                            <button class="btn btn-disabled" disabled>No Sections</button>
                                        </td>
                                    </tr>
                                <?php 
                                    endif;
                                endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Enrollment Actions -->
            <?php if (!empty($selected_subjects)): ?>
                <div class="enrollment-actions">
                    <button class="btn btn-secondary" onclick="clearSelection()">Clear Selection</button>
                    <button class="btn btn-primary" onclick="finalizeEnrollment()">Finalize Enlistment</button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Custom Confirmation Modal -->
    <div id="confirmModal" class="modal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h3>Confirm Enlistment</h3>
            </div>
            <div class="modal-body">
                <p class="enrollment-warning"><strong>Please review your enlistment before finalizing.</strong></p>
                <div id="enrollmentSummary">
                    <!-- Will be populated by JavaScript -->
                </div>
                <p class="enrollment-note"><strong>Note:</strong> This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('confirmModal')">Cancel</button>
                <button class="btn btn-primary" id="finalizeBtn" onclick="submitEnrollment()">Yes, Finalize Enlistment</button>
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

