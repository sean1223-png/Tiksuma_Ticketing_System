-- Enhanced CLARC Feedback System Database Updates

-- Add new columns to feedbacks table
ALTER TABLE `feedbacks` 
ADD COLUMN `subject` VARCHAR(255) NOT NULL AFTER `username`,
ADD COLUMN `category` VARCHAR(50) NOT NULL AFTER `subject`,
ADD COLUMN `rating` INT(1) NOT NULL AFTER `description`,
ADD COLUMN `status` ENUM('New', 'In Review', 'Resolved', 'Closed') DEFAULT 'New' AFTER `attachment`,
ADD COLUMN `admin_response` TEXT DEFAULT NULL AFTER `status`;

-- Create feedback categories table for better categorization
CREATE TABLE IF NOT EXISTS `feedback_categories` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `category_name` VARCHAR(50) NOT NULL,
  `description` TEXT,
  `is_active` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default feedback categories
INSERT INTO `feedback_categories` (`category_name`, `description`) VALUES
('Bug Report', 'Report technical issues or bugs in the system'),
('Feature Request', 'Suggest new features or improvements'),
('General Feedback', 'General comments and suggestions'),
('User Experience', 'Feedback about user interface and experience'),
('Performance Issue', 'Report slow performance or system issues'),
('Other', 'Other types of feedback');

-- Create feedback responses table for admin responses
CREATE TABLE IF NOT EXISTS `feedback_responses` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `feedback_id` INT(11) NOT NULL,
  `admin_username` VARCHAR(100) NOT NULL,
  `response_text` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`feedback_id`) REFERENCES `feedbacks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create feedback tracking table for analytics
CREATE TABLE IF NOT EXISTS `feedback_analytics` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `feedback_id` INT(11) NOT NULL,
  `view_count` INT(11) DEFAULT 0,
  `last_viewed` TIMESTAMP NULL,
  `response_time_hours` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`feedback_id`) REFERENCES `feedbacks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add indexes for better performance
CREATE INDEX idx_feedback_username ON feedbacks(username);
CREATE INDEX idx_feedback_category ON feedbacks(category);
CREATE INDEX idx_feedback_status ON feedbacks(status);
CREATE INDEX idx_feedback_submitted ON feedbacks(submitted_at);

-- Update existing feedbacks with default values
UPDATE feedbacks SET 
  subject = 'General Feedback',
  category = 'General Feedback',
  rating = 4,
  status = 'New'
WHERE subject IS NULL;

-- Add sample data for testing (optional)
INSERT INTO feedbacks (username, subject, category, description, rating, status) VALUES
('sean@gmail.com', 'Great system!', 'General Feedback', 'The ticketing system is very intuitive and easy to use.', 5, 'New'),
('sean@gmail.com', 'Bug in ticket creation', 'Bug Report', 'When creating a ticket with special characters in the subject, it gives an error.', 3, 'In Review');
