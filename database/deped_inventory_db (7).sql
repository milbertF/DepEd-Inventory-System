-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 07, 2025 at 09:58 AM
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
  `office_description` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deped_inventory_employee_office`
--

INSERT INTO `deped_inventory_employee_office` (`office_id`, `office_name`, `office_description`, `created_at`, `updated_at`) VALUES
('408133', 'Pos 2', '', '2025-10-14 07:38:01', NULL);

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
('396050', 'Pos 1', '', '2025-10-14 07:37:56', NULL),
('694962', 'Pos1', '', '2025-10-14 07:38:04', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `deped_inventory_items`
--

CREATE TABLE `deped_inventory_items` (
  `item_id` int(11) NOT NULL,
  `item_photo` varchar(255) DEFAULT NULL,
  `item_name` varchar(255) NOT NULL,
  `category_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `initial_quantity` int(11) NOT NULL DEFAULT 0,
  `date_acquired` date DEFAULT NULL,
  `unit` varchar(255) DEFAULT NULL,
  `unit_cost` decimal(10,2) NOT NULL,
  `total_cost` decimal(12,2) GENERATED ALWAYS AS (`quantity` * `unit_cost`) STORED,
  `item_status` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deped_inventory_items`
--

INSERT INTO `deped_inventory_items` (`item_id`, `item_photo`, `item_name`, `category_id`, `description`, `brand`, `model`, `serial_number`, `quantity`, `initial_quantity`, `date_acquired`, `unit`, `unit_cost`, `item_status`, `created_at`) VALUES
(78431, '', 'Hammer', 23, '16oz steel claw hammer', 'Stanley', '', '', 1, 1, '2025-10-13', 'Piece', 350.00, 'Good', '2025-10-13 04:16:18'),
(122193, '', 'Desktop Computer', 2, 'Intel i5, 8GB RAM, 512GB SSD', 'HP', '', '', 1, 1, '2025-10-13', 'Set', 35000.00, 'Good', '2025-10-13 04:16:17'),
(174008, '', 'Safety Helmet', 9, 'Yellow hard hat for site safety', '3M', '', '', 1, 1, '2025-10-13', 'Piece', 250.00, 'Good', '2025-10-13 04:16:17'),
(212319, '', 'Microsoft Office License', 25, '1-year subscription for 5 users', 'Microsoft', '', '', 1, 1, '2025-10-13', 'License', 8500.00, 'Good', '2025-10-13 04:16:18'),
(233765, '', 'Bond Paper Short1', 28, 'Short size 8.5x11in 70gsm', 'PaperOne', NULL, NULL, 1, 1, '2025-10-13', 'Ream', 270.00, 'Good', '2025-10-13 04:16:18'),
(243607, '', 'Engine Oil 1L', 19, 'High-performance motor oil', 'Shell', '', '', 1, 1, '2025-10-13', 'Bottle', 550.00, 'Good', '2025-10-13 04:16:18'),
(339926, '', 'Basketball', 12, 'Official size and weight', 'Molten', '', '', 1, 1, '2025-10-13', 'Piece', 950.00, 'Good', '2025-10-13 04:16:18'),
(366904, '', 'Printer Ink Cartridge', 17, 'Black ink for HP LaserJet', 'HP', '', '', 1, 1, '2025-10-13', 'Piece', 1800.00, 'Good', '2025-10-13 04:16:18'),
(415176, '', 'PVC Pipe ½ inch', 7, 'Durable plumbing pipe', 'Atlanta', '', '', 1, 1, '2025-10-13', 'Piece', 80.00, 'Good', '2025-10-13 04:16:17'),
(427476, '', 'Bond Paper A4', 1, 'High-quality A4 size paper', 'PaperOne', '', '', 1, 1, '2025-10-13', 'Ream', 280.00, 'Good', '2025-10-13 04:16:17'),
(501500, '', 'Rice 25kg', 21, 'Premium white rice sack1', 'Sinandomeng', NULL, NULL, 1, 1, '2025-10-13', 'Sack', 1400.00, 'Good', '2025-10-13 04:16:18'),
(675791, '', 'Electric Fan', 983840, '16-inch stand fan', 'Asahi', '', '', 1, 1, '2025-10-13', 'Piece', 1200.00, 'Good', '2025-10-13 04:16:18'),
(732878, '', 'Microscope', 13, '1000x magnification, LED light', 'AmScope', '', '', 1, 1, '2025-10-13', 'Piece', 12000.00, 'Good', '2025-10-13 04:16:18'),
(781946, '', 'Ballpoint Pen', 10, 'Blue ink, pack of 12', 'Pilot', '', '', 1, 1, '2025-10-13', 'Box', 180.00, 'Good', '2025-10-13 04:16:18'),
(788159, '', 'Wi-Fi Router', 24, 'Dual-band wireless router', 'TP-Link', NULL, NULL, 1, 1, '2025-10-13', 'Piece', 2500.00, 'Lost', '2025-10-13 04:16:18'),
(799188, '', 'First Aid Kit', 14, 'Complete emergency medical set', 'MedGuard', '', '', 1, 1, '2025-10-13', 'Set', 800.00, 'Good', '2025-10-13 04:16:18'),
(811118, '', 'LED Tube Light', 18, '4ft 18W daylight', 'Philips', '', '', 1, 1, '2025-10-13', 'Piece', 300.00, 'Good', '2025-10-13 04:16:18'),
(877184, '', 'Office Chair', 3, 'Adjustable ergonomic chair', 'ErgoPro', '', '', 1, 1, '2025-10-13', 'Piece', 2500.00, 'Good', '2025-10-13 04:16:17'),
(932012, '', 'Cooking Pot', 20, 'Stainless steel with lid', 'Meyer', '', '', 1, 1, '2025-10-13', 'Piece', 750.00, 'Good', '2025-10-13 04:16:18'),
(999652, '', 'Student Uniform', 27, 'Standard size uniform set', 'DepEd Supply', '', '', 1, 1, '2025-10-13', 'Set', 800.00, 'Good', '2025-10-13 04:16:18');

-- --------------------------------------------------------

--
-- Table structure for table `deped_inventory_items_deleted`
--

CREATE TABLE `deped_inventory_items_deleted` (
  `deleted_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `item_photo` varchar(255) DEFAULT NULL,
  `item_name` varchar(255) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `category_name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `brand` varchar(255) DEFAULT NULL,
  `model` varchar(255) DEFAULT NULL,
  `serial_number` varchar(255) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `initial_quantity` int(11) DEFAULT 0,
  `date_acquired` date DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `unit_cost` decimal(10,2) DEFAULT NULL,
  `total_cost` decimal(10,2) DEFAULT NULL,
  `item_status` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_by_user_id` varchar(50) DEFAULT NULL,
  `deleted_by_fname` varchar(255) DEFAULT 'Unknown',
  `deleted_by_lname` varchar(255) DEFAULT 'Unknown',
  `deleted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deped_inventory_items_deleted`
