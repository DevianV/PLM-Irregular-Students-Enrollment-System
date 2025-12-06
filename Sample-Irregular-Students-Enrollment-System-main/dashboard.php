<?php
/**
 * Dashboard Page
 * Displays student information, grades, and taken subjects
 */
require_once 'config.php';
requireLogin();

$student_id = getCurrentStudentId();
$pdo = getDBConnection();

// Fetch student information
$stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Fetch grades
$stmt = $pdo->prepare("SELECT g.*, s.subject_name 
                      FROM grades g 
                      JOIN subjects s ON g.subject_code = s.subject_code 
                      WHERE g.student_id = ? 
                      ORDER BY g.id DESC");
$stmt->execute([$student_id]);
$grades = $stmt->fetchAll();

// Fetch taken subjects (from grades and enlistments)
$stmt = $pdo->prepare("SELECT DISTINCT s.subject_code, s.subject_name, s.units 
                      FROM subjects s 
                      WHERE s.subject_code IN (
                          SELECT subject_code FROM grades WHERE student_id = ? AND passed = 1
                          UNION
                          SELECT es.subject_code FROM enrollment_subjects es
                          JOIN enrollments e ON es.enrollment_id = e.enrollment_id
                          WHERE e.student_id = ?
                      )
                      ORDER BY s.subject_code");
$stmt->execute([$student_id, $student_id]);
$taken_subjects = $stmt->fetchAll();

// Determine active tab
$active_tab = $_GET['tab'] ?? 'personal';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - PLM Enlistment System</title>
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
                <span class="user-welcome">Welcome, <strong><?php echo sanitize($student['full_name']); ?></strong></span>
                <a href="logout.php" class="btn btn-secondary">Logout</a>
            </div>
        </div>
        <div class="plm-header-line"></div>
    </header>

    <div class="container">
        <header class="dashboard-header">
            <h1>Irregular Student Enlistment System</h1>
        </header>
        
        <div class="dashboard-content">
            <div class="tabs">
                <button class="tab-btn <?php echo $active_tab === 'personal' ? 'active' : ''; ?>" 
                        onclick="showTab('personal')">Personal Info</button>
                <button class="tab-btn <?php echo $active_tab === 'grades' ? 'active' : ''; ?>" 
                        onclick="showTab('grades')">Grades</button>
                <button class="tab-btn <?php echo $active_tab === 'taken' ? 'active' : ''; ?>" 
                        onclick="showTab('taken')">Taken Subjects</button>
            </div>
            
            <div class="tab-content">
                <!-- Personal Info Tab -->
                <div id="personal" class="tab-pane <?php echo $active_tab === 'personal' ? 'active' : ''; ?>">
                    <h2>Personal Information</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Student ID:</label>
                            <span><?php echo sanitize($student['student_id']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Full Name:</label>
                            <span><?php echo sanitize($student['full_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Program:</label>
                            <span><?php echo sanitize($student['program']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>College:</label>
                            <span><?php echo sanitize($student['college']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>PLM Email:</label>
                            <span><?php echo sanitize($student['plm_email']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Year Level:</label>
                            <span><?php echo sanitize($student['year_level']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Status:</label>
                            <span><?php echo sanitize($student['status']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Enlistment Status:</label>
                            <span class="status-badge <?php echo strtolower(str_replace(' ', '-', $student['enrollment_status'])); ?>">
                                <?php echo sanitize($student['enrollment_status']); ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Grades Tab -->
                <div id="grades" class="tab-pane <?php echo $active_tab === 'grades' ? 'active' : ''; ?>">
                    <h2>Grades</h2>
                    <?php if (empty($grades)): ?>
                        <p class="no-data">No grades recorded yet.</p>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Subject Code</th>
                                    <th>Subject Name</th>
                                    <th>Grade</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($grades as $grade): ?>
                                    <tr>
                                        <td><?php echo sanitize($grade['subject_code']); ?></td>
                                        <td><?php echo sanitize($grade['subject_name']); ?></td>
                                        <td><?php echo sanitize($grade['grade']); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $grade['passed'] ? 'passed' : 'failed'; ?>">
                                                <?php echo $grade['passed'] ? 'Passed' : 'Failed'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                
                <!-- Taken Subjects Tab -->
                <div id="taken" class="tab-pane <?php echo $active_tab === 'taken' ? 'active' : ''; ?>">
                    <h2>Previously Taken Subjects</h2>
                    <?php if (empty($taken_subjects)): ?>
                        <p class="no-data">No subjects taken yet.</p>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Subject Code</th>
                                    <th>Subject Name</th>
                                    <th>Units</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($taken_subjects as $subject): ?>
                                    <tr>
                                        <td><?php echo sanitize($subject['subject_code']); ?></td>
                                        <td><?php echo sanitize($subject['subject_name']); ?></td>
                                        <td><?php echo sanitize($subject['units']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="dashboard-actions">
                <?php if ($student['enrollment_status'] === 'Not Enrolled'): ?>
                    <a href="enroll.php" class="btn btn-primary">Enroll</a>
                <?php else: ?>
                    <button class="btn btn-disabled" disabled>Already Enrolled</button>
                <?php endif; ?>
                
                <?php if ($student['enrollment_status'] === 'Enrolled'): ?>
                    <a href="ser.php" class="btn btn-secondary" target="_blank">Print SER</a>
                <?php else: ?>
                    <button class="btn btn-disabled" disabled>Print SER</button>
                <?php endif; ?>
                
                <!-- Testing Tool: Reset Enrollment -->
                <a href="reset_enrollment.html" class="btn btn-warning" title="Testing tool to reset enrollment status">Reset Enlistment (Testing)</a>
            </div>
        </div>
    </div>
    
    <script src="js/main.js"></script>
</body>
</html>

