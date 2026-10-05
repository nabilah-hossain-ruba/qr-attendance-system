-- Migration: Modify course structure for manual enrollment
-- This migration updates the database to support admin-created courses with manual enrollment

-- First, let's ensure we're using the right database structure
-- The existing schema already has most of what we need, but let's optimize it

-- Update the courses table to be more focused on course definitions
-- (The existing table structure is already good for this)

-- Ensure course_sections table has proper capacity tracking
ALTER TABLE course_sections 
ADD COLUMN IF NOT EXISTS max_capacity INT DEFAULT 40,
ADD COLUMN IF NOT EXISTS current_enrollment INT DEFAULT 0;

-- Update existing enrolled_count to current_enrollment for consistency
UPDATE course_sections SET current_enrollment = enrolled_count WHERE current_enrollment = 0;

-- Create an index for faster course/section queries
CREATE INDEX IF NOT EXISTS idx_course_sections_active ON course_sections(course_code, section_number, is_active);

-- Create a trigger to automatically update current_enrollment when enrollments change
DELIMITER //

DROP TRIGGER IF EXISTS update_section_enrollment_insert//
CREATE TRIGGER update_section_enrollment_insert
    AFTER INSERT ON enrollments
    FOR EACH ROW
BEGIN
    IF NEW.enrollment_status = 'enrolled' THEN
        UPDATE course_sections 
        SET current_enrollment = current_enrollment + 1 
        WHERE course_code = NEW.course_code 
          AND section_number = NEW.section_number;
    END IF;
END//

DROP TRIGGER IF EXISTS update_section_enrollment_update//
CREATE TRIGGER update_section_enrollment_update
    AFTER UPDATE ON enrollments
    FOR EACH ROW
BEGIN
    IF OLD.enrollment_status = 'enrolled' AND NEW.enrollment_status != 'enrolled' THEN
        UPDATE course_sections 
        SET current_enrollment = current_enrollment - 1 
        WHERE course_code = NEW.course_code 
          AND section_number = NEW.section_number;
    ELSEIF OLD.enrollment_status != 'enrolled' AND NEW.enrollment_status = 'enrolled' THEN
        UPDATE course_sections 
        SET current_enrollment = current_enrollment + 1 
        WHERE course_code = NEW.course_code 
          AND section_number = NEW.section_number;
    END IF;
END//

DROP TRIGGER IF EXISTS update_section_enrollment_delete//
CREATE TRIGGER update_section_enrollment_delete
    AFTER DELETE ON enrollments
    FOR EACH ROW
BEGIN
    IF OLD.enrollment_status = 'enrolled' THEN
        UPDATE course_sections 
        SET current_enrollment = current_enrollment - 1 
        WHERE course_code = OLD.course_code 
          AND section_number = OLD.section_number;
    END IF;
END//

DELIMITER ;

-- Create a view for available course sections with enrollment info
CREATE OR REPLACE VIEW available_course_sections AS
SELECT 
    cs.id,
    c.code as course_code,
    c.name as course_name,
    c.description,
    c.credits,
    c.department,
    cs.section_number,
    cs.lecturer_id,
    CONCAT(u.first_name, ' ', u.last_name) as lecturer_name,
    cs.room,
    cs.max_capacity,
    cs.current_enrollment,
    (cs.max_capacity - cs.current_enrollment) as available_spots,
    cs.semester,
    cs.academic_year,
    cs.is_active,
    CASE 
        WHEN cs.current_enrollment >= cs.max_capacity THEN 'Full'
        WHEN cs.current_enrollment >= cs.max_capacity * 0.9 THEN 'Almost Full'
        ELSE 'Available'
    END as enrollment_status
FROM course_sections cs
JOIN courses c ON cs.course_code = c.code
LEFT JOIN users u ON cs.lecturer_id = u.lecturer_id
WHERE cs.is_active = TRUE AND c.is_active = TRUE
ORDER BY c.code, cs.section_number;

-- Insert default system settings for manual enrollment
INSERT IGNORE INTO system_settings (setting_key, setting_value, description) VALUES
('enable_manual_enrollment', '1', 'Enable manual enrollment system instead of CSV import'),
('default_section_capacity', '40', 'Default capacity for new sections'),
('allow_lecturer_self_assignment', '1', 'Allow lecturers to assign themselves to available sections'),
('allow_student_self_enrollment', '1', 'Allow students to enroll themselves in available sections');