--

INSERT INTO `deped_inventory_items_deleted` (`deleted_id`, `item_id`, `item_photo`, `item_name`, `category_id`, `category_name`, `description`, `brand`, `model`, `serial_number`, `quantity`, `initial_quantity`, `date_acquired`, `unit`, `unit_cost`, `total_cost`, `item_status`, `created_at`, `deleted_by_user_id`, `deleted_by_fname`, `deleted_by_lname`, `deleted_at`) VALUES
(250124, 84741, '', 'Filing Cabinet11', 29, 'Office Furniture', '4-drawer metal cabinet', 'SteelArt', NULL, NULL, 1, 1, '2025-10-13', 'Piece', 6800.00, 6800.00, 'For Repair', '2025-10-13 04:16:18', '979342', 'Ronson', 'Delena', '2025-10-15 07:08:46'),
(308182, 963083, '', 'Garden Rake', 8, 'Gardening Tools', 'Steel head with wooden handle', 'Truper', NULL, NULL, 1, 1, '2025-10-13', 'Piece', 350.00, 350.00, 'Good', '2025-10-13 04:16:17', '979342', 'Ronson', 'Delena', '2025-10-15 07:22:05'),
(564055, 27856, '', 'Screwdriver Set', 5, 'Electrical Tools', 'Assorted flat and Phillips screwdrivers', 'Bosch', '', '', 1, 1, '2025-10-13', '0', 600.00, 600.00, 'Good', '2025-10-13 04:16:17', '979342', 'Ronson', 'Delena', '2025-10-15 06:42:48'),
(616010, 597704, '', 'Cement', 6, 'Construction Materials', 'Portland cement 40kg bag', 'Holcim', '', '', 1, 1, '2025-10-13', '0', 270.00, 270.00, 'Good', '2025-10-13 04:16:17', '979342', 'Ronson', 'Delena', '2025-10-15 06:42:42');

