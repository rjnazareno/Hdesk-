-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 05, 2025 at 06:16 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ithelp`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `color` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `icon`, `color`, `is_active`, `created_at`) VALUES
(1, 'Hardware', 'Hardware related issues (computers, printers, phones)', 'desktop', '#3B82F6', 1, '2025-10-08 03:37:59'),
(2, 'Software', 'Software installation and troubleshooting', 'code', '#10B981', 1, '2025-10-08 03:37:59'),
(3, 'Network', 'Network connectivity and access issues', 'wifi', '#F59E0B', 1, '2025-10-08 03:37:59'),
(4, 'Email', 'Email account and configuration issues', 'mail', '#EF4444', 1, '2025-10-08 03:37:59'),
(5, 'Access', 'System access and permission requests', 'key', '#8B5CF6', 1, '2025-10-08 03:37:59'),
(6, 'Other', 'Other IT support requests', 'help-circle', '#6B7280', 1, '2025-10-08 03:37:59'),
(7, 'Harley', NULL, 'fa-bug', '#6b7280', 1, '2025-10-13 01:38:28');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(50) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `personal_email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `fname` varchar(100) DEFAULT NULL,
  `lname` varchar(100) DEFAULT NULL,
  `company` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `official_sched` int(11) DEFAULT NULL,
  `role` enum('employee','manager','supervisor') DEFAULT 'employee',
  `status` enum('active','inactive','terminated') DEFAULT 'active',
  `profile_picture` varchar(255) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `employee_id`, `username`, `email`, `personal_email`, `password`, `fname`, `lname`, `company`, `position`, `contact`, `official_sched`, `role`, `status`, `profile_picture`, `profile_image`, `created_at`) VALUES
