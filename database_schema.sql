-- QR Attendance System Database Schema
-- Course Enrollment and Management System

-- Create database
CREATE DATABASE IF NOT EXISTS qr_attendance;
USE qr_attendance;

-- Users table (students and lecturers)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_type ENUM('student', 'lecturer', 'admin') NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    student_id VARCHAR(20) UNIQUE,
    lecturer_id VARCHAR(20) UNIQUE,
    phone VARCHAR(20),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_user_type (user_type),
    INDEX idx_student_id (student_id),
    INDEX idx_lecturer_id (lecturer_id),
    INDEX idx_email (email)
);

-- Courses table
CREATE TABLE courses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    credits INT DEFAULT 3,
    department VARCHAR(100),
    faculty VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_code (code),
    INDEX idx_department (department),
    INDEX idx_faculty (faculty)
);

-- Course sections table
CREATE TABLE course_sections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_code VARCHAR(20) NOT NULL,
    section_number VARCHAR(10) NOT NULL,
    lecturer_id VARCHAR(20),
    room VARCHAR(50),
    capacity INT DEFAULT 40,
    enrolled_count INT DEFAULT 0,
    semester VARCHAR(20),
    academic_year VARCHAR(10),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_section (course_code, section_number, semester, academic_year),
    FOREIGN KEY (course_code) REFERENCES courses(code) ON DELETE CASCADE,
    FOREIGN KEY (lecturer_id) REFERENCES users(lecturer_id) ON DELETE SET NULL,
    INDEX idx_course_section (course_code, section_number),
    INDEX idx_lecturer (lecturer_id)
);

-- Course schedules table
CREATE TABLE schedules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_code VARCHAR(20) NOT NULL,
    section_number VARCHAR(10) NOT NULL,
    faculty VARCHAR(100),
    day_of_week ENUM('MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    room VARCHAR(50),
    session_id VARCHAR(100) UNIQUE NOT NULL,
    status ENUM('scheduled', 'in_progress', 'completed', 'cancelled', 'postponed') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (course_code) REFERENCES courses(code) ON DELETE CASCADE,
    INDEX idx_course_section (course_code, section_number),
    INDEX idx_day_time (day_of_week, start_time),
    INDEX idx_session_id (session_id),
    INDEX idx_status (status)
);

-- Student enrollments table
CREATE TABLE enrollments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id VARCHAR(20) NOT NULL,
    course_code VARCHAR(20) NOT NULL,
    section_number VARCHAR(10) NOT NULL,
    enrollment_status ENUM('enrolled', 'waitlisted', 'dropped', 'completed') DEFAULT 'enrolled',
    enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    drop_date TIMESTAMP NULL,
    grade VARCHAR(5) NULL,
    semester VARCHAR(20),
    academic_year VARCHAR(10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_enrollment (student_id, course_code, section_number, semester, academic_year),
    FOREIGN KEY (student_id) REFERENCES users(student_id) ON DELETE CASCADE,
    FOREIGN KEY (course_code) REFERENCES courses(code) ON DELETE CASCADE,
    INDEX idx_student (student_id),
    INDEX idx_course_section (course_code, section_number),
    INDEX idx_status (enrollment_status)
);

-- Attendance records table
CREATE TABLE attendance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id VARCHAR(20) NOT NULL,
    session_id VARCHAR(100) NOT NULL,
    attendance_status ENUM('present', 'absent', 'late', 'excused') DEFAULT 'present',
    check_in_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    check_out_time TIMESTAMP NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    location_data TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_attendance (student_id, session_id),
    FOREIGN KEY (student_id) REFERENCES users(student_id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES schedules(session_id) ON DELETE CASCADE,
    INDEX idx_student_session (student_id, session_id),
    INDEX idx_session (session_id),
    INDEX idx_status (attendance_status)
);

-- QR codes table for session management
CREATE TABLE qr_codes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    session_id VARCHAR(100) NOT NULL,
    qr_code_data TEXT NOT NULL,
    qr_code_image LONGBLOB,
    is_active BOOLEAN DEFAULT TRUE,
    expires_at TIMESTAMP,
    generated_by VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (session_id) REFERENCES schedules(session_id) ON DELETE CASCADE,
    FOREIGN KEY (generated_by) REFERENCES users(lecturer_id) ON DELETE SET NULL,
    INDEX idx_session (session_id),
    INDEX idx_active (is_active),
    INDEX idx_expires (expires_at)
);

