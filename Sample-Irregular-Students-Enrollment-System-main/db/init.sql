-- ============================================
-- PLM IRREGULAR STUDENT ENROLLMENT SYSTEM
-- Clean Database Initialization
-- ============================================

DROP DATABASE IF EXISTS plm_enrollment_db;
CREATE DATABASE plm_enrollment_db;
USE plm_enrollment_db;

-- ============================================
-- TABLE STRUCTURES
-- ============================================

-- Users table
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student') DEFAULT 'student'
);

-- Students table
CREATE TABLE students (
    student_id VARCHAR(20) PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    program VARCHAR(50) NOT NULL,
    college VARCHAR(50) NOT NULL,
    plm_email VARCHAR(100),
    year_level INT NOT NULL,
    status ENUM('Regular', 'Irregular') NOT NULL,
    enrollment_status ENUM('Not Enrolled', 'Enrolled') DEFAULT 'Not Enrolled',
    FOREIGN KEY (student_id) REFERENCES users(student_id) ON DELETE CASCADE
);

-- Subjects table
CREATE TABLE subjects (
    subject_code VARCHAR(20) PRIMARY KEY,
    subject_name VARCHAR(100) NOT NULL,
    units INT NOT NULL,
    program VARCHAR(50) NOT NULL,
    semester ENUM('1st','2nd','Summer') NOT NULL
);

-- Sections table
CREATE TABLE sections (
    section_id INT AUTO_INCREMENT PRIMARY KEY,
    subject_code VARCHAR(20) NOT NULL,
    day VARCHAR(20) NOT NULL,
    time_start TIME NOT NULL,
    time_end TIME NOT NULL,
    room VARCHAR(50),
    capacity INT DEFAULT 30,
    FOREIGN KEY (subject_code) REFERENCES subjects(subject_code) ON DELETE CASCADE
);

-- Prerequisites table
CREATE TABLE prerequisites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_code VARCHAR(20) NOT NULL,
    prerequisite_code VARCHAR(20) NOT NULL,
    FOREIGN KEY (subject_code) REFERENCES subjects(subject_code) ON DELETE CASCADE,
    FOREIGN KEY (prerequisite_code) REFERENCES subjects(subject_code) ON DELETE CASCADE
);

-- Corequisites table
CREATE TABLE corequisites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_code VARCHAR(20) NOT NULL,
    coreq_code VARCHAR(20) NOT NULL,
    FOREIGN KEY (subject_code) REFERENCES subjects(subject_code) ON DELETE CASCADE,
    FOREIGN KEY (coreq_code) REFERENCES subjects(subject_code) ON DELETE CASCADE
);

-- Grades table
CREATE TABLE grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) NOT NULL,
    subject_code VARCHAR(20) NOT NULL,
    grade VARCHAR(5) NOT NULL,
    passed TINYINT(1) NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (subject_code) REFERENCES subjects(subject_code) ON DELETE CASCADE
);

-- Enrollments table
CREATE TABLE enrollments (
    enrollment_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) NOT NULL,
    semester ENUM('1st','2nd','Summer') NOT NULL,
    date_submitted DATETIME NOT NULL,
    status ENUM('Enrolled') DEFAULT 'Enrolled',
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (student_id, semester)
);

-- Enrollment subjects table
CREATE TABLE enrollment_subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT NOT NULL,
    subject_code VARCHAR(20) NOT NULL,
    section_id INT NOT NULL,
    units INT NOT NULL,
    FOREIGN KEY (enrollment_id) REFERENCES enrollments(enrollment_id) ON DELETE CASCADE,
    FOREIGN KEY (subject_code) REFERENCES subjects(subject_code) ON DELETE CASCADE,
    FOREIGN KEY (section_id) REFERENCES sections(section_id) ON DELETE CASCADE
);

-- ============================================
-- SAMPLE USERS (Password: password123)
-- ============================================
INSERT INTO users (student_id, password_hash, role) VALUES
('2020-12345', '$2y$10$EzBrVRrihJNflzC85EiYOuGZKEvo4JU0FpMjkyjMND2PMETucejyO', 'student'),
('2020-12346', '$2y$10$EzBrVRrihJNflzC85EiYOuGZKEvo4JU0FpMjkyjMND2PMETucejyO', 'student'),
('2020-12347', '$2y$10$EzBrVRrihJNflzC85EiYOuGZKEvo4JU0FpMjkyjMND2PMETucejyO', 'student'),
('2020-12348', '$2y$10$EzBrVRrihJNflzC85EiYOuGZKEvo4JU0FpMjkyjMND2PMETucejyO', 'student'),
('2020-12349', '$2y$10$EzBrVRrihJNflzC85EiYOuGZKEvo4JU0FpMjkyjMND2PMETucejyO', 'student');