(1, '1', 'john.doe', 'john.doe@company.com', NULL, '$2y$10$N0waCj/5zu6MKllawHUlTOc9K5evXFsLsyuv4V0TXBjk6/cncSGUq', 'John', 'Doe', 'Company Inc', 'Sales Representative', '1234567890', NULL, 'employee', 'active', NULL, NULL, '2025-10-08 03:37:59'),
(2, 'TEST001', 'alice.johnson', 'alice.johnson@company.com', NULL, '$2y$10$cY33BqpnweebC3RzzqT6H.rhhy8RQnRg1BYc1M6GyRhqtZ/xKe.mi', 'Alice', 'Johnson', 'Marketing', 'Marketing Manager', '555-0101', NULL, 'employee', 'active', NULL, NULL, '2025-11-05 01:30:28'),
(3, 'TEST002', 'bob.smith', 'bob.smith@company.com', NULL, '$2y$10$IDEFEi6nrzrC4eqMggs5yOQ2hxY/XqXsjboGmHa6lBhdI.tvAVxca', 'Bob', 'Smith', 'Sales', 'Sales Representative', '555-0102', NULL, 'employee', 'active', NULL, NULL, '2025-11-05 01:30:28'),
(4, 'TEST003', 'carol.williams', 'carol.williams@company.com', NULL, '$2y$10$wuJijWbdHuJOVi8CgeKwBuuEzAX1q898jnpKEbm3dBPN/GgWOgIgi', 'Carol', 'Williams', 'IT', 'Software Developer', '555-0103', NULL, 'employee', 'active', NULL, NULL, '2025-11-05 01:30:28');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `type` enum('ticket_assigned','ticket_updated','ticket_resolved','ticket_created','comment_added','status_changed','priority_changed') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `ticket_id` int(11) DEFAULT NULL,
  `related_user_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `employee_id`, `type`, `title`, `message`, `ticket_id`, `related_user_id`, `is_read`, `created_at`) VALUES
(3, 2, NULL, 'ticket_assigned', 'Ticket Assigned to You', 'Ticket #3 - Network issue has been assigned', 3, NULL, 1, '2025-10-09 01:50:54'),
(4, 2, NULL, 'comment_added', 'Employee Replied', 'Employee added a comment to ticket #3', 3, NULL, 1, '2025-10-09 01:50:54'),
(5, 100, NULL, 'comment_added', 'IT Staff Responded', 'Mahfuzul Islam replied to your ticket', 1, NULL, 0, '2025-10-09 01:52:14'),
(6, 100, NULL, 'status_changed', 'Status Update', 'Your ticket is now: In Progress', 1, NULL, 0, '2025-10-09 01:52:14'),
(7, 100, NULL, 'ticket_resolved', 'Ticket Resolved', 'Your network issue has been resolved', 1, NULL, 1, '2025-10-09 01:52:14'),
(12, 1, NULL, 'ticket_created', 'New Ticket Submitted', 'A new support ticket requires attention', 1, NULL, 1, '2025-10-09 07:45:43'),
(13, 1, NULL, 'ticket_created', 'New Ticket Submitted', 'Employee submitted a new urgent ticket', 2, NULL, 1, '2025-10-09 05:45:43'),
(14, 1, NULL, 'ticket_updated', 'Ticket Updated by Employee', 'Employee added more information to ticket #1', 1, NULL, 1, '2025-10-09 03:45:43'),
(15, 1, NULL, 'ticket_assigned', 'Ticket Auto-Assigned', 'Ticket #1 was assigned to IT Staff', 1, 2, 1, '2025-10-08 09:45:43'),
(16, 2, NULL, 'ticket_assigned', 'New Ticket Assigned', 'Ticket #1 \"Laptop WiFi Issue\" has been assigned to you', 1, 1, 1, '2025-10-09 06:45:43'),
(17, 2, NULL, 'ticket_assigned', 'New Ticket Assigned', 'Urgent ticket #2 assigned to you', 2, 1, 1, '2025-10-09 04:45:43'),
(18, 2, NULL, 'comment_added', 'Employee Response', 'Employee responded to your solution on ticket #1', 1, NULL, 1, '2025-10-08 09:45:43'),
(24, 0, 1, 'ticket_created', 'Ticket Submitted Successfully', 'Your support ticket has been received and will be reviewed shortly', 1, NULL, 1, '2025-10-07 10:03:21'),
(25, 0, 1, 'ticket_assigned', 'Ticket Assigned to Support Team', 'Your ticket has been assigned to our IT support team', 1, 2, 1, '2025-10-08 10:03:21'),
(26, 0, 1, 'status_changed', 'Ticket Status Updated', 'Your ticket status changed to: In Progress', 1, 2, 1, '2025-10-09 02:03:21'),
(27, 0, 1, 'comment_added', 'New Comment from IT Support', 'IT Staff replied to your ticket with more information', 1, 2, 1, '2025-10-09 06:03:21'),
(28, 0, 1, 'ticket_resolved', 'Ticket Resolved', 'Your support ticket has been marked as resolved', 1, 2, 1, '2025-10-09 09:03:21'),
(29, NULL, 1, 'ticket_created', 'Ticket Created Successfully', 'Your ticket #TKT-2025-6077 has been submitted and is awaiting review', 10, NULL, 1, '2025-10-14 07:32:31'),
(31, 1, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-6077: Ced', 10, NULL, 1, '2025-10-14 07:32:31'),
(32, 2, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-6077: Ced', 10, NULL, 1, '2025-10-14 07:32:31'),
(33, NULL, 1, 'status_changed', 'Ticket Status Updated', 'Your ticket #TKT-2025-6077 status changed to: In Progress', 10, 1, 1, '2025-10-14 07:33:13'),
(34, 4, NULL, 'ticket_assigned', 'Ticket Assigned to You', 'You have been assigned to ticket #TKT-2025-6077: Ced', 10, 1, 1, '2025-10-14 07:33:13'),
(35, NULL, 1, 'ticket_created', 'Ticket Created Successfully', 'Your ticket #TKT-2025-4169 has been submitted and is awaiting review', 11, NULL, 1, '2025-10-14 09:06:40'),
(36, 4, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-4169: terst', 11, NULL, 1, '2025-10-14 09:06:40'),
(37, 1, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-4169: terst', 11, NULL, 1, '2025-10-14 09:06:40'),
(38, 2, NULL, 'ticket_created', 'New Ticket Submitted', 'New ticket #TKT-2025-4169: terst', 11, NULL, 1, '2025-10-14 09:06:40'),
(39, NULL, 1, 'ticket_created', 'Ticket Created for You', 'A support ticket #TKT-2025-4567 has been created on your behalf by admin', 13, 1, 1, '2025-10-16 02:35:40'),
(40, 4, NULL, 'ticket_created', 'New Ticket Created', 'Admin created ticket #TKT-2025-4567 for employee', 13, 1, 1, '2025-10-16 02:35:40'),
(41, 2, NULL, 'ticket_created', 'New Ticket Created', 'Admin created ticket #TKT-2025-4567 for employee', 13, 1, 1, '2025-10-16 02:35:40'),
(42, 2, NULL, 'ticket_assigned', 'Ticket Assigned to You', 'You have been assigned to ticket #TKT-2025-4567: Harley Problem', 13, 1, 1, '2025-10-16 02:36:36'),
(43, NULL, 1, 'ticket_resolved', 'Ticket Status Updated', 'Your ticket #TKT-2025-4567 status changed to: Closed', 13, 1, 1, '2025-10-16 02:44:24'),
(44, 2, NULL, 'ticket_assigned', 'Ticket Assigned to You', 'You have been assigned to ticket #TKT-2025-4567: Harley Problem', 13, 1, 1, '2025-10-16 02:44:24'),
(45, NULL, 1, 'ticket_resolved', 'Ticket Status Updated', 'Your ticket #TKT-2025-9752 status changed to: Closed', 12, 1, 0, '2025-10-16 08:39:20'),
(46, 2, NULL, 'ticket_assigned', 'Ticket Assigned to You', 'You have been assigned to ticket #TKT-2025-9752: Test', 12, 1, 1, '2025-10-16 08:39:20'),
(47, NULL, 1, 'ticket_resolved', 'Ticket Status Updated', 'Your ticket #TKT-2025-9752 status changed to: Resolved', 12, 1, 0, '2025-10-16 08:39:40'),
(48, 2, NULL, 'ticket_assigned', 'Ticket Assigned to You', 'You have been assigned to ticket #TKT-2025-9752: Test', 12, 1, 1, '2025-10-16 08:39:40'),
(49, NULL, 1, 'ticket_resolved', 'Ticket Status Updated', 'Your ticket #TKT-2025-9752 status changed to: Closed', 12, 2, 0, '2025-10-16 09:43:49'),
(50, 2, NULL, 'ticket_assigned', 'Ticket Assigned to You', 'You have been assigned to ticket #TKT-2025-9752: Test', 12, 2, 1, '2025-10-16 09:43:49'),
(51, NULL, 1, 'comment_added', 'New Comment on Your Ticket', 'IT staff added a comment on ticket #TKT-2025-003', 3, 2, 0, '2025-10-16 09:44:05'),
(52, NULL, 1, 'ticket_created', 'Ticket Created for You', 'A support ticket #TKT-2025-7354 has been created on your behalf by admin', 16, 4, 0, '2025-10-17 07:58:42'),
(53, 2, NULL, 'ticket_assigned', 'New Ticket Assigned to You', 'You have been assigned to ticket #TKT-2025-7354: tesrt434343', 16, 4, 1, '2025-10-17 07:58:42'),
(54, 1, NULL, 'ticket_created', 'New Ticket Created', 'Admin created ticket #TKT-2025-7354 for employee', 16, 4, 0, '2025-10-17 07:58:42'),
(55, NULL, 1, 'comment_added', 'New Comment on Your Ticket', 'IT staff added a comment on ticket #TKT-2025-7354', 16, 2, 0, '2025-10-17 07:59:23'),
(56, NULL, 1, 'comment_added', 'New Comment on Your Ticket', 'IT staff added a comment on ticket #TKT-2025-7354', 16, 2, 0, '2025-10-17 07:59:31'),
(57, NULL, 1, 'comment_added', 'New Comment on Your Ticket', 'IT staff added a comment on ticket #TKT-2025-7354', 16, 2, 0, '2025-10-17 08:33:08'),
(58, NULL, 1, 'ticket_resolved', 'Ticket Status Updated', 'Your ticket #TKT-2025-7354 status changed to: Resolved', 16, 2, 0, '2025-10-17 08:35:17'),
(59, 2, NULL, 'ticket_assigned', 'Ticket Assigned to You', 'You have been assigned to ticket #TKT-2025-7354: tesrt434343', 16, 2, 1, '2025-10-17 08:35:17'),
(60, NULL, 1, 'ticket_resolved', 'Ticket Status Updated', 'Your ticket #TKT-2025-7354 status changed to: Closed', 16, 2, 0, '2025-10-17 08:35:23'),
(61, 2, NULL, 'ticket_assigned', 'Ticket Assigned to You', 'You have been assigned to ticket #TKT-2025-7354: tesrt434343', 16, 2, 1, '2025-10-17 08:35:23'),
(62, NULL, 1, 'ticket_resolved', 'Ticket Status Updated', 'Your ticket #TKT-2025-9398 status changed to: Closed', 15, 2, 0, '2025-10-17 08:43:33'),
(63, 2, NULL, 'ticket_assigned', 'Ticket Assigned to You', 'You have been assigned to ticket #TKT-2025-9398: URGENT TEST - Printer not working', 15, 2, 1, '2025-10-17 08:43:33'),
(64, NULL, 1, 'comment_added', 'New Comment on Your Ticket', 'IT staff added a comment on ticket #TKT-2025-11-03-01591', 17, 2, 0, '2025-11-03 02:01:16'),
(65, NULL, 1, 'ticket_resolved', 'Ticket Status Updated', 'Your ticket #TKT-2025-11-03-01591 status changed to: Closed', 17, 2, 0, '2025-11-03 02:01:29'),
(66, 2, NULL, 'ticket_assigned', 'Ticket Assigned to You', 'You have been assigned to ticket #TKT-2025-11-03-01591: 🧪 SLA TEST - Quick Response Test', 17, 2, 0, '2025-11-03 02:01:29'),
(67, 2, NULL, 'ticket_assigned', 'Ticket Assigned to You', 'You have been assigned to ticket #TKT-2025-11-03-01591: 🧪 SLA TEST - Quick Response Test', 17, 2, 0, '2025-11-03 02:01:35'),
(68, NULL, 1, 'ticket_created', 'Ticket Created for You', 'A support ticket #TKT-2025-9243 has been created on your behalf by admin', 22, 4, 0, '2025-11-03 02:04:08'),
(69, 2, NULL, 'ticket_assigned', 'New Ticket Assigned to You', 'You have been assigned to ticket #TKT-2025-9243: test notif', 22, 4, 0, '2025-11-03 02:04:08'),
(70, 1, NULL, 'ticket_created', 'New Ticket Created', 'Admin created ticket #TKT-2025-9243 for employee', 22, 4, 0, '2025-11-03 02:04:08'),
(71, NULL, 1, 'ticket_resolved', 'Ticket Status Updated', 'Your ticket #TKT-2025-9243 status changed to: Closed', 22, 4, 0, '2025-11-03 02:18:43'),
(72, 2, NULL, 'ticket_assigned', 'Ticket Assigned to You', 'You have been assigned to ticket #TKT-2025-9243: test notif', 22, 4, 0, '2025-11-03 02:18:43');

-- --------------------------------------------------------

--
-- Table structure for table `sla_breaches`
--

CREATE TABLE `sla_breaches` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `sla_tracking_id` int(11) NOT NULL,
  `breach_type` enum('response','resolution') NOT NULL,
  `target_time` datetime NOT NULL COMMENT 'When it should have been completed',
  `actual_time` datetime NOT NULL COMMENT 'When it was actually completed',
  `delay_minutes` int(11) NOT NULL COMMENT 'How many minutes late',
  `notified` tinyint(1) DEFAULT 0 COMMENT 'Has notification been sent',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sla_breaches`
