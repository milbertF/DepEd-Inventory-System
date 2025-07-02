-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 02, 2025 at 07:36 PM
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
-- Database: `deped_inventory_db`
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
('239628', 'Top', '', '2025-07-02 20:56:24', NULL),
('422882', '123', '', '2025-07-02 21:39:44', NULL),
('426334', 'Mid', '', '2025-07-02 20:56:24', NULL),
('675829', 'Jungle', '', '2025-07-02 20:56:24', NULL),
('715433', 'My Office', '', '2025-07-03 01:32:27', NULL),
('754260', 'Bot', '', '2025-07-02 20:56:24', NULL),
('838296', 'Sideline', '', '2025-07-02 20:56:24', NULL),
('970316', 'Office of The President', '', '2025-07-03 01:32:27', NULL);

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
('221375', 'Pos 5', 'Tank', '2025-07-02 20:56:00', NULL),
('422801', 'Pos 1', 'Gold Lane', '2025-07-02 20:56:00', NULL),
('451426', 'Pos 3', 'Exp', '2025-07-02 20:56:00', NULL),
('489639', 'Pos 4', 'Mid', '2025-07-02 20:56:00', NULL),
('876124', 'Pos 2', 'Jungle', '2025-07-02 20:56:00', NULL);

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
('428651', 'Z3dKS3NPdnRIdG1lMUJtWTVoQWl4QT09OjqVQ8cg1dju0RXTsHt/mM4I', 'miku', '$2y$10$ESJ4ihbK3bn0sAwnEEHb7OFRcaB2B.5dgAhq6WGkuO/rfd.iEZ.xK', 'Employee', '2025-07-02 16:20:33', NULL),
('979342', 'Z0xPdCtJOWRkS095anRMSG50NFBSclZ1c3dyYm5wZ2xXUFdzOWN3NElsST06OmtzrGXM2SqboerVx/h6kl0=', 'ronson', '$2y$10$LYySZe5oIEt05I8Bd0Xapupnr170ATwdyixb93D4khlBGhBIAO8Fq', 'Deactivate', '2025-07-02 15:15:13', NULL);

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
(234887, '979342', 'Sm9lTE1Dc2FESENwcjhlaEZwd1dLQT09Ojr8HMHHGBI/FMCHpE1r0/kU', 'NEd4SzMrQTNQTkxtQTVUMnBFSGJVZz09Ojoy34Xr+KD293qZGSO794Sd', 'MUIwNDN2R01YS0E5a2xnMUZXSWxEdz09OjpC9jZQlJ7ljQvBGk0IRdhJ', 'd0t4YnBPZ1ZuaHNJcWY1TThzRm96dz09OjraYHB/UB6zXQJ0Dmi8YIO8', 'ZGgyVlAzZ0JYNEdjbjRjM053NmNRdz09OjpQAPO3yCRmx62iAZpzsEk9', '/images/user-profile/ronson_delena.png', '876124', '675829', '2025-07-02 15:15:13', NULL),
(889896, '428651', 'V2xQR1dkY2poMEV4cElwdWZmeVI3dz09OjppwkpxL/4IQdrB+4zOLONM', 'YnN6SGJYNlUyQWlUeC92Z3RVd1RCUT09OjrOMKtdxyn6jx/DMMH6E+D+', 'QzBuMHBQUDdIbDF4YkJZNzhlNjludz09OjqWhcaXfALaufuu0W780YhJ', 'NlNaNjVwOUwyZXdPaU5MUHZoM3p6QT09Ojp9EUYJzXof8v/nQnNfmq1x', 'aHlCSWVtaHlxcmNkL2lHSGtvb0NoQXV4Z1FKcWFVZytmS09BRE0vV2I3OTk5Q0pHWDIrK0x1RFA2Y0Jja1BIazo6p9JjUNWbFqRh4L+moK29ww==', '/images/user-profile/miku_delena_2.jpg', '876124', '754260', '2025-07-02 16:20:33', NULL);

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
  MODIFY `info_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=972898;

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
