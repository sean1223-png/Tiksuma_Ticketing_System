-- Update users table to support profile fields used in view-it-profile.php and other profile pages
-- This SQL adds missing columns and updates user_type enum to include 'IT' for IT staff

-- First, add the missing profile fields
ALTER TABLE `users`
ADD COLUMN `full_name` VARCHAR(100) NULL AFTER `email`,
ADD COLUMN `contact_number` VARCHAR(20) NULL AFTER `full_name`,
ADD COLUMN `department` VARCHAR(50) NULL AFTER `contact_number`,
ADD COLUMN `position` VARCHAR(50) NULL AFTER `department`,
ADD COLUMN `bio` TEXT NULL AFTER `position`,
ADD COLUMN `profile_picture` VARCHAR(255) NULL AFTER `bio`;

-- Update the user_type enum to include 'IT' for IT staff
ALTER TABLE `users` MODIFY COLUMN `user_type` ENUM('Admin','User','IT') NOT NULL;

-- Update existing users with default values
UPDATE `users` SET
  `full_name` = `username` WHERE `full_name` IS NULL,
  `profile_picture` = 'default-profile.png' WHERE `profile_picture` IS NULL;

-- Update IT staff user_type to 'IT'
UPDATE `users` SET `user_type` = 'IT' WHERE `location` = 'it-staff.php' OR `username` = 'ITStaff@gmail.com';

-- Add indexes for better performance
ALTER TABLE `users` ADD INDEX `idx_department` (`department`);
ALTER TABLE `users` ADD INDEX `idx_user_type` (`user_type`);

-- Sample data for testing (optional)
-- UPDATE `users` SET
--   `full_name` = 'IT Staff Member',
--   `contact_number` = '+1234567890',
--   `department` = 'Information Technology',
--   `position` = 'IT Support Specialist',
--   `bio` = 'Dedicated IT professional providing technical support and system maintenance.'
-- WHERE `username` = 'ITStaff@gmail.com';

-- Verify the changes
DESCRIBE `users`;

-- Check updated user types
SELECT username, user_type, location FROM `users`;
