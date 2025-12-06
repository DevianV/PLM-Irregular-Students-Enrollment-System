-- Migration script to update existing 'Enrolled'/'Not Enrolled' values to 'Enlisted'/'Not Enlisted'
-- Run this script if you have existing data with the old status values

-- Update students table
UPDATE students 
SET enrollment_status = 'Enlisted' 
WHERE enrollment_status = 'Enrolled';

UPDATE students 
SET enrollment_status = 'Not Enlisted' 
WHERE enrollment_status = 'Not Enrolled';

-- Update enrollments table
UPDATE enrollments 
SET status = 'Enlisted' 
WHERE status = 'Enrolled';

-- Verify the changes
SELECT enrollment_status, COUNT(*) as count 
FROM students 
GROUP BY enrollment_status;

SELECT status, COUNT(*) as count 
FROM enrollments 
GROUP BY status;