--

INSERT INTO `sla_breaches` (`id`, `ticket_id`, `sla_tracking_id`, `breach_type`, `target_time`, `actual_time`, `delay_minutes`, `notified`, `created_at`) VALUES
(1, 5, 5, 'response', '2025-10-16 08:30:00', '2025-10-16 15:53:47', 444, 0, '2025-10-16 07:53:47'),
(2, 6, 6, 'response', '2025-10-16 08:08:47', '2025-10-16 15:53:47', 465, 0, '2025-10-16 07:53:47'),
(3, 10, 10, 'response', '2025-10-16 08:08:47', '2025-10-16 15:53:47', 465, 0, '2025-10-16 07:53:47'),
(4, 13, 13, 'resolution', '2025-10-16 11:53:47', '2025-10-16 15:53:47', 240, 0, '2025-10-16 07:53:47'),
(5, 12, 12, 'response', '2025-10-16 08:08:47', '2025-10-16 16:39:20', 511, 0, '2025-10-16 08:39:20'),
(6, 12, 12, 'resolution', '2025-10-16 11:53:47', '2025-10-16 16:39:20', 286, 0, '2025-10-16 08:39:20'),
(9, 16, 17, 'response', '2025-10-17 16:13:42', '2025-10-17 16:33:08', 19, 0, '2025-10-17 08:33:08'),
(10, 16, 17, 'response', '2025-10-17 16:13:42', '2025-10-17 16:33:08', 19, 0, '2025-10-17 08:35:17'),
(11, 15, 18, 'resolution', '2025-10-16 16:03:18', '2025-10-17 16:43:33', 1480, 0, '2025-10-17 08:43:33');