-- ============================================
-- SAMPLE STUDENTS
-- ============================================
INSERT INTO students (student_id, full_name, program, college, plm_email, year_level, status, enrollment_status) VALUES
('2020-12345', 'Juan Dela Cruz', 'BS Computer Science', 'College of Engineering', 'juan.delacruz@plm.edu.ph', 3, 'Irregular', 'Not Enrolled'),
('2020-12346', 'Maria Santos', 'BS Information Technology', 'College of Engineering', 'maria.santos@plm.edu.ph', 2, 'Irregular', 'Not Enrolled'),
('2020-12347', 'Pedro Reyes', 'BS Computer Science', 'College of Engineering', 'pedro.reyes@plm.edu.ph', 4, 'Regular', 'Not Enrolled'),
('2020-12348', 'Ana Garcia', 'BS Information Systems', 'College of Engineering', 'ana.garcia@plm.edu.ph', 2, 'Irregular', 'Not Enrolled'),
('2020-12349', 'Carlos Mendoza', 'BS Computer Science', 'College of Engineering', 'carlos.mendoza@plm.edu.ph', 1, 'Irregular', 'Not Enrolled');

-- ============================================
-- SUBJECTS (1st Semester Only - Current Semester)
-- ============================================

-- BS Computer Science Subjects
INSERT INTO subjects (subject_code, subject_name, units, program, semester) VALUES
-- Year 1
('CS101', 'Introduction to Computer Science', 3, 'BS Computer Science', '1st'),
('CS102', 'Programming Fundamentals', 3, 'BS Computer Science', '1st'),
('CS103', 'Computer Ethics', 3, 'BS Computer Science', '1st'),
('CS104', 'Technical Writing', 3, 'BS Computer Science', '1st'),
-- Year 2
('CS201', 'Data Structures', 3, 'BS Computer Science', '1st'),
('CS202', 'Object-Oriented Programming', 3, 'BS Computer Science', '1st'),
('CS203', 'Discrete Mathematics', 3, 'BS Computer Science', '1st'),
('CS204', 'Computer Organization', 3, 'BS Computer Science', '1st'),
('CS205', 'Software Engineering', 3, 'BS Computer Science', '1st'),
('CS206', 'Operating Systems', 3, 'BS Computer Science', '1st'),
-- Year 3
('CS301', 'Database Systems', 3, 'BS Computer Science', '1st'),
('CS302', 'Web Development', 3, 'BS Computer Science', '1st'),
('CS303', 'Computer Networks', 3, 'BS Computer Science', '1st'),
('CS304', 'Artificial Intelligence', 3, 'BS Computer Science', '1st');

-- BS Information Technology Subjects
INSERT INTO subjects (subject_code, subject_name, units, program, semester) VALUES
-- Year 1
('IT101', 'Introduction to IT', 3, 'BS Information Technology', '1st'),
('IT102', 'Computer Applications', 3, 'BS Information Technology', '1st'),
-- Year 2
('IT201', 'Network Fundamentals', 3, 'BS Information Technology', '1st'),
('IT202', 'Database Management', 3, 'BS Information Technology', '1st'),
('IT203', 'Web Technologies', 3, 'BS Information Technology', '1st'),
('IT204', 'System Analysis and Design', 3, 'BS Information Technology', '1st'),
('IT205', 'Information Security', 3, 'BS Information Technology', '1st'),
('IT206', 'IT Project Management', 3, 'BS Information Technology', '1st');

-- BS Information Systems Subjects
INSERT INTO subjects (subject_code, subject_name, units, program, semester) VALUES
-- Year 1
('IS101', 'Introduction to Information Systems', 3, 'BS Information Systems', '1st'),
('IS102', 'Business Process Analysis', 3, 'BS Information Systems', '1st'),
('IS103', 'Business Communication', 3, 'BS Information Systems', '1st'),
('IS104', 'E-Commerce Fundamentals', 3, 'BS Information Systems', '1st'),
-- Year 2
('IS201', 'Systems Analysis and Design', 3, 'BS Information Systems', '1st'),
('IS202', 'Enterprise Architecture', 3, 'BS Information Systems', '1st'),
('IS203', 'IT Project Management', 3, 'BS Information Systems', '1st'),
('IS204', 'Information Systems Ethics', 3, 'BS Information Systems', '1st'),
-- Year 3
('IS301', 'Business Intelligence', 3, 'BS Information Systems', '1st'),
('IS302', 'IS Capstone Project 1', 3, 'BS Information Systems', '1st');

