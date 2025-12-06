<?php
/**
 * Student Enrollment Report (SER)
 * Printable enrollment report
 */
require_once 'config.php';
requireLogin();

$student_id = getCurrentStudentId();
$pdo = getDBConnection();

// Get student information
$stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    header('Location: login.php');
    exit;
}

// Get latest enrollment
$stmt = $pdo->prepare("SELECT * FROM enrollments 
                       WHERE student_id = ? 
                       ORDER BY date_submitted DESC 
                       LIMIT 1");
$stmt->execute([$student_id]);
$enrollment = $stmt->fetch();

if (!$enrollment) {
    header('Location: dashboard.php');
    exit;
}

// Get enrolled subjects
$stmt = $pdo->prepare("SELECT es.*, s.subject_name, s.units, sec.day, sec.time_start, sec.time_end, sec.room
                       FROM enrollment_subjects es
                       JOIN subjects s ON es.subject_code = s.subject_code
                       JOIN sections sec ON es.section_id = sec.section_id
                       WHERE es.enrollment_id = ?
                       ORDER BY es.subject_code");
$stmt->execute([$enrollment['enrollment_id']]);
$enrolled_subjects = $stmt->fetchAll();

// Calculate total units
$total_units = 0;
foreach ($enrolled_subjects as $subject) {
    $total_units += $subject['units'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Enrollment Report - <?php echo sanitize($student['student_id']); ?></title>
    <link rel="stylesheet" href="styles.css">
    <style>
        @media print {
            .no-print {
                display: none;
            }
            body {
                margin: 0;
                padding: 0;
            }
            .ser-header-line {
                display: block !important;
            }
        }
    </style>
</head>
<body>
    <div class="ser-container">
        <div class="ser-actions no-print">
            <button onclick="window.print()" class="btn btn-primary">Print SER</button>
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
        
        <!-- PLM Header for SER -->
        <div class="ser-plm-header">
            <div class="ser-plm-logo-section">
                <?php if (file_exists('images/plm-logo.png')): ?>
                    <img src="images/plm-logo.png" alt="PLM Logo" class="ser-plm-logo">
                <?php elseif (file_exists('images/plm-logo.jpg')): ?>
                    <img src="images/plm-logo.jpg" alt="PLM Logo" class="ser-plm-logo">
                <?php elseif (file_exists('images/plm-logo.svg')): ?>
                    <img src="images/plm-logo.svg" alt="PLM Logo" class="ser-plm-logo">
                <?php endif; ?>
                <div class="ser-plm-title">
                    <h1 class="ser-plm-main-title">PAMANTASAN NG LUNGSOD NG MAYNILA</h1>
                    <p class="ser-plm-subtitle">University of the City of Manila</p>
                </div>
            </div>
            <div class="ser-header-line"></div>
        </div>
        
        <div class="ser-header">
            <h1>STUDENT ENROLLMENT REPORT</h1>
        </div>
        
        <div class="ser-student-info">
            <table>
                <tr>
                    <td><strong>Student Name:</strong></td>
                    <td><?php echo sanitize($student['full_name']); ?></td>
                </tr>
                <tr>
                    <td><strong>Student ID:</strong></td>
                    <td><?php echo sanitize($student['student_id']); ?></td>
                </tr>
                <tr>
                    <td><strong>Program:</strong></td>
                    <td><?php echo sanitize($student['program']); ?></td>
                </tr>
                <tr>
                    <td><strong>College:</strong></td>
                    <td><?php echo sanitize($student['college']); ?></td>
                </tr>
                <tr>
                    <td><strong>Year Level:</strong></td>
                    <td><?php echo sanitize($student['year_level']); ?></td>
                </tr>
                <tr>
                    <td><strong>Semester:</strong></td>
                    <td><?php echo sanitize($enrollment['semester']); ?> Semester</td>
                </tr>
                <tr>
                    <td><strong>Enrollment Date:</strong></td>
                    <td><?php echo date('F d, Y h:i A', strtotime($enrollment['date_submitted'])); ?></td>
                </tr>
            </table>
        </div>
        
        <div class="ser-subjects">
            <h3>Enrolled Subjects</h3>
            <table class="ser-subjects">
                <thead>
                    <tr>
                        <th>Subject Code</th>
                        <th>Subject Name</th>
                        <th>Section</th>
                        <th>Schedule</th>
                        <th>Room</th>
                        <th>Units</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($enrolled_subjects as $subject): ?>
                        <tr>
                            <td><?php echo sanitize($subject['subject_code']); ?></td>
                            <td><?php echo sanitize($subject['subject_name']); ?></td>
                            <td><?php echo sanitize($subject['day']); ?></td>
                            <td>
                                <?php echo date('g:i A', strtotime($subject['time_start'])); ?> - 
                                <?php echo date('g:i A', strtotime($subject['time_end'])); ?>
                            </td>
                            <td><?php echo sanitize($subject['room']); ?></td>
                            <td><?php echo sanitize($subject['units']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr style="font-weight: bold; background-color: #f0f0f0;">
                        <td colspan="5" style="text-align: right;">Total Units:</td>
                        <td><?php echo $total_units; ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="ser-footer">
            <p><strong>Generated on:</strong> <?php echo date('F d, Y h:i A'); ?></p>
            <p style="margin-top: 40px;">This is a system-generated document.</p>
        </div>
    </div>
</body>
</html>

