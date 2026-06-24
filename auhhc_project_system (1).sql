-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 23, 2026 at 11:49 PM
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
-- Database: `auhhc_project_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `submissions`
--

CREATE TABLE `submissions` (
  `sub_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'proposal',
  `file_name` varchar(255) NOT NULL,
  `status` enum('Pending','Approved','Rejected','Needs Revision') NOT NULL DEFAULT 'Pending',
  `feedback` text DEFAULT NULL,
  `grade` varchar(5) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `submissions`
--

INSERT INTO `submissions` (`sub_id`, `student_id`, `title`, `description`, `type`, `file_name`, `status`, `feedback`, `grade`, `created_at`) VALUES
(1, 5, 'UPCS - Undergraduate Project Coordination System', 'A multi-role web system coordinating students, supervisors, and the HOD for final-year project management.', 'proposal', 'sample_upcs_proposal.pdf', 'Approved', 'Well-structured proposal. Approved to proceed.', 'A', '2026-06-19 15:27:04'),
(2, 6, 'Smart Attendance System Using QR Codes', 'A mobile-friendly attendance tracking system using QR code scanning.', 'proposal', 'sample_attendance_proposal.pdf', 'Pending', NULL, NULL, '2026-06-19 15:27:04'),
(3, 7, 'AI-Based Crop Disease Detection', 'An image-classification system to detect common crop diseases from leaf photos.', 'final_report', 'sample_crop_report.pdf', 'Needs Revision', 'Please expand the literature review and add more evaluation metrics.', NULL, '2026-06-19 15:27:04');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `fullname` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','supervisor','hod','admin') NOT NULL DEFAULT 'student',
  `phone` varchar(20) DEFAULT NULL,
  `department` varchar(150) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `assigned_supervisor_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `fullname`, `email`, `password`, `role`, `phone`, `department`, `bio`, `profile_picture`, `assigned_supervisor_id`, `created_at`) VALUES
(1, 'Dame Admin', 'admin@auhhc.edu.et', '$2b$10$oC4E1rzEjCgPkxOVbPRJoOQczKX1FHRbIv/El03xhv6eWplCsYxD2', 'admin', '0911000001', 'IT', 'System administrator account.', NULL, NULL, '2026-06-19 15:27:04'),
(2, 'Dr. Tolera Bekele', 'hod.it@auhhc.edu.et', '$2b$10$oC4E1rzEjCgPkxOVbPRJoOQczKX1FHRbIv/El03xhv6eWplCsYxD2', 'hod', '0911000002', 'Information Technology', 'Head of Department, IT.', NULL, NULL, '2026-06-19 15:27:04'),
(3, 'Eng. Chala Gemechu', 'chala.supervisor@auhhc.edu.et', '$2b$10$oC4E1rzEjCgPkxOVbPRJoOQczKX1FHRbIv/El03xhv6eWplCsYxD2', 'supervisor', '0911000003', 'Information Technology', 'Senior lecturer supervising final-year IT projects.', NULL, NULL, '2026-06-19 15:27:04'),
(4, 'Eng. Sara Mohammed', 'sara.supervisor@auhhc.edu.et', '$2b$10$oC4E1rzEjCgPkxOVbPRJoOQczKX1FHRbIv/El03xhv6eWplCsYxD2', 'supervisor', '0911000004', 'Information Technology', 'Lecturer supervising web & mobile projects.', NULL, NULL, '2026-06-19 15:27:04'),
(5, 'Dame Tesfaye', 'dame.student@auhhc.edu.et', '$2b$10$oC4E1rzEjCgPkxOVbPRJoOQczKX1FHRbIv/El03xhv6eWplCsYxD2', 'student', '0911000005', 'Information Technology', 'Final-year IT student, Group G-3.', NULL, 3, '2026-06-19 15:27:04'),
(6, 'Bontu Lemma', 'bontu.student@auhhc.edu.et', '$2b$10$oC4E1rzEjCgPkxOVbPRJoOQczKX1FHRbIv/El03xhv6eWplCsYxD2', 'student', '0911000006', 'Information Technology', 'Final-year IT student.', NULL, 3, '2026-06-19 15:27:04'),
(7, 'Yonas Girma', 'yonas.student@auhhc.edu.et', '$2b$10$oC4E1rzEjCgPkxOVbPRJoOQczKX1FHRbIv/El03xhv6eWplCsYxD2', 'student', '0911000007', 'Information Technology', 'Final-year IT student.', NULL, 4, '2026-06-19 15:27:04'),
(8, 'hani', 'hani@gmail.com', '$2y$10$Nja1JD/Is0dSKVNL7yyq9eQXgw.DnlO47kSitUp7RASZRUPZnn9l2', 'student', NULL, NULL, NULL, NULL, NULL, '2026-06-21 22:01:50'),
(9, 'yoonii', 'yoonii@gmail.com', '$2y$10$boLDlKvKNozn6ednjrIwIeNqIWljh38zakz6dBSa95L8iFxCn46iG', 'hod', NULL, NULL, NULL, NULL, NULL, '2026-06-21 22:02:54'),
(10, 'barnee', 'barnaabse@gmail.com', '$2y$10$jBS.OVaCQ.0SBck1J5.3HeQc7GWc6SiLXuTaWd0AIqiML2JHmGrjO', 'student', NULL, NULL, NULL, NULL, NULL, '2026-06-22 15:50:25'),
(11, 'hana ', 'hana1@gmail.com', '$2y$10$oLjMRwyMYDr5435/z/suD.8e1SLf96vR7qwcLGspZwCMAaKOPMNgi', 'supervisor', NULL, NULL, NULL, NULL, NULL, '2026-06-22 15:51:31'),
(12, 'tola', 'tola@gmail.com', '$2y$10$cAzLxTtuvcWkPMfrtrGea.gV0lKwYjftVdvtPlGHPI0XqzY.ypLWC', 'hod', NULL, NULL, NULL, NULL, NULL, '2026-06-22 15:52:05'),
(13, 'biinii', 'biinii@gmail.com', '$2y$10$AY4DRH3FoimpYfnuUaANB.vv2.c47ccnMUV9tW9C64.BcbKnG1sya', 'admin', NULL, NULL, NULL, NULL, NULL, '2026-06-22 15:52:49'),
(15, 'damed', 'D@gmail.com', '$2y$10$HlAustXgbb3lCXVFPlDANeNEpSe83woWJkQJozdKTCvIiDmOyeyBq', 'supervisor', NULL, NULL, NULL, NULL, NULL, '2026-06-22 16:16:11');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `submissions`
--
ALTER TABLE `submissions`
  ADD PRIMARY KEY (`sub_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `assigned_supervisor_id` (`assigned_supervisor_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `submissions`
--
ALTER TABLE `submissions`
  MODIFY `sub_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `submissions`
--
ALTER TABLE `submissions`
  ADD CONSTRAINT `fk_submissions_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_supervisor` FOREIGN KEY (`assigned_supervisor_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
