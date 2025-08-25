-- Add missing fields to users table
USE c_planner;

-- Add phone and birth_date fields to users table
ALTER TABLE users 
ADD COLUMN phone VARCHAR(20) NULL AFTER email,
ADD COLUMN birth_date DATE NULL AFTER phone;

-- Update existing users with default values if needed
UPDATE users SET phone = NULL WHERE phone IS NULL;
UPDATE users SET birth_date = NULL WHERE birth_date IS NULL;
