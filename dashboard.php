<?php
/**
 * Dashboard Page
 * Displays student information and study plan
 */
require_once 'config.php';
requireLogin();

$student_id = getCurrentStudentId();
$pdo = getDBConnection();

// Fetch student information
$stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Fetch enrolled subjects for Study Plan (only if enrolled)
$enrolled_subjects = [];
$total_units = 0;
$current_enrollment = null;
$has_enrollment_record = false;

// Get latest enrollment (check regardless of status - this is what ser.php and enroll.php actually check)
$stmt = $pdo->prepare("SELECT * FROM enrollments 
                       WHERE student_id = ? 
                       ORDER BY date_submitted DESC 
                       LIMIT 1");
$stmt->execute([$student_id]);
$current_enrollment = $stmt->fetch();

if ($current_enrollment) {
    $has_enrollment_record = true;
    // Get enrolled subjects
    $stmt = $pdo->prepare("SELECT es.*, s.subject_name, s.units, sec.day, sec.time_start, sec.time_end, sec.room
                           FROM enrollment_subjects es
                           JOIN subjects s ON es.subject_code = s.subject_code
                           JOIN sections sec ON es.section_id = sec.section_id
                           WHERE es.enrollment_id = ?
                           ORDER BY es.subject_code");
    $stmt->execute([$current_enrollment['enrollment_id']]);
    $enrolled_subjects = $stmt->fetchAll();
    
    // Calculate total units
    foreach ($enrolled_subjects as $subject) {
        $total_units += $subject['units'];
    }
}

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
                <button class="tab-btn <?php echo $active_tab === 'studyplan' ? 'active' : ''; ?>" 
                        onclick="showTab('studyplan')">Study Plan</button>
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
                            <?php 
                            // Determine status based on enrollment record (most reliable)
                            // If there's an enrollment record, student is Enlisted
                            if ($has_enrollment_record) {
                                $enrollment_status = 'Enlisted';
                            } else {
                                // Otherwise, check the database status field
                                $enrollment_status = isset($student['enrollment_status']) && !empty($student['enrollment_status']) 
                                    ? $student['enrollment_status'] 
                                    : 'Not Enlisted';
                                
                                // Map old values to new values for display
                                if ($enrollment_status === 'Enrolled') {
                                    $enrollment_status = 'Enlisted';
                                } elseif ($enrollment_status === 'Not Enrolled') {
                                    $enrollment_status = 'Not Enlisted';
                                }
                                
                                // Ensure we have a valid status
                                if (empty($enrollment_status)) {
                                    $enrollment_status = 'Not Enlisted';
                                }
                            }
                            
                            $status_class = strtolower(str_replace(' ', '-', $enrollment_status));
                            ?>
                            <span class="status-badge <?php echo $status_class; ?>">
                                <?php echo sanitize($enrollment_status); ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Study Plan Tab -->
                <div id="studyplan" class="tab-pane <?php echo $active_tab === 'studyplan' ? 'active' : ''; ?>">
                    <h2>Study Plan</h2>
                    <?php if (!$has_enrollment_record || empty($enrolled_subjects)): ?>
                        <p class="no-data">No enlisted subjects. Please enlist to view your study plan.</p>
                    <?php else: ?>
                        <?php if ($current_enrollment): ?>
                            <div style="margin-bottom: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 4px;">
                                <p><strong>Semester:</strong> <?php echo sanitize($current_enrollment['semester']); ?> Semester</p>
                                <p><strong>Enlistment Date:</strong> <?php echo date('F d, Y h:i A', strtotime($current_enrollment['date_submitted'])); ?></p>
                            </div>
                        <?php endif; ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Subject Code</th>
                                    <th>Subject Name</th>
                                    <th>Day</th>
                                    <th>Time</th>
                                    <th>Room</th>
                                    <th>Units</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($enrolled_subjects as $subject): ?>
                                    <tr>
                                        <td><?php echo sanitize($subject['subject_code']); ?></td>
                                        <td><?php echo sanitize($subject['subject_name']); ?></td>
                                        <td>TBA</td>
                                        <td>TBA</td>
                                        <td>TBA</td>
                                        <td><?php echo sanitize($subject['units']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr style="font-weight: bold; background-color: #e7f3ff;">
                                    <td colspan="5" style="text-align: right;">Total Units:</td>
                                    <td><?php echo $total_units; ?></td>
                                </tr>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="dashboard-actions">
                <?php if (!$has_enrollment_record): ?>
                    <a href="enroll.php" class="btn btn-primary">Enlist</a>
                <?php else: ?>
                    <button class="btn btn-disabled" disabled>Already Enlisted</button>
                <?php endif; ?>
                
                <?php if ($has_enrollment_record): ?>
                    <a href="ser.php" class="btn btn-secondary" target="_blank">Print SER</a>
                <?php else: ?>
                    <button class="btn btn-disabled" disabled>Print SER</button>
                <?php endif; ?>
                
                <!-- Testing Tool: Reset Enlistment -->
                <a href="reset_enrollment.html" class="btn btn-warning" title="Testing tool to reset enlistment status">Reset Enlistment (Testing)</a>
            </div>
        </div>
    </div>
    
    <script src="js/main.js"></script>
</body>
</html>