-- --------------------------------------------------------

--
-- Table structure for table `sla_policies`
--

CREATE TABLE `sla_policies` (
  `id` int(11) NOT NULL,
  `priority` enum('low','medium','high','urgent') NOT NULL,
  `response_time` int(11) NOT NULL COMMENT 'Minutes until first response required',
  `resolution_time` int(11) NOT NULL COMMENT 'Minutes until resolution required',
  `is_business_hours` tinyint(1) DEFAULT 1 COMMENT '1=business hours only, 0=24/7 calculation',
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'Enable/disable this policy',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sla_policies`
--

INSERT INTO `sla_policies` (`id`, `priority`, `response_time`, `resolution_time`, `is_business_hours`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'urgent', 15, 240, 0, 1, '2025-10-16 07:36:20', '2025-10-16 07:36:20'),
(2, 'high', 30, 480, 1, 1, '2025-10-16 07:36:20', '2025-10-16 07:36:20'),
(3, 'medium', 120, 1440, 1, 1, '2025-10-16 07:36:20', '2025-10-16 07:36:20'),
(4, 'low', 480, 2880, 1, 1, '2025-10-16 07:36:20', '2025-10-16 07:36:20');

-- --------------------------------------------------------

--
-- Table structure for table `sla_tracking`
--

CREATE TABLE `sla_tracking` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `sla_policy_id` int(11) NOT NULL,
  `response_due_at` datetime NOT NULL COMMENT 'When first response is due',
  `first_response_at` datetime DEFAULT NULL COMMENT 'When IT staff first responded',
  `response_sla_status` enum('met','at_risk','breached','pending') DEFAULT 'pending',
  `response_time_minutes` int(11) DEFAULT NULL COMMENT 'Actual response time in minutes',
  `resolution_due_at` datetime NOT NULL COMMENT 'When resolution is due',
  `resolved_at` datetime DEFAULT NULL COMMENT 'When ticket was resolved',
  `resolution_sla_status` enum('met','at_risk','breached','pending') DEFAULT 'pending',
  `resolution_time_minutes` int(11) DEFAULT NULL COMMENT 'Actual resolution time in minutes',
  `is_paused` tinyint(1) DEFAULT 0 COMMENT 'Is SLA currently paused',
  `paused_at` datetime DEFAULT NULL COMMENT 'When SLA was paused',
  `pause_reason` varchar(255) DEFAULT NULL COMMENT 'Why SLA was paused',
  `total_pause_minutes` int(11) DEFAULT 0 COMMENT 'Total time SLA has been paused',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sla_tracking`
--

INSERT INTO `sla_tracking` (`id`, `ticket_id`, `sla_policy_id`, `response_due_at`, `first_response_at`, `response_sla_status`, `response_time_minutes`, `resolution_due_at`, `resolved_at`, `resolution_sla_status`, `resolution_time_minutes`, `is_paused`, `paused_at`, `pause_reason`, `total_pause_minutes`, `created_at`, `updated_at`) VALUES
(1, 1, 2, '2025-10-16 08:30:00', NULL, 'pending', NULL, '2025-10-16 16:00:00', '2025-10-16 15:53:47', 'met', 11775, 0, NULL, NULL, 0, '2025-10-16 07:53:47', '2025-10-16 07:53:47'),
(2, 2, 3, '2025-10-16 10:00:00', NULL, 'pending', NULL, '2025-10-20 14:00:00', '2025-10-16 15:53:47', 'met', 11775, 0, NULL, NULL, 0, '2025-10-16 07:53:47', '2025-10-16 07:53:47'),
(3, 3, 3, '2025-10-16 10:00:00', NULL, 'pending', NULL, '2025-10-20 14:00:00', '2025-10-16 15:53:47', 'met', 11775, 0, NULL, NULL, 0, '2025-10-16 07:53:47', '2025-10-16 07:53:47'),
(4, 4, 4, '2025-10-16 16:00:00', NULL, 'pending', NULL, '2025-10-23 11:00:00', '2025-10-16 15:53:47', 'met', 11775, 0, NULL, NULL, 0, '2025-10-16 07:53:47', '2025-10-16 07:53:47'),
(5, 5, 2, '2025-10-16 08:30:00', '2025-10-16 15:53:47', 'breached', 10401, '2025-10-16 16:00:00', NULL, 'pending', NULL, 0, NULL, NULL, 0, '2025-10-16 07:53:47', '2025-10-16 07:53:47'),
(6, 6, 1, '2025-10-16 08:08:47', '2025-10-16 15:53:47', 'breached', 9975, '2025-10-16 11:53:47', NULL, 'pending', NULL, 0, NULL, NULL, 0, '2025-10-16 07:53:47', '2025-10-16 07:53:47'),
(7, 7, 1, '2025-10-16 08:08:47', NULL, 'pending', NULL, '2025-10-16 11:53:47', NULL, 'pending', NULL, 0, NULL, NULL, 0, '2025-10-16 07:53:47', '2025-10-16 07:53:47'),
(8, 8, 1, '2025-10-16 08:08:47', NULL, 'pending', NULL, '2025-10-16 11:53:47', NULL, 'pending', NULL, 0, NULL, NULL, 0, '2025-10-16 07:53:47', '2025-10-16 07:53:47'),
(9, 9, 2, '2025-10-16 08:30:00', NULL, 'pending', NULL, '2025-10-16 16:00:00', NULL, 'pending', NULL, 0, NULL, NULL, 0, '2025-10-16 07:53:47', '2025-10-16 07:53:47'),
(10, 10, 1, '2025-10-16 08:08:47', '2025-10-16 15:53:47', 'breached', 2901, '2025-10-16 11:53:47', NULL, 'pending', NULL, 0, NULL, NULL, 0, '2025-10-16 07:53:47', '2025-10-16 07:53:47'),
(11, 11, 1, '2025-10-16 08:08:47', NULL, 'pending', NULL, '2025-10-16 11:53:47', NULL, 'pending', NULL, 0, NULL, NULL, 0, '2025-10-16 07:53:47', '2025-10-16 07:53:47'),
(12, 12, 1, '2025-10-16 08:08:47', '2025-10-16 16:39:20', 'breached', 373, '2025-10-16 11:53:47', '2025-10-16 16:39:20', 'breached', 373, 0, NULL, NULL, 0, '2025-10-16 07:53:47', '2025-10-16 08:39:20'),
(13, 13, 1, '2025-10-16 08:08:47', NULL, 'pending', NULL, '2025-10-16 11:53:47', '2025-10-16 15:53:47', 'breached', 318, 0, NULL, NULL, 0, '2025-10-16 07:53:47', '2025-10-16 07:53:47'),
(17, 16, 1, '2025-10-17 16:13:42', '2025-10-17 16:33:08', 'breached', 34, '2025-10-17 19:58:42', '2025-10-17 16:35:17', 'met', 36, 0, NULL, NULL, 0, '2025-10-17 08:31:51', '2025-10-17 08:35:17'),
(18, 15, 1, '2025-10-16 12:18:18', NULL, 'pending', NULL, '2025-10-16 16:03:18', '2025-10-17 16:43:33', 'breached', 1720, 0, NULL, NULL, 0, '2025-10-17 08:31:51', '2025-10-17 08:43:33'),
(19, 17, 1, '2025-11-03 10:14:19', '2025-11-03 10:01:16', 'met', 1, '2025-11-03 13:59:19', '2025-11-03 10:01:29', 'met', 2, 0, NULL, NULL, 0, '2025-11-03 01:59:19', '2025-11-03 02:01:29'),
(20, 21, 2, '2025-11-03 10:33:48', NULL, 'pending', NULL, '2025-11-04 09:04:00', NULL, 'pending', NULL, 0, NULL, NULL, 0, '2025-11-03 02:03:48', '2025-11-03 02:03:48'),
(21, 22, 2, '2025-11-03 10:34:08', '2025-11-03 10:18:43', 'met', 14, '2025-11-04 09:04:00', '2025-11-03 10:18:43', 'met', 14, 0, NULL, NULL, 0, '2025-11-03 02:04:08', '2025-11-03 02:18:43');

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `id` int(11) NOT NULL,
  `ticket_number` varchar(20) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `category_id` int(11) NOT NULL,
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `sla_status` enum('met','at_risk','breached','pending','none') DEFAULT 'pending' COMMENT 'Overall SLA status for quick filtering',
  `status` enum('pending','open','in_progress','resolved','closed') NOT NULL DEFAULT 'pending',
  `submitter_id` int(11) NOT NULL,
  `submitter_type` enum('employee','user') NOT NULL DEFAULT 'employee',
  `assigned_to` int(11) DEFAULT NULL,
  `resolution` text DEFAULT NULL,
  `attachments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ticket_activity`
--

CREATE TABLE `ticket_activity` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('employee','user') NOT NULL DEFAULT 'user',
  `action_type` varchar(50) NOT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ticket_activity`
--

INSERT INTO `ticket_activity` (`id`, `ticket_id`, `user_id`, `user_type`, `action_type`, `old_value`, `new_value`, `comment`, `created_at`) VALUES
(1, 1, 1, 'employee', 'created', NULL, 'open', 'Ticket created', '2025-10-08 03:37:59'),
(2, 1, 2, 'user', 'assigned', NULL, 'Mahfuzul Islam', 'Ticket assigned to IT staff', '2025-10-08 03:37:59'),
(3, 2, 1, 'employee', 'created', NULL, 'pending', 'Ticket created', '2025-10-08 03:37:59'),
(4, 3, 1, 'employee', 'created', NULL, 'pending', 'Ticket created', '2025-10-08 03:37:59'),
(5, 3, 2, 'user', 'status_change', NULL, 'in_progress', 'Started working on this issue', '2025-10-08 03:37:59'),
(6, 4, 1, 'employee', 'created', NULL, 'pending', 'Ticket created', '2025-10-08 03:37:59'),
(7, 4, 2, 'user', 'status_change', NULL, 'resolved', 'Email configured successfully', '2025-10-08 03:37:59'),
(8, 1, 1, 'user', 'comment', NULL, NULL, 'test', '2025-10-08 03:57:13'),
(9, 1, 1, 'user', 'status_change', 'open', 'pending', 'Status changed from open to pending', '2025-10-08 03:57:20'),
(10, 1, 1, 'user', 'comment', NULL, NULL, 'test', '2025-10-08 04:47:03'),
(11, 4, 1, 'user', 'status_change', 'resolved', 'closed', 'Status changed from resolved to closed', '2025-10-08 04:54:15'),
(12, 4, 1, 'user', 'status_change', 'resolved', 'closed', 'Status changed from resolved to closed', '2025-10-08 04:57:11'),
(13, 4, 1, 'user', 'assigned', NULL, 'Cedrick Arnigo', 'Ticket assigned to Cedrick Arnigo', '2025-10-08 04:57:11'),
(14, 3, 1, 'user', 'status_change', 'in_progress', 'closed', 'Status changed from in_progress to closed', '2025-10-08 04:57:42'),
(15, 2, 1, 'user', 'status_change', 'pending', 'closed', 'Status changed from pending to closed', '2025-10-08 04:57:55'),
(16, 2, 1, 'user', 'assigned', NULL, 'Cedrick Arnigo', 'Ticket assigned to Cedrick Arnigo', '2025-10-08 04:57:55'),
(17, 1, 1, 'user', 'status_change', 'open', 'closed', 'Status changed from open to closed', '2025-10-08 04:58:15'),
(18, 1, 1, 'user', 'assigned', NULL, 'Cedrick Arnigo', 'Ticket assigned to Cedrick Arnigo', '2025-10-08 04:58:15'),
(19, 1, 1, 'employee', 'comment', NULL, NULL, 'test', '2025-10-08 04:58:42'),
(20, 1, 1, 'employee', 'comment', NULL, NULL, 'ced', '2025-10-08 04:58:52'),
(21, 1, 1, 'employee', 'comment', NULL, NULL, 'ced', '2025-10-08 04:58:57'),
(22, 5, 1, 'employee', 'created', NULL, 'pending', 'Ticket created', '2025-10-09 02:32:42'),
(23, 5, 1, 'user', 'comment', NULL, NULL, 'test', '2025-10-09 02:34:05'),
(24, 5, 1, 'user', 'status_change', 'pending', 'in_progress', 'Status changed from pending to in_progress', '2025-10-09 02:34:19'),
(25, 5, 1, 'user', 'assigned', NULL, 'Cedrick Arnigo', 'Ticket assigned to Cedrick Arnigo', '2025-10-09 02:34:19'),
(26, 6, 1, 'employee', 'created', NULL, 'pending', 'Ticket created', '2025-10-09 09:38:01'),
(27, 6, 1, 'user', 'status_change', 'pending', 'in_progress', 'Status changed from pending to in_progress', '2025-10-09 09:38:41'),
(28, 6, 1, 'user', 'assigned', NULL, 'Cedrick Arnigo', 'Ticket assigned to Cedrick Arnigo', '2025-10-09 09:38:41'),
(29, 6, 1, 'user', 'comment', NULL, NULL, 'test', '2025-10-10 03:55:22'),
(30, 7, 1, 'employee', 'created', NULL, 'pending', 'Ticket created', '2025-10-14 05:58:49'),
(31, 8, 1, 'employee', 'created', NULL, 'pending', 'Ticket created', '2025-10-14 06:35:06'),
(32, 9, 1, 'employee', 'created', NULL, 'pending', 'Ticket created', '2025-10-14 07:01:43'),
(33, 10, 1, 'employee', 'created', NULL, 'pending', 'Ticket created', '2025-10-14 07:32:31'),
(34, 10, 1, 'user', 'status_change', 'pending', 'in_progress', 'Status changed from pending to in_progress', '2025-10-14 07:33:13'),
(35, 10, 1, 'user', 'assigned', NULL, 'Cedrick', 'Ticket assigned to Cedrick', '2025-10-14 07:33:13'),
(36, 11, 1, 'employee', 'created', NULL, 'pending', 'Ticket created', '2025-10-14 09:06:40'),
(37, 12, 4, 'user', 'created', NULL, 'pending', 'Ticket created by admin on behalf of employee', '2025-10-16 02:25:54'),
(38, 13, 1, 'user', 'created', NULL, 'pending', 'Ticket created by admin on behalf of employee', '2025-10-16 02:35:40'),
(39, 13, 1, 'user', 'assigned', NULL, 'Mahfuzul Islam', 'Ticket assigned to Mahfuzul Islam', '2025-10-16 02:36:36'),
(40, 13, 1, 'user', 'status_change', 'pending', 'closed', 'Status changed from pending to closed', '2025-10-16 02:44:24'),
(41, 13, 1, 'user', 'assigned', NULL, 'Mahfuzul Islam', 'Ticket assigned to Mahfuzul Islam', '2025-10-16 02:44:24'),
(42, 12, 1, 'user', 'status_change', 'pending', 'closed', 'Status changed from pending to closed', '2025-10-16 08:39:20'),
(43, 12, 1, 'user', 'assigned', NULL, 'Mahfuzul Islam', 'Ticket assigned to Mahfuzul Islam', '2025-10-16 08:39:20'),
(44, 12, 1, 'user', 'status_change', 'closed', 'resolved', 'Status changed from closed to resolved', '2025-10-16 08:39:40'),
(45, 12, 1, 'user', 'assigned', NULL, 'Mahfuzul Islam', 'Ticket assigned to Mahfuzul Islam', '2025-10-16 08:39:40'),
(46, 12, 2, 'user', 'status_change', 'resolved', 'closed', 'Status changed from resolved to closed', '2025-10-16 09:43:49'),
(47, 12, 2, 'user', 'assigned', NULL, 'Mahfuzul Islam', 'Ticket assigned to Mahfuzul Islam', '2025-10-16 09:43:49'),
(48, 3, 2, 'user', 'comment', NULL, NULL, 'test', '2025-10-16 09:44:05'),
(49, 16, 4, 'user', 'created', NULL, 'pending', 'Ticket created by admin on behalf of employee', '2025-10-17 07:58:42'),
(50, 16, 2, 'user', 'comment', NULL, NULL, 'hello', '2025-10-17 07:59:22'),
(51, 16, 2, 'user', 'comment', NULL, NULL, 'hello', '2025-10-17 07:59:31'),
(52, 16, 2, 'user', 'comment', NULL, NULL, 'test', '2025-10-17 08:33:08'),
(53, 16, 2, 'user', 'status_change', 'pending', 'resolved', 'Status changed from pending to resolved', '2025-10-17 08:35:17'),
(54, 16, 2, 'user', 'assigned', NULL, 'Mahfuzul Islam', 'Ticket assigned to Mahfuzul Islam', '2025-10-17 08:35:17'),
(55, 16, 2, 'user', 'status_change', 'resolved', 'closed', 'Status changed from resolved to closed', '2025-10-17 08:35:23'),
(56, 16, 2, 'user', 'assigned', NULL, 'Mahfuzul Islam', 'Ticket assigned to Mahfuzul Islam', '2025-10-17 08:35:23'),
(57, 15, 2, 'user', 'status_change', 'open', 'closed', 'Status changed from open to closed', '2025-10-17 08:43:33'),
(58, 15, 2, 'user', 'assigned', NULL, 'Mahfuzul Islam', 'Ticket assigned to Mahfuzul Islam', '2025-10-17 08:43:33'),
(59, 17, 2, 'user', 'comment', NULL, NULL, 'hi', '2025-11-03 02:01:16'),
(60, 17, 2, 'user', 'status_change', 'pending', 'closed', 'Status changed from pending to closed', '2025-11-03 02:01:29'),
(61, 17, 2, 'user', 'assigned', NULL, 'Mahfuzul Islam', 'Ticket assigned to Mahfuzul Islam', '2025-11-03 02:01:29'),
(62, 17, 2, 'user', 'resolution_added', NULL, 'test', 'Resolution added', '2025-11-03 02:01:29'),
(63, 17, 2, 'user', 'assigned', NULL, 'Mahfuzul Islam', 'Ticket assigned to Mahfuzul Islam', '2025-11-03 02:01:35'),
(64, 17, 2, 'user', 'resolution_added', NULL, 'test', 'Resolution added', '2025-11-03 02:01:35'),
(65, 22, 4, 'user', 'created', NULL, 'pending', 'Ticket created by admin on behalf of employee', '2025-11-03 02:04:08'),
(66, 22, 4, 'user', 'status_change', 'pending', 'closed', 'Status changed from pending to closed', '2025-11-03 02:18:43'),
(67, 22, 4, 'user', 'assigned', NULL, 'Mahfuzul Islam', 'Ticket assigned to Mahfuzul Islam', '2025-11-03 02:18:43');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('it_staff','admin') NOT NULL DEFAULT 'it_staff',
  `department` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `role`, `department`, `phone`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@company.com', '$2y$10$N0waCj/5zu6MKllawHUlTOc9K5evXFsLsyuv4V0TXBjk6/cncSGUq', 'Cedrick C. Arnigo', 'admin', 'IT', '', 1, '2025-10-08 03:37:59', '2025-10-10 02:51:18'),
(2, 'mahfuzul', 'mahfuzul@company.com', '$2y$10$N0waCj/5zu6MKllawHUlTOc9K5evXFsLsyuv4V0TXBjk6/cncSGUq', 'Mahfuzul Islam', 'it_staff', 'IT', NULL, 1, '2025-10-08 03:37:59', '2025-10-13 07:35:19'),
(4, 'Cedrick.Arnigo', 'cedrick.arnigo@resourcestaff.com.ph', '$2y$10$to75yioYpT0K/BkVPu2E.eFnyDQxukXWPCVJhYOWmZdDIJMQCX/06', 'Cedrick', 'admin', 'IT Department', '993 864 2974', 1, '2025-10-13 04:12:29', '2025-10-13 04:12:29');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_sla_summary`
-- (See below for the actual view)
--
CREATE TABLE `v_sla_summary` (
`ticket_id` int(11)
,`ticket_number` varchar(20)
,`title` varchar(200)
,`priority` enum('low','medium','high','urgent')
,`ticket_status` enum('pending','open','in_progress','resolved','closed')
,`sla_status` enum('met','at_risk','breached','pending','none')
,`response_sla_status` enum('met','at_risk','breached','pending')
,`resolution_sla_status` enum('met','at_risk','breached','pending')
,`response_due_at` datetime
,`resolution_due_at` datetime
,`first_response_at` datetime
,`resolved_at` datetime
,`response_time_minutes` int(11)
,`resolution_time_minutes` int(11)
,`is_paused` tinyint(1)
,`target_response_minutes` int(11)
,`target_resolution_minutes` int(11)
,`is_business_hours` tinyint(1)
,`minutes_remaining` bigint(21)
,`elapsed_percentage` decimal(26,2)
);

-- --------------------------------------------------------

--
-- Structure for view `v_sla_summary`
--
DROP TABLE IF EXISTS `v_sla_summary`;

CREATE VIEW `v_sla_summary` AS 
SELECT 
  `t`.`id` AS `ticket_id`, 
  `t`.`ticket_number` AS `ticket_number`, 
  `t`.`title` AS `title`, 
  `t`.`priority` AS `priority`, 
  `t`.`status` AS `ticket_status`, 
  `t`.`sla_status` AS `sla_status`, 
  `st`.`response_sla_status` AS `response_sla_status`, 
  `st`.`resolution_sla_status` AS `resolution_sla_status`, 
  `st`.`response_due_at` AS `response_due_at`, 
  `st`.`resolution_due_at` AS `resolution_due_at`, 
  `st`.`first_response_at` AS `first_response_at`, 
  `st`.`resolved_at` AS `resolved_at`, 
  `st`.`response_time_minutes` AS `response_time_minutes`, 
  `st`.`resolution_time_minutes` AS `resolution_time_minutes`, 
  `st`.`is_paused` AS `is_paused`, 
  `sp`.`response_time` AS `target_response_minutes`, 
  `sp`.`resolution_time` AS `target_resolution_minutes`, 
  `sp`.`is_business_hours` AS `is_business_hours`, 
  CASE 
    WHEN `st`.`resolved_at` IS NOT NULL THEN 0 
    WHEN `st`.`is_paused` = 1 THEN TIMESTAMPDIFF(MINUTE, CURRENT_TIMESTAMP(), `st`.`resolution_due_at`) 
    ELSE TIMESTAMPDIFF(MINUTE, CURRENT_TIMESTAMP(), `st`.`resolution_due_at`) 
  END AS `minutes_remaining`, 
  CASE 
    WHEN `st`.`resolved_at` IS NOT NULL THEN 100 
    ELSE ROUND(TIMESTAMPDIFF(MINUTE, `t`.`created_at`, CURRENT_TIMESTAMP()) / `sp`.`resolution_time` * 100, 2) 
  END AS `elapsed_percentage` 
FROM `tickets` `t` 
LEFT JOIN `sla_tracking` `st` ON `t`.`id` = `st`.`ticket_id` 
LEFT JOIN `sla_policies` `sp` ON `st`.`sla_policy_id` = `sp`.`id` 
WHERE `t`.`status` <> 'closed';

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `ticket_id` (`ticket_id`),
  ADD KEY `is_read` (`is_read`),
  ADD KEY `created_at` (`created_at`),
  ADD KEY `idx_notifications_employee_id` (`employee_id`);

--
-- Indexes for table `sla_breaches`
--
ALTER TABLE `sla_breaches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sla_tracking_id` (`sla_tracking_id`),
  ADD KEY `idx_ticket_id` (`ticket_id`),
  ADD KEY `idx_breach_type` (`breach_type`),
  ADD KEY `idx_notified` (`notified`);

--
-- Indexes for table `sla_policies`
--
ALTER TABLE `sla_policies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_priority` (`priority`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `sla_tracking`
--
ALTER TABLE `sla_tracking`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sla_policy_id` (`sla_policy_id`),
  ADD KEY `idx_ticket_id` (`ticket_id`),
  ADD KEY `idx_response_status` (`response_sla_status`),
  ADD KEY `idx_resolution_status` (`resolution_sla_status`),
  ADD KEY `idx_response_due` (`response_due_at`),
  ADD KEY `idx_resolution_due` (`resolution_due_at`);

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ticket_number` (`ticket_number`),
  ADD UNIQUE KEY `unique_ticket_number` (`ticket_number`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_submitter` (`submitter_id`,`submitter_type`),
  ADD KEY `idx_assigned` (`assigned_to`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_sla_status` (`sla_status`);

--
-- Indexes for table `ticket_activity`
--
ALTER TABLE `ticket_activity`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ticket` (`ticket_id`),
  ADD KEY `idx_user` (`user_id`,`user_type`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `sla_breaches`
--
ALTER TABLE `sla_breaches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `sla_policies`
--
ALTER TABLE `sla_policies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sla_tracking`
--
ALTER TABLE `sla_tracking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ticket_activity`
--
ALTER TABLE `ticket_activity`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

-- Temporarily disable foreign key checks to avoid constraint errors
SET FOREIGN_KEY_CHECKS = 0;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notification_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sla_breaches`
--
ALTER TABLE `sla_breaches`
  ADD CONSTRAINT `sla_breaches_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sla_breaches_ibfk_2` FOREIGN KEY (`sla_tracking_id`) REFERENCES `sla_tracking` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sla_tracking`
--
ALTER TABLE `sla_tracking`
  ADD CONSTRAINT `sla_tracking_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sla_tracking_ibfk_2` FOREIGN KEY (`sla_policy_id`) REFERENCES `sla_policies` (`id`);

--
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tickets_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `ticket_activity`
--
ALTER TABLE `ticket_activity`
  ADD CONSTRAINT `ticket_activity_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