-- ============================================
-- SECTIONS
-- ============================================

-- CS Subjects Sections
INSERT INTO sections (subject_code, day, time_start, time_end, room, capacity) VALUES
-- CS101
('CS101', 'Monday', '08:00:00', '10:00:00', 'Room 101', 30),
('CS101', 'Wednesday', '08:00:00', '10:00:00', 'Room 101', 30),
-- CS102
('CS102', 'Monday', '10:00:00', '12:00:00', 'Room 102', 30),
('CS102', 'Tuesday', '10:00:00', '12:00:00', 'Room 102', 30),
-- CS103
('CS103', 'Tuesday', '08:00:00', '10:00:00', 'Room 103', 30),
('CS103', 'Thursday', '08:00:00', '10:00:00', 'Room 103', 30),
-- CS104
('CS104', 'Wednesday', '10:00:00', '12:00:00', 'Room 104', 30),
('CS104', 'Friday', '10:00:00', '12:00:00', 'Room 104', 30),
-- CS201
('CS201', 'Tuesday', '08:00:00', '10:00:00', 'Room 201', 25),
('CS201', 'Thursday', '08:00:00', '10:00:00', 'Room 201', 25),
-- CS202
('CS202', 'Tuesday', '13:00:00', '15:00:00', 'Room 202', 25),
('CS202', 'Thursday', '13:00:00', '15:00:00', 'Room 202', 25),
-- CS203
('CS203', 'Monday', '13:00:00', '15:00:00', 'Room 203', 25),
('CS203', 'Wednesday', '13:00:00', '15:00:00', 'Room 203', 25),
-- CS204
('CS204', 'Tuesday', '15:00:00', '17:00:00', 'Room 204', 25),
('CS204', 'Thursday', '15:00:00', '17:00:00', 'Room 204', 25),
-- CS205
('CS205', 'Monday', '15:00:00', '17:00:00', 'Room 205', 25),
('CS205', 'Wednesday', '15:00:00', '17:00:00', 'Room 205', 25),
-- CS206
('CS206', 'Friday', '08:00:00', '10:00:00', 'Room 206', 25),
('CS206', 'Friday', '10:00:00', '12:00:00', 'Room 206', 25),
-- CS301
('CS301', 'Wednesday', '10:00:00', '12:00:00', 'Room 301', 20),
('CS301', 'Friday', '10:00:00', '12:00:00', 'Room 301', 20),
-- CS302
('CS302', 'Wednesday', '13:00:00', '15:00:00', 'Room 302', 20),
('CS302', 'Friday', '13:00:00', '15:00:00', 'Room 302', 20),
-- CS303
('CS303', 'Monday', '10:00:00', '12:00:00', 'Room 303', 20),
('CS303', 'Wednesday', '10:00:00', '12:00:00', 'Room 303', 20),
-- CS304
('CS304', 'Tuesday', '10:00:00', '12:00:00', 'Room 304', 20),
('CS304', 'Thursday', '10:00:00', '12:00:00', 'Room 304', 20);

-- IT Subjects Sections
INSERT INTO sections (subject_code, day, time_start, time_end, room, capacity) VALUES
-- IT101
('IT101', 'Monday', '14:00:00', '16:00:00', 'Room 401', 30),
('IT101', 'Wednesday', '14:00:00', '16:00:00', 'Room 401', 30),
-- IT102
('IT102', 'Tuesday', '08:00:00', '10:00:00', 'Room 402', 30),
('IT102', 'Thursday', '08:00:00', '10:00:00', 'Room 402', 30),
-- IT201
('IT201', 'Tuesday', '15:00:00', '17:00:00', 'Room 403', 25),
('IT201', 'Thursday', '15:00:00', '17:00:00', 'Room 403', 25),
-- IT202
('IT202', 'Wednesday', '14:00:00', '16:00:00', 'Room 404', 25),
('IT202', 'Friday', '14:00:00', '16:00:00', 'Room 404', 25),
-- IT203
('IT203', 'Monday', '16:00:00', '18:00:00', 'Room 405', 25),
('IT203', 'Wednesday', '16:00:00', '18:00:00', 'Room 405', 25),
-- IT204
('IT204', 'Tuesday', '13:00:00', '15:00:00', 'Room 406', 25),
('IT204', 'Thursday', '13:00:00', '15:00:00', 'Room 406', 25),
-- IT205
('IT205', 'Friday', '10:00:00', '12:00:00', 'Room 407', 25),
('IT205', 'Friday', '13:00:00', '15:00:00', 'Room 407', 25),
-- IT206
('IT206', 'Monday', '13:00:00', '15:00:00', 'Room 408', 25),
('IT206', 'Wednesday', '13:00:00', '15:00:00', 'Room 408', 25);

