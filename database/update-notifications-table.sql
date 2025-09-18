-- Create notifications table for enhanced notification system
CREATE TABLE IF NOT EXISTS notifications (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT,
    ticket_id INT(11) DEFAULT NULL,
    sender_id INT(11) DEFAULT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY user_id (user_id),
    KEY ticket_id (ticket_id),
    KEY sender_id (sender_id),
    KEY is_read (is_read),
    KEY created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add foreign key constraints
ALTER TABLE notifications
    ADD CONSTRAINT fk_notifications_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_notifications_ticket_id FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_notifications_sender_id FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE SET NULL;

-- Insert sample notification types
INSERT IGNORE INTO priority_levels (id, level_name) VALUES 
(4, 'Urgent'),
(5, 'Critical');

-- Update ticket statuses if needed
INSERT IGNORE INTO ticket_statuses (id, status_name) VALUES 
(5, 'Pending'),
(6, 'Reopened'),
(7, 'Escalated'),
(8, 'On Hold');
