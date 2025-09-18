-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 18, 2025 at 02:43 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tiksumadb`
--

-- --------------------------------------------------------

--
-- Table structure for table `feedbacks`
--

CREATE TABLE `feedbacks` (
  `id` int(11) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `ticket_id` int(11) DEFAULT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `title`, `message`, `ticket_id`, `sender_id`, `is_read`, `created_at`) VALUES
(1, 1, 'new_ticket', 'New Ticket Submitted', 'User sean@gmail.com submitted a new ticket: no internet connection (Priority: High)', 30, 2, 0, '2025-09-17 15:49:40'),
(2, 1, 'new_ticket', 'New Ticket Submitted', 'User sean@gmail.com submitted a new ticket: Technical Issue (Priority: Low)', 1, 2, 0, '2025-09-17 15:55:45'),
(3, 1, 'new_ticket', 'New Ticket Submitted', 'User sean@gmail.com submitted a new ticket: Account/Access Issue (Priority: Medium)', 2, 2, 0, '2025-09-17 15:57:38'),
(4, 1, 'new_ticket', 'New Ticket Submitted', 'User sean@gmail.com submitted a new ticket: Incident (Priority: High)', 3, 2, 0, '2025-09-17 16:01:22'),
(5, 1, 'new_ticket', 'New Ticket Submitted', 'User sean@gmail.com submitted a new ticket: Billing/Payment Issue (Priority: Medium)', 4, 2, 0, '2025-09-17 16:03:27');

-- --------------------------------------------------------

--
-- Table structure for table `priority_levels`
--

CREATE TABLE `priority_levels` (
  `id` int(11) NOT NULL,
  `level_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `priority_levels`
--

INSERT INTO `priority_levels` (`id`, `level_name`) VALUES
(1, 'Low'),
(2, 'Medium'),
(3, 'High');

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `ticket_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `priority_id` int(11) DEFAULT NULL,
  `status_id` int(11) DEFAULT NULL,
  `assigned_to` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_seen_by_it` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tickets`
--

INSERT INTO `tickets` (`ticket_id`, `username`, `subject`, `priority_id`, `status_id`, `assigned_to`, `description`, `attachment`, `created_at`, `is_seen_by_it`) VALUES
(1, 'sean@gmail.com', 'Technical Issue', 1, 1, 'ITStaff@gmail.com', 'problems with software, hardware, or system errors', NULL, '2025-09-17 15:55:45', 0),
(2, 'sean@gmail.com', 'Account/Access Issue', 2, 1, 'ITStaff@gmail.com', 'login errors, password resets, permission requests', NULL, '2025-09-17 15:57:38', 0),
(3, 'sean@gmail.com', 'Incident', 3, 1, 'ITStaff@gmail.com', 'unexpected system bug, service interruption', NULL, '2025-09-17 16:01:22', 0),
(4, 'sean@gmail.com', 'Billing/Payment Issue', 2, 1, 'ITStaff@gmail.com', 'finance-related concerns.', NULL, '2025-09-17 16:03:27', 0);

-- --------------------------------------------------------

--
-- Table structure for table `ticket_notes`
--

CREATE TABLE `ticket_notes` (
  `note_id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `note` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ticket_notes`
--

INSERT INTO `ticket_notes` (`note_id`, `ticket_id`, `username`, `note`, `created_at`) VALUES
(1, 23, 'ITStaff@gmail.com', 'why this hi the priority id high', '2025-09-12 12:49:56'),
(2, 25, 'ITStaff@gmail.com', 'can  pending for an hours i have appointment', '2025-09-12 13:14:30'),
(3, 25, 'ITStaff@gmail.com', 'sorry i back', '2025-09-12 13:14:42'),
(4, 25, 'ITStaff@gmail.com', 'sorry', '2025-09-17 15:26:08');

-- --------------------------------------------------------

--
-- Table structure for table `ticket_statuses`
--

CREATE TABLE `ticket_statuses` (
  `id` int(11) NOT NULL,
  `status_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ticket_statuses`
--

INSERT INTO `ticket_statuses` (`id`, `status_name`) VALUES
(1, 'Open'),
(2, 'In Progress'),
(3, 'Resolved'),
(4, 'Closed'),
(5, 'Pending');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `department` varchar(50) DEFAULT NULL,
  `position` varchar(50) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `user_type` enum('Admin','User') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `location` varchar(100) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `full_name`, `contact_number`, `department`, `position`, `bio`, `password`, `user_type`, `created_at`, `location`, `profile_picture`) VALUES
(1, 'ITStaff@gmail.com', 'ITStaff@gmail.com', 'ITstaff', '', NULL, NULL, NULL, '$2y$10$GOI0DECrDJo4iItan08bN.MFNki1wNAhw20zuD/AGtYMCWrGTuQRS', 'Admin', '2025-07-22 12:36:07', 'it-staff.php', 'uploads/ITStaff@gmail.com_profile_1756764949.png'),
(2, 'sean@gmail.com', 'sean@gmail.com', 'sean', '', NULL, NULL, NULL, '$2y$10$UuGRE.JeSUkgxE5OGwWD/O12MCdzJFqcvBLw70803/qsCd3WFcIeO', 'User', '2025-07-22 12:34:59', 'clarendon-staff.php', 'uploads/sean@gmail.com_profile_1756765028.jpg');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `ticket_id` (`ticket_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `is_read` (`is_read`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `priority_levels`
--
ALTER TABLE `priority_levels`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`ticket_id`),
  ADD KEY `priority_id` (`priority_id`),
  ADD KEY `status_id` (`status_id`);

--
-- Indexes for table `ticket_notes`
--
ALTER TABLE `ticket_notes`
  ADD PRIMARY KEY (`note_id`),
  ADD KEY `fk_ticket_notes_ticket` (`ticket_id`);

--
-- Indexes for table `ticket_statuses`
--
ALTER TABLE `ticket_statuses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `priority_levels`
--
ALTER TABLE `priority_levels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `ticket_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `ticket_notes`
--
ALTER TABLE `ticket_notes`
  MODIFY `note_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `ticket_statuses`
--
ALTER TABLE `ticket_statuses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`priority_id`) REFERENCES `priority_levels` (`id`),
  ADD CONSTRAINT `tickets_ibfk_2` FOREIGN KEY (`status_id`) REFERENCES `ticket_statuses` (`id`);

--
-- Constraints for table `ticket_notes`
--
ALTER TABLE `ticket_notes`
  ADD CONSTRAINT `fk_ticket_notes_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`ticket_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