-- IS Subjects Sections
INSERT INTO sections (subject_code, day, time_start, time_end, room, capacity) VALUES
-- IS101
('IS101', 'Monday', '08:00:00', '10:00:00', 'Room 501', 30),
('IS101', 'Wednesday', '08:00:00', '10:00:00', 'Room 501', 30),
-- IS102
('IS102', 'Tuesday', '08:00:00', '10:00:00', 'Room 502', 25),
('IS102', 'Thursday', '08:00:00', '10:00:00', 'Room 502', 25),
-- IS103
('IS103', 'Tuesday', '10:00:00', '12:00:00', 'Room 503', 30),
('IS103', 'Thursday', '10:00:00', '12:00:00', 'Room 503', 30),
-- IS104
('IS104', 'Monday', '10:00:00', '12:00:00', 'Room 504', 30),
('IS104', 'Wednesday', '10:00:00', '12:00:00', 'Room 504', 30),
-- IS201
('IS201', 'Monday', '10:00:00', '12:00:00', 'Room 505', 25),
('IS201', 'Wednesday', '10:00:00', '12:00:00', 'Room 505', 25),
-- IS202
('IS202', 'Tuesday', '10:00:00', '12:00:00', 'Room 506', 25),
('IS202', 'Thursday', '10:00:00', '12:00:00', 'Room 506', 25),
-- IS203
('IS203', 'Monday', '13:00:00', '15:00:00', 'Room 507', 25),
('IS203', 'Wednesday', '13:00:00', '15:00:00', 'Room 507', 25),
-- IS204
('IS204', 'Tuesday', '13:00:00', '15:00:00', 'Room 508', 30),
('IS204', 'Thursday', '13:00:00', '15:00:00', 'Room 508', 30),
-- IS301
('IS301', 'Monday', '13:00:00', '15:00:00', 'Room 509', 20),
('IS301', 'Wednesday', '13:00:00', '15:00:00', 'Room 509', 20),
-- IS302
('IS302', 'Friday', '08:00:00', '10:00:00', 'Room 510', 15),
('IS302', 'Friday', '10:00:00', '12:00:00', 'Room 510', 15);

-- ============================================
-- PREREQUISITES
-- ============================================
INSERT INTO prerequisites (subject_code, prerequisite_code) VALUES
-- CS Prerequisites
('CS201', 'CS102'),  -- Data Structures requires Programming Fundamentals
('CS202', 'CS102'),  -- OOP requires Programming Fundamentals
('CS301', 'CS201'),  -- Database Systems requires Data Structures
('CS302', 'CS202'),  -- Web Development requires OOP
('CS303', 'CS201'),  -- Computer Networks requires Data Structures
('CS303', 'CS202'),  -- Computer Networks requires OOP
('CS304', 'CS201'),  -- AI requires Data Structures
('CS304', 'CS203'),  -- AI requires Discrete Mathematics
-- CS203, CS204, CS205, CS206 have NO prerequisites (can enroll directly)
-- CS103, CS104 have NO prerequisites (can enroll directly)

-- IT Prerequisites
('IT201', 'IT101'),  -- Network Fundamentals requires Intro to IT
('IT202', 'IT101'),  -- Database Management requires Intro to IT
('IT203', 'IT101'),  -- Web Technologies requires Intro to IT
('IT204', 'IT101'),  -- System Analysis requires Intro to IT
('IT205', 'IT101'),  -- Information Security requires Intro to IT
('IT206', 'IT201'),  -- IT Project Management requires Network Fundamentals
-- IT102 has NO prerequisites

-- IS Prerequisites
('IS102', 'IS101'),  -- Business Process Analysis requires Intro to IS
('IS201', 'IS102'),  -- Systems Analysis requires Business Process Analysis
('IS201', 'IT202'),  -- Systems Analysis requires Database Management (cross-program)
('IS202', 'IS201'),  -- Enterprise Architecture requires Systems Analysis
('IS203', 'IS101'),  -- IT Project Management requires Intro to IS
('IS301', 'IS202'),  -- Business Intelligence requires Enterprise Architecture
('IS301', 'IT202'),  -- Business Intelligence requires Database Management
('IS302', 'IS301'),  -- IS Capstone requires Business Intelligence
-- IS101, IS103, IS104, IS204 have NO prerequisites (can enroll directly)

