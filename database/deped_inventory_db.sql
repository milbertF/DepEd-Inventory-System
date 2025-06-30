-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 30, 2025 at 01:16 PM
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
-- Database: `deped-inventory-db`
--

-- --------------------------------------------------------

--
-- Table structure for table `deped_inventory_employee_office`
--

CREATE TABLE `deped_inventory_employee_office` (
  `office_id` varchar(6) NOT NULL,
  `office_name` varchar(100) DEFAULT NULL,
  `office_location` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deped_inventory_employee_office`
--

INSERT INTO `deped_inventory_employee_office` (`office_id`, `office_name`, `office_location`, `created_at`, `updated_at`) VALUES
('643198', '123', '123', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `deped_inventory_employee_position`
--

CREATE TABLE `deped_inventory_employee_position` (
  `position_id` varchar(6) NOT NULL,
  `position_title` varchar(100) DEFAULT NULL,
  `position_description` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deped_inventory_employee_position`
--

INSERT INTO `deped_inventory_employee_position` (`position_id`, `position_title`, `position_description`, `created_at`, `updated_at`) VALUES
('000034', '143', '143', NULL, NULL),
('565491', '153', '154', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `deped_inventory_users`
--

CREATE TABLE `deped_inventory_users` (
  `user_id` varchar(6) NOT NULL,
  `email` varchar(500) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deped_inventory_users`
--

INSERT INTO `deped_inventory_users` (`user_id`, `email`, `username`, `password`, `role`, `created_at`, `updated_at`) VALUES
('874354', 'S2lJOWxLUWJVNHhFRjJtQ29jaXRLdWRPUTRERFFTbk91OHoyRTB4ZzZkOD06OgEBb7u5VXI0urHNBA0PnFY=', 'jdaguidan', '$2y$10$idFqMvnGvph9pjltypjPtO2jnHuhbhFOHQH1lvtzs/m4LNXRm023O', 'Admin', '2025-06-30 18:23:58', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `deped_inventory_user_info`
--

CREATE TABLE `deped_inventory_user_info` (
  `info_id` int(11) NOT NULL,
  `user_id` varchar(6) DEFAULT NULL,
  `first_name` varchar(500) DEFAULT NULL,
  `middle_name` varchar(500) DEFAULT NULL,
  `last_name` varchar(500) NOT NULL,
  `contact_number` varchar(500) NOT NULL,
  `address` varchar(1000) NOT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `position_id` varchar(6) DEFAULT NULL,
  `office_id` varchar(6) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deped_inventory_user_info`
--

INSERT INTO `deped_inventory_user_info` (`info_id`, `user_id`, `first_name`, `middle_name`, `last_name`, `contact_number`, `address`, `profile_photo`, `position_id`, `office_id`, `created_at`, `updated_at`) VALUES
(821614, '874354', 'ZXNzUk5Lalp3Y3orSFpMZ3VlUjdFdz09Ojpm76tp8zYPtwOc7ghztNal', 'QkNQUlRjRVYvM0ZDYUI5dnNJa1l1dz09OjpYXGHTfI29rKFvwBeMmlFN', 'dzNKWGlZMUdWWktHUFlJOFhXZTNXQT09OjqUYQ0/QjobsksioR3aCSaw', 'MjZ1ZnRMbTJZN3AvVG1veDFTaG85Zz09Ojpkuh8ZoGrkM2AvrKYId5VT', 'Z2I5RkpBRjBZWE1LNEpRRXFmWEQrQT09OjqUZMdSVXWkMKijgG5sH6C0', 'images/user-profile/ronson_delena.jpg', '565491', '643198', '2025-06-30 18:23:58', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `deped_inventory_employee_office`
--
ALTER TABLE `deped_inventory_employee_office`
  ADD PRIMARY KEY (`office_id`);

--
-- Indexes for table `deped_inventory_employee_position`
--
ALTER TABLE `deped_inventory_employee_position`
  ADD PRIMARY KEY (`position_id`);

--
-- Indexes for table `deped_inventory_users`
--
ALTER TABLE `deped_inventory_users`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `deped_inventory_user_info`
--
ALTER TABLE `deped_inventory_user_info`
  ADD PRIMARY KEY (`info_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `position_id` (`position_id`),
  ADD KEY `office_id` (`office_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `deped_inventory_user_info`
--
ALTER TABLE `deped_inventory_user_info`
  MODIFY `info_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=821615;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `deped_inventory_user_info`
--
ALTER TABLE `deped_inventory_user_info`
  ADD CONSTRAINT `deped_inventory_user_info_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `deped_inventory_users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `deped_inventory_user_info_ibfk_2` FOREIGN KEY (`position_id`) REFERENCES `deped_inventory_employee_position` (`position_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `deped_inventory_user_info_ibfk_3` FOREIGN KEY (`office_id`) REFERENCES `deped_inventory_employee_office` (`office_id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