-- --------------------------------------------------------

--
-- Table structure for table `deped_inventory_item_category`
--

CREATE TABLE `deped_inventory_item_category` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `upated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deped_inventory_item_category`
--

INSERT INTO `deped_inventory_item_category` (`category_id`, `category_name`, `created_at`, `upated_at`) VALUES
(1, 'Office Supplies', '2025-10-13 12:12:40', '2025-10-13 12:12:40'),
(2, 'IT Equipment', '2025-10-13 12:12:40', '2025-10-13 12:12:40'),
(3, 'Furniture', '2025-10-13 12:12:40', '2025-10-13 12:12:40'),
(4, 'Cleaning Materials', '2025-10-13 12:12:40', '2025-10-13 12:12:40'),
(5, 'Electrical Tools', '2025-10-13 12:12:40', '2025-10-13 12:12:40'),
(6, 'Construction Materials', '2025-10-13 12:12:40', '2025-10-13 12:12:40'),
(7, 'Plumbing Supplies', '2025-10-13 12:12:40', '2025-10-13 12:12:40'),
(8, 'Gardening Tools', '2025-10-13 12:12:40', '2025-10-13 12:12:40'),
(9, 'Safety Equipment', '2025-10-13 12:12:40', '2025-10-13 12:12:40'),
(10, 'Stationery', '2025-10-13 12:12:40', '2025-10-13 12:12:40'),
(11, 'School Equipment', '2025-10-13 12:12:40', '2025-10-13 12:12:40'),
(12, 'Sports Equipment', '2025-10-13 12:12:40', '2025-10-13 12:12:40'),
(13, 'Laboratory Supplies', '2025-10-13 12:12:40', '2025-10-13 12:12:40'),
(14, 'Medical Supplies', '2025-10-13 12:12:40', '2025-10-13 12:12:40'),
(15, 'Textbooks', '2025-10-13 12:12:40', '2025-10-13 12:12:40'),
(17, 'Printer Ink & Toner', '2025-10-13 12:12:40', '2025-10-13 12:12:40'),
(18, 'Lighting Fixtures', '2025-10-13 12:12:40', '2025-10-13 12:12:40'),
(19, 'Vehicle Maintenance', '2025-10-13 12:12:40', '2025-10-13 12:12:40'),
(20, 'Kitchen Equipment', '2025-10-13 12:12:40', '2025-10-13 12:12:40'),
(21, 'Food Supplies', '2025-10-13 12:12:40', '2025-10-13 12:12:40'),
(22, 'Cleaning Equipment', '2025-10-13 12:12:40', '2025-10-13 12:12:40'),
(23, 'Hardware Tools', '2025-10-13 12:12:40', '2025-10-13 12:12:40'),
(24, 'Networking Devices', '2025-10-13 12:12:40', '2025-10-13 12:12:40'),
(25, 'Software Licenses', '2025-10-13 12:12:40', '2025-10-13 12:12:40'),
(26, 'Storage Devices', '2025-10-13 12:12:40', '2025-10-13 12:12:40'),
(27, 'School Uniforms', '2025-10-13 12:12:40', '2025-10-13 12:12:40'),
(28, 'Paper Products', '2025-10-13 12:12:40', '2025-10-13 12:12:40'),
(29, 'Office Furniture', '2025-10-13 12:12:40', '2025-10-13 12:12:40'),
(345271, '1', '2025-10-15 07:44:01', '2025-10-15 07:44:01'),
(657698, 'Electronics', '2025-10-13 12:07:30', '2025-10-13 12:10:39'),
(983840, 'Electrical Appliances', '2025-10-13 12:43:21', '2025-10-13 12:43:32'),
(983861, 'Audio Visual Equipment', '2025-10-15 10:50:39', '2025-10-15 11:22:27');

-- --------------------------------------------------------

--
-- Table structure for table `deped_inventory_notifications`
--

