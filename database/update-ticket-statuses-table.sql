-- Add "Pending" status to ticket_statuses table if it doesn't exist
INSERT INTO ticket_statuses (status_name) VALUES ('Pending')
ON DUPLICATE KEY UPDATE status_name = status_name;