-- Cross-program prerequisites
('IS201', 'IT202');  -- Systems Analysis also requires Database Management

-- ============================================
-- COREQUISITES
-- ============================================
INSERT INTO corequisites (subject_code, coreq_code) VALUES
-- CS Corequisites (all bidirectional)
('CS201', 'CS202'),  -- Data Structures and OOP are corequisites
('CS202', 'CS201'),  -- OOP and Data Structures are corequisites
('CS203', 'CS204'),  -- Discrete Mathematics and Computer Organization are corequisites
('CS204', 'CS203'),  -- Computer Organization and Discrete Mathematics are corequisites
-- IT Corequisites (all bidirectional)
('IT201', 'IT202'),  -- Network Fundamentals and Database Management are corequisites
('IT202', 'IT201'),  -- Database Management and Network Fundamentals are corequisites
('IT203', 'IT204'),  -- Web Technologies and System Analysis are corequisites
('IT204', 'IT203'),  -- System Analysis and Web Technologies are corequisites
-- IS Corequisites (all bidirectional)
('IS201', 'IS203'),  -- Systems Analysis and IT Project Management are corequisites
('IS203', 'IS201'),  -- IT Project Management and Systems Analysis are corequisites
('IS202', 'IS204'),  -- Enterprise Architecture and IS Ethics are corequisites
('IS204', 'IS202');  -- IS Ethics and Enterprise Architecture are corequisites

-- ============================================
-- SAMPLE GRADES (Prerequisites for Enrollment)
-- ============================================

-- Juan Dela Cruz (CS, Year 3) - Comprehensive grades showing progression
INSERT INTO grades (student_id, subject_code, grade, passed) VALUES
-- Year 1 Subjects
('2020-12345', 'CS101', 'B+', 1),
('2020-12345', 'CS102', 'A', 1),
('2020-12345', 'CS103', 'A-', 1),
('2020-12345', 'CS104', 'B+', 1),
-- Year 2 Subjects
('2020-12345', 'CS201', 'A-', 1),
('2020-12345', 'CS202', 'B+', 1),
('2020-12345', 'CS203', 'A', 1),
('2020-12345', 'CS204', 'B+', 1),
('2020-12345', 'CS205', 'A-', 1),
('2020-12345', 'CS206', 'B', 1);
-- Can enroll in: CS301, CS302, CS303, CS304 (Year 3 subjects)

-- Maria Santos (IT, Year 2) - Comprehensive IT program grades
INSERT INTO grades (student_id, subject_code, grade, passed) VALUES
-- Year 1 Subjects
('2020-12346', 'IT101', 'B', 1),
('2020-12346', 'IT102', 'B+', 1),
-- Year 2 Subjects (some completed)
('2020-12346', 'IT201', 'B+', 1),
('2020-12346', 'IT202', 'A-', 1);
-- Can enroll in: IT203, IT204, IT205, IT206 (remaining Year 2 subjects)

-- Ana Garcia (IS, Year 2) - Comprehensive IS program grades
INSERT INTO grades (student_id, subject_code, grade, passed) VALUES
-- Year 1 Subjects
('2020-12348', 'IS101', 'A', 1),
('2020-12348', 'IS102', 'B+', 1),
('2020-12348', 'IS103', 'A-', 1),
('2020-12348', 'IS104', 'B+', 1),
-- Cross-program prerequisite (from IT program)
('2020-12348', 'IT202', 'B', 1);
-- Can enroll in: IS201, IS203, IS204 (Year 2 subjects requiring prerequisites)

-- Carlos Mendoza (CS, Year 1) - Freshman with basic foundation
INSERT INTO grades (student_id, subject_code, grade, passed) VALUES
-- Year 1 Subjects (completed)
('2020-12349', 'CS101', 'B', 1),
('2020-12349', 'CS102', 'A-', 1),
('2020-12349', 'CS103', 'B+', 1),
('2020-12349', 'CS104', 'A', 1);
-- Can enroll in: CS201, CS202, CS203, CS204, CS205, CS206 (Year 2 subjects)

-- ============================================
-- END OF INITIALIZATION
-- ============================================