CREATE TABLE `deped_inventory_notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` varchar(20) NOT NULL,
  `item_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `action_type` enum('quantity_added','item_created','item_updated','item_deleted') NOT NULL,
  `old_quantity` int(11) DEFAULT NULL,
  `new_quantity` int(11) DEFAULT NULL,
  `quantity_added` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deped_inventory_notifications`
--

INSERT INTO `deped_inventory_notifications` (`notification_id`, `user_id`, `item_id`, `item_name`, `action_type`, `old_quantity`, `new_quantity`, `quantity_added`, `message`, `created_at`, `is_read`) VALUES
(12, '979342', 243269, 'Mop Bucket', 'quantity_added', 1, 2, 1, 'Added 1 quantity to item \'Mop Bucket\'. Quantity changed from 1 to 2.', '2025-10-14 23:50:24', 1),
(42, '979342', 243269, 'Mop Bucket', 'item_deleted', 2, NULL, NULL, 'Item #243269 (Mop Bucket) was permanently deleted from the system.', '2025-10-15 02:56:49', 1),
(43, '979342', 963083, 'Garden Rake1', 'item_updated', 1, 1, NULL, 'Item #963083 (Garden Rake1) was updated: name from \'Garden Rake\' to \'Garden Rake1\'', '2025-10-15 02:59:14', 1),
(44, '979342', 963083, 'Garden Rake', 'item_updated', 1, 1, NULL, 'Item #963083 (Garden Rake) was updated: name from \'Garden Rake1\' to \'Garden Rake\'', '2025-10-15 02:59:20', 1),
(45, '979342', 84741, 'Filing Cabinet', 'item_deleted', 1, NULL, NULL, 'Item #84741 (Filing Cabinet) was permanently deleted from the system.', '2025-10-15 03:00:44', 1),
(46, '979342', 826820, 'LED TV11', 'item_updated', 18, 18, NULL, 'Item #826820 (LED TV11) was updated: details', '2025-10-15 03:22:10', 1),
(47, '979342', 826820, 'LED TV111', 'item_updated', 18, 18, NULL, 'Item #826820 (LED TV111) was updated: name from \'LED TV11\' to \'LED TV111\'', '2025-10-15 03:36:44', 1),
(48, '979342', 963083, 'Garden Rake1', 'item_updated', 1, 1, NULL, 'Item #963083 (Garden Rake1) was updated: name from \'Garden Rake\' to \'Garden Rake1\'', '2025-10-15 03:46:10', 1),
(49, '979342', 963083, 'Garden Rake', 'item_updated', 1, 1, NULL, 'Item #963083 (Garden Rake) was updated: name from \'Garden Rake1\' to \'Garden Rake\'', '2025-10-15 03:46:14', 1),
(50, '979342', 501500, 'Rice 25kg', 'item_updated', 1, 1, NULL, 'Item #501500 (Rice 25kg) was updated: details', '2025-10-15 03:50:25', 1),
(51, '979342', 963083, 'Garden Rake1', 'item_updated', 1, 1, NULL, 'Item #963083 (Garden Rake1) was updated: name from \'Garden Rake\' to \'Garden Rake1\'', '2025-10-15 04:11:40', 1),
(52, '979342', 963083, 'Garden Rake', 'item_updated', 1, 1, NULL, 'Item #963083 (Garden Rake) was updated: name from \'Garden Rake1\' to \'Garden Rake\'', '2025-10-15 04:11:43', 1),
(53, '979342', 233765, 'Bond Paper Short', 'item_deleted', 1, NULL, NULL, 'Item #233765 (Bond Paper Short) was deleted from the inventory .', '2025-10-15 04:11:49', 1),
(54, '979342', 826820, 'LED TV111', 'quantity_added', 18, 23, 5, 'Item #826820 (LED TV111): Added 5 quantity. Quantity changed from 18 to 23.', '2025-10-15 04:48:11', 1),
(55, '979342', 2025857097, '1', 'item_deleted', 1, NULL, NULL, 'Item #2025857097 (1) was  deleted from the inventory.', '2025-10-15 05:00:50', 1),
(56, '979342', 826820, 'LED TV111', 'item_deleted', 23, NULL, NULL, 'Item #826820 (LED TV111) was  deleted from the inventory.', '2025-10-15 05:05:41', 1),
(57, '979342', 826820, 'LED TV1111', 'item_updated', 23, 23, NULL, 'Item #826820 (LED TV1111) was updated: name from \'LED TV111\' to \'LED TV1111\'', '2025-10-15 05:10:54', 1),
(58, '979342', 826820, 'LED TV1111', 'item_deleted', 23, NULL, NULL, 'Item #826820 (LED TV1111) was  deleted from the inventory.', '2025-10-15 05:10:57', 1),
(59, '979342', 243269, 'Mop Bucket', 'item_deleted', 2, NULL, NULL, 'Item #243269 (Mop Bucket) was  deleted from the inventory.', '2025-10-15 06:05:21', 1),
(60, '979342', 84741, 'Filing Cabinet1', 'item_updated', 1, 1, NULL, 'Item #84741 (Filing Cabinet1) was updated: name from \'Filing Cabinet\' to \'Filing Cabinet1\'', '2025-10-15 06:19:14', 1),
(61, '979342', 597704, 'Cement', 'item_deleted', 1, NULL, NULL, 'Item #597704 (Cement) was  deleted from the inventory.', '2025-10-15 06:24:02', 1),
(62, '979342', 699697, 'Liquid Detergent', 'item_deleted', 1, NULL, NULL, 'Item #699697 (Liquid Detergent) was  deleted from the inventory.', '2025-10-15 06:24:15', 1),
(63, '979342', 27856, 'Screwdriver Set', 'item_deleted', 1, NULL, NULL, 'Item #27856 (Screwdriver Set) was  deleted from the inventory.', '2025-10-15 06:41:55', 1),
(64, '979342', 597704, 'Cement', 'item_deleted', 1, NULL, NULL, 'Item #597704 (Cement) was  deleted from the inventory.', '2025-10-15 06:42:32', 1),
(65, '979342', 597704, 'Cement', 'item_deleted', 1, NULL, NULL, 'Item #597704 (Cement) was  deleted from the inventory.', '2025-10-15 06:42:42', 1),
(66, '979342', 27856, 'Screwdriver Set', 'item_deleted', 1, NULL, NULL, 'Item #27856 (Screwdriver Set) was  deleted from the inventory.', '2025-10-15 06:42:48', 1),
(67, '979342', 84741, 'Filing Cabinet11', 'item_updated', 1, 1, NULL, 'Item #84741 (Filing Cabinet11) was updated: name from \'Filing Cabinet1\' to \'Filing Cabinet11\'', '2025-10-15 07:08:43', 1),
(68, '979342', 84741, 'Filing Cabinet11', 'item_deleted', 1, NULL, NULL, 'Item #84741 (Filing Cabinet11) was deleted from the inventory .', '2025-10-15 07:08:46', 1),
(69, '979342', 963083, 'Garden Rake1', 'item_updated', 1, 1, NULL, 'Item #963083 (Garden Rake1) was updated: name from \'Garden Rake\' to \'Garden Rake1\'', '2025-10-15 07:21:53', 1),
(70, '979342', 963083, 'Garden Rake', 'item_updated', 1, 1, NULL, 'Item #963083 (Garden Rake) was updated: name from \'Garden Rake1\' to \'Garden Rake\'', '2025-10-15 07:22:00', 1),
(71, '979342', 963083, 'Garden Rake', 'item_deleted', 1, NULL, NULL, 'Item #963083 (Garden Rake) was deleted from the inventory .', '2025-10-15 07:22:05', 1),
(72, '979342', 233765, 'Bond Paper Short1', 'item_updated', 1, 1, NULL, 'Item #233765 (Bond Paper Short1) was updated: name from \'Bond Paper Short\' to \'Bond Paper Short1\'', '2025-10-15 07:22:37', 1);