-- Lecturer assignments table
CREATE TABLE lecturer_assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    lecturer_id VARCHAR(20) NOT NULL,
    course_code VARCHAR(20) NOT NULL,
    section_number VARCHAR(10) NOT NULL,
    role ENUM('primary', 'assistant', 'substitute') DEFAULT 'primary',
    semester VARCHAR(20),
    academic_year VARCHAR(10),
    is_active BOOLEAN DEFAULT TRUE,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_assignment (lecturer_id, course_code, section_number, semester, academic_year),
    FOREIGN KEY (lecturer_id) REFERENCES users(lecturer_id) ON DELETE CASCADE,
    FOREIGN KEY (course_code) REFERENCES courses(code) ON DELETE CASCADE,
    INDEX idx_lecturer (lecturer_id),
    INDEX idx_course_section (course_code, section_number)
);

-- System settings table
CREATE TABLE system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_by VARCHAR(20),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_key (setting_key)
);

-- Insert default settings
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('current_semester', 'Fall 2025', 'Current academic semester'),
('current_academic_year', '2025-2026', 'Current academic year'),
('max_enrollment_per_section', '40', 'Maximum students per section'),
('attendance_grace_period', '15', 'Minutes after start time to mark attendance'),
('qr_code_expiry_time', '300', 'QR code validity in seconds'),
('auto_generate_student_id', '1', 'Automatically generate student IDs'),
('auto_generate_lecturer_id', '1', 'Automatically generate lecturer IDs');

-- Create views for common queries

-- View for course enrollment summary
CREATE VIEW course_enrollment_summary AS
SELECT 
    c.code,
    c.name,
    cs.section_number,
    cs.capacity,
    cs.enrolled_count,
    u.first_name AS lecturer_first_name,
    u.last_name AS lecturer_last_name,
    cs.room,
    cs.semester,
    cs.academic_year
FROM courses c
JOIN course_sections cs ON c.code = cs.course_code
LEFT JOIN users u ON cs.lecturer_id = u.lecturer_id
WHERE c.is_active = TRUE AND cs.is_active = TRUE;

-- View for student schedules
CREATE VIEW student_schedules AS
SELECT 
    e.student_id,
    u.first_name,
    u.last_name,
    c.code,
    c.name AS course_name,
    e.section_number,
    s.day_of_week,
    s.start_time,
    s.end_time,
    s.room,
    e.enrollment_status
FROM enrollments e
JOIN users u ON e.student_id = u.student_id
JOIN courses c ON e.course_code = c.code
JOIN schedules s ON e.course_code = s.course_code AND e.section_number = s.section_number
WHERE e.enrollment_status = 'enrolled'
ORDER BY e.student_id, s.day_of_week, s.start_time;

-- View for lecturer schedules
CREATE VIEW lecturer_schedules AS
SELECT 
    la.lecturer_id,
    u.first_name,
    u.last_name,
    c.code,
    c.name AS course_name,
    la.section_number,
    s.day_of_week,
    s.start_time,
    s.end_time,
    s.room,
    s.session_id,
    s.status
FROM lecturer_assignments la
JOIN users u ON la.lecturer_id = u.lecturer_id
JOIN courses c ON la.course_code = c.code
JOIN schedules s ON la.course_code = s.course_code AND la.section_number = s.section_number
WHERE la.is_active = TRUE
ORDER BY la.lecturer_id, s.day_of_week, s.start_time;

-- Triggers for maintaining enrollment counts
DELIMITER //

CREATE TRIGGER update_enrollment_count_insert
    AFTER INSERT ON enrollments
    FOR EACH ROW
BEGIN
    IF NEW.enrollment_status = 'enrolled' THEN
        UPDATE course_sections 
        SET enrolled_count = enrolled_count + 1 
        WHERE course_code = NEW.course_code AND section_number = NEW.section_number;
    END IF;
END//

CREATE TRIGGER update_enrollment_count_update
    AFTER UPDATE ON enrollments
    FOR EACH ROW
BEGIN
    IF OLD.enrollment_status = 'enrolled' AND NEW.enrollment_status != 'enrolled' THEN
        UPDATE course_sections 
        SET enrolled_count = enrolled_count - 1 
        WHERE course_code = NEW.course_code AND section_number = NEW.section_number;
    ELSEIF OLD.enrollment_status != 'enrolled' AND NEW.enrollment_status = 'enrolled' THEN
        UPDATE course_sections 
        SET enrolled_count = enrolled_count + 1 
        WHERE course_code = NEW.course_code AND section_number = NEW.section_number;
    END IF;
END//

CREATE TRIGGER update_enrollment_count_delete
    AFTER DELETE ON enrollments
    FOR EACH ROW
BEGIN
    IF OLD.enrollment_status = 'enrolled' THEN
        UPDATE course_sections 
        SET enrolled_count = enrolled_count - 1 
        WHERE course_code = OLD.course_code AND section_number = OLD.section_number;
    END IF;
END//

DELIMITER ;