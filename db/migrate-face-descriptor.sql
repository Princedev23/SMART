-- ============================================================
-- RUN THIS ONCE in phpMyAdmin > attendance_system > SQL tab
-- Adds the face_descriptor column needed for real face matching
-- ============================================================

USE attendance_system;

ALTER TABLE students
  ADD COLUMN IF NOT EXISTS face_descriptor LONGTEXT NULL
  COMMENT 'JSON array of 128 floats - face embedding from face-api.js';

-- Confirm it worked
DESCRIBE students;
