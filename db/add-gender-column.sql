-- ============================================================
-- Migration: Add gender column to students and lecturers
-- Run this in phpMyAdmin or via MySQL CLI:
--   mysql -u root -p attendance_system < add-gender-column.sql
-- ============================================================

USE attendance_system;

-- Add gender to students table (if not already present)
ALTER TABLE students
  ADD COLUMN IF NOT EXISTS gender ENUM('Male','Female') DEFAULT NULL
  AFTER department;

-- Add gender to lecturers table (if not already present)
ALTER TABLE lecturers
  ADD COLUMN IF NOT EXISTS gender ENUM('Male','Female') DEFAULT NULL
  AFTER email;

SELECT 'Gender columns added successfully.' AS result;