-- --------------------------------------------------------

--
-- Table structure for table `deped_inventory_requests`
--

CREATE TABLE `deped_inventory_requests` (
  `request_id` int(11) NOT NULL,
  `user_id` varchar(6) NOT NULL,
  `purpose` text DEFAULT NULL,
  `status` enum('Pending','Approved','Declined','Released','Received') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deped_inventory_request_items`
--

CREATE TABLE `deped_inventory_request_items` (
  `req_item_id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `requested_quantity` int(11) NOT NULL,
  `date_needed` date NOT NULL,
  `approval_status` enum('Pending','Approved','Declined') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
('455232', 'Qm8wNHpQWW5jOGozcml0VGZqY3lNWjZ0WXhBalhaY3gxM0E2eE10QXN2TT06OlU1dd6l4E1z8mAY2O5B5ek=', 'milbert', '$2y$10$ID4Es6uM0UoM4m2RuVeXuuNLgeyVQc1IKgKQEzEfV00WO2WG1Kw7C', 'Employee', '2025-07-04 17:45:32', NULL),
('960248', 'eU5WSWF0Q0lwZW9sT1lpMzNYcG5UQWJyeS9yd1dVaHVlMjdLUU8yN3VtRT06Ou/jUOa01MesNMA79rk8QEQ=', 'elhope', '$2y$10$YaGeumaDsrP63lnCy1vI/.e9asm3wFuFM.nfTxPbJCd4fHaOAW6mG', 'Employee', '2025-10-14 07:43:10', NULL),
('967782', 'YUVycWtmQzNqZE9kbU5LaHNJcFBxUW5JWmUzNkc0UkJ0Y2dDWjZUMDRRQT06OjKlYdIyM1exGQekQhI13UI=', 'chito', '$2y$10$wQ72tSJcTubJFNutSpDI..QstDQyxxZA9YaCeawkKh47aOTyBVIEK', 'Inactive', '2025-10-14 07:44:41', NULL),
('979342', 'Z0xPdCtJOWRkS095anRMSG50NFBSclZ1c3dyYm5wZ2xXUFdzOWN3NElsST06OmtzrGXM2SqboerVx/h6kl0=', 'ronson', '$2y$10$LYySZe5oIEt05I8Bd0Xapupnr170ATwdyixb93D4khlBGhBIAO8Fq', 'Admin', '2025-07-02 15:15:13', NULL);

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
(208626, '960248', 'TFppdThid0JwVUZMUFM2MFZuT1dyQT09OjrfP6u7yfrEtWiOpKuo96mZ', 'Y25oL0ZJUW1mUzhrNFBNeWdXQ2xpUT09Ojr28DSd4GOwxC4CwyvrYri1', 'RHNTZDlza08zWmc2S25TM0kzdVA2UT09OjrQuOYGlfddFfL33HshTJ0O', 'WE82Mm5NWGVqVmdkV1BDcVI2QzBPUT09Ojq+ne51laXAv9Di3cGTQWCm', 'SU1Ia2Z0YTNJdnhQbi8xczFZWE93dz09OjoY3nsx562lA6kBld/CHrv2', NULL, '396050', '408133', '2025-10-14 07:43:10', NULL),
(234887, '979342', 'eDNnVHkxdWdpUitHY01nMVVSUk1DQT09OjoR1IIHkoueIceA9PIYwf4t', 'NURobzAvS1dUL0p5azdZMUxsYjRqZz09OjpmR7on96VVQQCd/qscNYyz', 'Nm1RSit1S1JyU1JGUFpyenFpeVFjdz09OjqUyTqXHuw0GdQmXdVwmf8B', 'bVBLOUhvNkpoaktBek5iZm5QL05GZz09Ojq0t+ByeFHtq4UjDujk2FN0', 'dzN1ZmlkZ1J0aFFpOVRJQmM2ZXJvUT09Ojox8Q3p+OXMeEjK1pctKpn5', '/images/user-profile/ronson_delena.png', NULL, NULL, '2025-07-02 15:15:13', NULL),
(278879, '967782', 'KzBNWmVZZVRySHBEdGZ0dmk4VFZmZz09Ojq0GBztgRk9YZN5KioGlluZ', 'cytxeDQ3UVZqOUphV2p5bkYzWEFhUT09OjrEXd241c1f2Vp8oA3xrVb4', 'TER1ZjlReHNVdFd0cG5wZDZvQTIyZz09OjpqrrghjwsRg0ASECuWwy60', 'T0IzcnkydnFNcDA1WnQvL2RBd3JQZz09OjrEig6fgI/Sw7HnPBhTI+TA', 'cGs0bnlUVjV4clpJdUlqdVptTUttQT09OjrtYetdmIKBPm6mKhYdMTMh', NULL, '396050', '408133', '2025-10-14 07:44:41', NULL),
(639723, '455232', 'bWgxWFBEWE95ckozZW9vLzlMRFV5Zz09OjqWPXPgqWBoQQ4NIHmaaMLL', NULL, 'NkpXdmY1bUdOenlmaWxNNWhIV0dJQT09Ojqq7FAZry+0vno7NVmgaroX', 'WkRnTHE0NWo5OTFYd2RYMUJzcHpJQT09Ojqw4qFDYPkPjlS/c3XGqnc9', 'SzVoZFVLdWxZbWhkT0FPYXBGWjJyUT09OjohkOyDwqWCFX6IO4swBZwY', NULL, '396050', '408133', '2025-07-04 17:45:32', NULL),
(889896, '428651', 'ZGVjWVA3US9UYk9qZWpXT0JDYVBzdz09OjogjyKnl9OCJAv3UqhOpWZp', 'SnRzUjFDN2NzZ0JVb3JyYnE4aVJrQT09OjpF6HfNy0gUnb1QvJRn6dOe', 'T3AvOVpEUDF0M2xDY01KVHU0VGQ2UT09Ojqt+Zn5dDqlW5mezK8TTqLZ', 'VFVZcmZieHBGdytxcWRtWGsxbWptdz09OjocqYZGn5XnWw+/82/w+e+U', 'dHNaak93bW81amNnK3dTR1pRSXppVTQ2NWVEMm5JRkl6MldFa3d1WldyQlZUK1lZMFY3ZFk5R3Y5UGV4R1F4OTo6QKG32Z0a2jnnimJg+qSKGA==', '/images/user-profile/miku_delena_2.jpg', '396050', '408133', '2025-07-02 16:20:33', NULL);

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
-- Indexes for table `deped_inventory_items`
--
ALTER TABLE `deped_inventory_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `fk_category` (`category_id`);

--
-- Indexes for table `deped_inventory_items_deleted`
--
ALTER TABLE `deped_inventory_items_deleted`
  ADD PRIMARY KEY (`deleted_id`);

--
-- Indexes for table `deped_inventory_item_category`
--
ALTER TABLE `deped_inventory_item_category`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `deped_inventory_notifications`
--
ALTER TABLE `deped_inventory_notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `deped_inventory_requests`
--
ALTER TABLE `deped_inventory_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `fk_request_user` (`user_id`);

--
-- Indexes for table `deped_inventory_request_items`
--
ALTER TABLE `deped_inventory_request_items`
  ADD PRIMARY KEY (`req_item_id`),
  ADD KEY `fk_req_item_request` (`request_id`),
  ADD KEY `fk_req_item_item` (`item_id`);

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
-- AUTO_INCREMENT for table `deped_inventory_items`
--
ALTER TABLE `deped_inventory_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2025857098;

--
-- AUTO_INCREMENT for table `deped_inventory_items_deleted`
--
ALTER TABLE `deped_inventory_items_deleted`
  MODIFY `deleted_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=999400;

--
-- AUTO_INCREMENT for table `deped_inventory_item_category`
--
ALTER TABLE `deped_inventory_item_category`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=983862;

--
-- AUTO_INCREMENT for table `deped_inventory_notifications`
--
ALTER TABLE `deped_inventory_notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `deped_inventory_requests`
--
ALTER TABLE `deped_inventory_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2025980706;

--
-- AUTO_INCREMENT for table `deped_inventory_request_items`
--
ALTER TABLE `deped_inventory_request_items`
  MODIFY `req_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `deped_inventory_user_info`
--
ALTER TABLE `deped_inventory_user_info`
  MODIFY `info_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=972898;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `deped_inventory_items`
--
ALTER TABLE `deped_inventory_items`
  ADD CONSTRAINT `fk_category` FOREIGN KEY (`category_id`) REFERENCES `deped_inventory_item_category` (`category_id`) ON DELETE CASCADE;

--
-- Constraints for table `deped_inventory_requests`
--
ALTER TABLE `deped_inventory_requests`
  ADD CONSTRAINT `fk_request_user` FOREIGN KEY (`user_id`) REFERENCES `deped_inventory_users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `deped_inventory_request_items`
--
ALTER TABLE `deped_inventory_request_items`
  ADD CONSTRAINT `fk_req_item_item` FOREIGN KEY (`item_id`) REFERENCES `deped_inventory_items` (`item_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_req_item_request` FOREIGN KEY (`request_id`) REFERENCES `deped_inventory_requests` (`request_id`) ON DELETE CASCADE ON UPDATE CASCADE;

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
