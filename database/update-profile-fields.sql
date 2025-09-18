-- Add additional profile fields to users table
-- Run this SQL to enhance user profiles with more information

ALTER TABLE `users` 
ADD COLUMN `full_name` VARCHAR(100) NULL AFTER `email`,
ADD COLUMN `contact_number` VARCHAR(20) NULL AFTER `full_name`,
ADD COLUMN `department` VARCHAR(50) NULL AFTER `contact_number`,
ADD COLUMN `position` VARCHAR(50) NULL AFTER `department`,
ADD COLUMN `bio` TEXT NULL AFTER `position`,
ADD COLUMN `profile_picture` VARCHAR(255) NULL AFTER `bio`;

-- Update existing users with placeholder data if needed
UPDATE `users` SET `full_name` = `username` WHERE `full_name` IS NULL;

-- Add index for better performance on common queries
ALTER TABLE `users` ADD INDEX `idx_department` (`department`);
ALTER TABLE `users` ADD INDEX `idx_user_type` (`user_type`);

-- Optional: Add default profile picture path for existing users
UPDATE `users` SET `profile_picture` = 'default-profile.png' WHERE `profile_picture` IS NULL;

-- Verify the changes
DESCRIBE `users`;

-- Sample data for testing (optional)
-- UPDATE `users` SET 
--   `full_name` = 'Sean Jungay',
--   `contact_number` = '+1234567890',
--   `department` = 'IT',
--   `position` = 'System Administrator',
--   `bio` = 'IT professional with 5+ years of experience'
-- WHERE `username` = 'sean@gmail.com';
