-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 04, 2025 at 11:21 PM
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
-- Database: `student_management_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_terms`
--

CREATE TABLE `academic_terms` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`, `firstname`, `lastname`, `email`, `created_at`, `updated_at`) VALUES
(3, 'root', '$2y$10$tUkei1R0uzB4rkqwqEhBdO0fPlu8azJrOHTJl9fdnSOdU3IxKXUnm', 'SIAS', 'ADMIN', 'sias@school.com', '2025-05-01 07:41:24', '2025-05-01 07:41:24');

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

CREATE TABLE `assignments` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` date NOT NULL,
  `subject_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assignments`
--

INSERT INTO `assignments` (`id`, `title`, `description`, `due_date`, `subject_id`, `created_at`) VALUES
(13, '', NULL, '0000-00-00', 2, '2025-05-01 15:01:58'),
(14, 'sing', 'make a video of you singing minimum of 1:30 seconds', '2025-05-11', 2, '2025-05-04 06:24:04'),
(17, 'Solve the problem of love', 'Show your solutions', '2025-05-12', 1, '2025-05-04 21:10:44'),
(18, 'Calculate the x', 'Show your solution', '2025-05-12', 1, '2025-05-04 21:11:51');

-- --------------------------------------------------------

--
-- Table structure for table `assignment_responses`
--

CREATE TABLE `assignment_responses` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `response` text NOT NULL,
  `submission_date` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assignment_submissions`
--

CREATE TABLE `assignment_submissions` (
  `id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `status` enum('pending','completed') DEFAULT 'pending',
  `file_path` varchar(255) DEFAULT NULL,
  `grade` int(11) DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assignment_submissions`
--

INSERT INTO `assignment_submissions` (`id`, `assignment_id`, `student_id`, `status`, `file_path`, `grade`, `submitted_at`) VALUES
(10, 18, 44, 'completed', 'uploads/assignments/6817d8ced96ae_681705ce95848_NB Small size.docx', 10, '2025-05-04 21:14:54');

-- --------------------------------------------------------

--
-- Table structure for table `class_schedules`
--

CREATE TABLE `class_schedules` (
  `id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `room` varchar(50) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `semester_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_schedules`
--

INSERT INTO `class_schedules` (`id`, `subject_id`, `teacher_id`, `room`, `day_of_week`, `start_time`, `end_time`, `semester_id`, `created_at`, `updated_at`) VALUES
(16, 1, 18, '201', 'Monday', '06:00:00', '07:00:00', 1, '2025-05-04 21:07:12', '2025-05-04 21:07:12'),
(17, 2, 19, '301', 'Tuesday', '07:00:00', '08:00:00', 1, '2025-05-04 21:07:31', '2025-05-04 21:07:31'),
(18, 3, 20, '401', 'Wednesday', '09:00:00', '10:00:00', 1, '2025-05-04 21:07:48', '2025-05-04 21:07:48'),
(19, 6, 18, '202', 'Thursday', '11:00:00', '12:00:00', 1, '2025-05-04 21:08:11', '2025-05-04 21:08:11'),
(20, 7, 21, '302', 'Friday', '13:00:00', '14:00:00', 1, '2025-05-04 21:08:40', '2025-05-04 21:08:40'),
(21, 8, 22, '402', 'Saturday', '15:00:00', '16:00:00', 1, '2025-05-04 21:09:06', '2025-05-04 21:09:06');

-- --------------------------------------------------------

--
-- Table structure for table `dashboard_items`
--

CREATE TABLE `dashboard_items` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `target_audience` enum('all','students','teachers') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `teacher_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`id`, `student_id`, `subject_id`, `teacher_id`) VALUES
(107, 44, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `grades`
--

CREATE TABLE `grades` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `subject_name` varchar(100) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `school_year` varchar(20) DEFAULT NULL,
  `grade` decimal(5,2) DEFAULT NULL,
  `remarks` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `prelim` decimal(5,2) DEFAULT NULL,
  `midterm` decimal(5,2) DEFAULT NULL,
  `finals` decimal(5,2) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `semestral_grade` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grades`
--

INSERT INTO `grades` (`id`, `student_id`, `teacher_id`, `subject_name`, `semester`, `school_year`, `grade`, `remarks`, `created_at`, `prelim`, `midterm`, `finals`, `subject_id`, `semestral_grade`) VALUES
(2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-04-27 19:58:57', 90.00, 0.00, 0.00, 6, NULL),
(27, 44, NULL, NULL, NULL, NULL, NULL, NULL, '2025-05-04 21:09:43', 90.00, NULL, NULL, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notification`
--

CREATE TABLE `notification` (
  `id` int(11) NOT NULL,
  `message` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0,
  `user_id` int(11) DEFAULT NULL,
  `user_type` enum('admin','teacher','student') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `schedule_enrollments`
--

CREATE TABLE `schedule_enrollments` (
  `id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `status` enum('enrolled','dropped') DEFAULT 'enrolled',
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedule_enrollments`
--

INSERT INTO `schedule_enrollments` (`id`, `schedule_id`, `student_id`, `status`, `enrolled_at`) VALUES
(39, 16, 44, 'enrolled', '2025-05-04 21:09:43');

-- --------------------------------------------------------

--
-- Table structure for table `semesters`
--

CREATE TABLE `semesters` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `semesters`
--

INSERT INTO `semesters` (`id`, `name`, `is_active`, `created_at`) VALUES
(1, '1st Semester', 1, '2025-05-01 10:38:40'),
(2, '2nd Semester', 1, '2025-05-01 10:38:40');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `lastname` varchar(100) DEFAULT NULL,
  `firstname` varchar(100) DEFAULT NULL,
  `middlename` varchar(100) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `grade_level` varchar(50) DEFAULT NULL,
  `program` varchar(100) DEFAULT NULL,
  `previous_school` varchar(150) DEFAULT NULL,
  `enrollment_date` date DEFAULT NULL,
  `student_type` varchar(20) DEFAULT NULL,
  `parent_name` varchar(150) DEFAULT NULL,
  `parent_relationship` varchar(50) DEFAULT NULL,
  `parent_phone` varchar(20) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` varchar(50) DEFAULT 'student',
  `balance` decimal(10,2) DEFAULT 0.00,
  `grade` varchar(10) DEFAULT NULL,
  `approval_status` enum('pending','approved','rejected') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `lastname`, `firstname`, `middlename`, `dob`, `gender`, `email`, `phone`, `address`, `grade_level`, `program`, `previous_school`, `enrollment_date`, `student_type`, `parent_name`, `parent_relationship`, `parent_phone`, `username`, `password`, `created_at`, `role`, `balance`, `grade`, `approval_status`) VALUES
(44, 'Abarca', 'John Andy', NULL, NULL, NULL, 'andy@gmail.com', NULL, NULL, '1st Year', 'BSIT', NULL, NULL, NULL, NULL, NULL, NULL, 'andy', '$2y$10$uORwvYNnYlt6HcLsrwskz.OsoRrrtrFqoV3tKI8lwuWEoNeom2jJ2', '2025-05-04 20:54:50', 'student', 0.00, NULL, 'approved'),
(45, 'Paderes', 'John Mark', NULL, NULL, NULL, 'jm@gmail.com', NULL, NULL, '1st Year', 'BSHM', NULL, NULL, NULL, NULL, NULL, NULL, 'jm123', '$2y$10$T8M0EdlstMFqnlZMGe.c5eixvWeP/9CIVZmlqv5Ruf3OhO3Dh1tPG', '2025-05-04 20:55:27', 'student', 0.00, NULL, 'approved'),
(46, 'Enmacino', 'Regine', NULL, NULL, NULL, 'regine@gmail.com', NULL, NULL, '1st Year', 'BSP', NULL, NULL, NULL, NULL, NULL, NULL, 'regine', '$2y$10$bBl9PV1FgMDBJ8liyODr7O.3F34olVCDgV.EGirUGeCh0Jdt2Nx7W', '2025-05-04 20:55:55', 'student', 0.00, NULL, 'approved'),
(47, 'Pacoma', 'Mariella', NULL, NULL, NULL, 'maye@gmail.com', NULL, NULL, '1st Year', 'BLIS', NULL, NULL, NULL, NULL, NULL, NULL, 'maye', '$2y$10$zfeDSQllNLPPUaevV6FEZO/r3./bETdIHIA6zggXHeXWgmme12wfC', '2025-05-04 20:56:45', 'student', 0.00, NULL, 'approved'),
(48, 'Felicilda', 'DM', NULL, NULL, NULL, 'dm@gmail.com', NULL, NULL, '1st Year', 'BSCRIM', NULL, NULL, NULL, NULL, NULL, NULL, 'dm', '$2y$10$jTQ2LUdAPSmSCkme0f.GtuccH8ydmvm91yf7mpiTOvvLjkyBBxQfS', '2025-05-04 20:57:09', 'student', 0.00, NULL, 'approved'),
(49, 'Guerrero', 'Jayson', NULL, NULL, NULL, 'jayson@gmail.com', NULL, NULL, '1st Year', 'BSIT', NULL, NULL, NULL, NULL, NULL, NULL, 'jayson', '$2y$10$kghWVxwJ23OyZNBGrM2GbuQWRCJxMqEBr3.6NKkJ930pZ.EG9G10K', '2025-05-04 20:57:42', 'student', 0.00, NULL, 'approved'),
(50, 'Estella', 'Justin', NULL, NULL, NULL, 'justin@gmail.com', NULL, NULL, '1st Year', 'BSHM', NULL, NULL, NULL, NULL, NULL, NULL, 'justin', '$2y$10$lBVynSIj9pbZ.cBvQ82vEuTz6osAUFTXyXdpskZsqM1DuqeV2qTmW', '2025-05-04 20:58:07', 'student', 0.00, NULL, 'approved'),
(51, 'Lesiges', 'Angelo', NULL, NULL, NULL, 'gelo@gmail.com', NULL, NULL, '1st Year', 'BSP', NULL, NULL, NULL, NULL, NULL, NULL, 'gelo', '$2y$10$HYT8C89OdyWceEVP3oa35OJAIrCM9sCTJQpOUBZc8JrEMo/uOImlC', '2025-05-04 20:58:28', 'student', 0.00, NULL, 'approved');

-- --------------------------------------------------------

--
-- Table structure for table `student_subjects`
--

CREATE TABLE `student_subjects` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `subject_code` varchar(50) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `units` int(11) NOT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `year_level` varchar(20) DEFAULT NULL,
  `school_year` varchar(9) DEFAULT NULL,
  `program` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `units`, `semester`, `year_level`, `school_year`, `program`) VALUES
(1, 'MATH101', 'College Algebra', 4, '1st Semester', '1st Year', '2024-2025', 'BSIT'),
(2, 'ENG102', 'Communication Skills', 3, '1st Semester', '1st Year', '2024-2025', NULL),
(3, 'CS103', 'Introduction to Programming', 3, '1st Semester', '1st Year', '2024-2025', NULL),
(6, 'IT101                                             ', 'Fundamentals of Information Technology', 3, '2nd Semester', '1st Year', '2024-2025', 'BSIT'),
(7, 'CS 101', 'Introduction to Computer Science', 3, '1st Semester', '1st Year', '2024-2025', NULL),
(8, 'CCS 1101', 'Fundamentals of Programming', 0, '1st Semester', NULL, '2024-2025', NULL),
(11, 'RZ', 'RIZAL', 3, '1st Semester', '1st Year', '2024-2025', 'BSIT');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `section` varchar(50) DEFAULT NULL,
  `program` varchar(255) DEFAULT NULL,
  `grade_level` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `lastname`, `firstname`, `email`, `phone`, `username`, `password`, `created_at`, `section`, `program`, `grade_level`) VALUES
(18, 'Ibarra', 'Crisostomo', 'juan@gmail.com', '09927053032', 'juan', '$2y$10$2AayjticVh0LkWYT0yDGy.SoRBla.LMAoi93crpCAU3az.4uoraQG', '2025-05-04 21:02:59', NULL, 'BSIT', '1st Year'),
(19, 'Cervera', 'Lailanie', 'lai@gmail.com', '09477585121', 'lai', '$2y$10$mkcCg1XGW0gpTHkHEqoUP.1Tcaa.D77i5TZwSJTK/okzAAP0KwvF6', '2025-05-04 21:03:25', NULL, 'BSHM', '1st Year'),
(20, 'Clara', 'Maria', 'maria@gmail.com', '09197345191', 'maria', '$2y$10$XPOeBYOX9uQNquHflmuMKOBIxCtVYtHZFZh6jmRtwiV86FCP2JhMa', '2025-05-04 21:03:58', NULL, 'BSP', '1st Year'),
(21, 'Rubio', 'Rewin', 'rewin@gmail.com', '09459616965', 'rewin', '$2y$10$IHqqWrpt3yx/hGAQAg0YZe7cB6cS5x4q4t8cz.JsioEIhla1FsR/q', '2025-05-04 21:04:49', NULL, 'BSCRIM', '1st Year'),
(22, 'Hatake', 'Kakashi', 'kakashi@gmail.com', '09091914525', 'kakashi', '$2y$10$kpSK9Ws4Bg.2IKuIjzsErupdEWurHiV1iAj2.YJJbXH/d3F3RyinS', '2025-05-04 21:05:38', NULL, 'BLIS', '1st Year');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_subjects`
--

CREATE TABLE `teacher_subjects` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wallet_transactions`
--

CREATE TABLE `wallet_transactions` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` varchar(20) DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_terms`
--
ALTER TABLE `academic_terms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `assignment_responses`
--
ALTER TABLE `assignment_responses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `assignment_id` (`assignment_id`);

--
-- Indexes for table `assignment_submissions`
--
ALTER TABLE `assignment_submissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_submission` (`assignment_id`,`student_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `class_schedules`
--
ALTER TABLE `class_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `semester_id` (`semester_id`);

--
-- Indexes for table `dashboard_items`
--
ALTER TABLE `dashboard_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `grades`
--
ALTER TABLE `grades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `grades_teacher_fk` (`teacher_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `notification`
--
ALTER TABLE `notification`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `schedule_enrollments`
--
ALTER TABLE `schedule_enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_enrollment` (`schedule_id`,`student_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `semesters`
--
ALTER TABLE `semesters`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_subjects`
--
ALTER TABLE `student_subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_terms`
--
ALTER TABLE `academic_terms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `assignment_responses`
--
ALTER TABLE `assignment_responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `assignment_submissions`
--
ALTER TABLE `assignment_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `class_schedules`
--
ALTER TABLE `class_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `dashboard_items`
--
ALTER TABLE `dashboard_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=108;

--
-- AUTO_INCREMENT for table `grades`
--
ALTER TABLE `grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `notification`
--
ALTER TABLE `notification`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `schedule_enrollments`
--
ALTER TABLE `schedule_enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `semesters`
--
ALTER TABLE `semesters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `student_subjects`
--
ALTER TABLE `student_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `assignment_responses`
--
ALTER TABLE `assignment_responses`
  ADD CONSTRAINT `assignment_responses_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assignment_responses_ibfk_2` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `assignment_submissions`
--
ALTER TABLE `assignment_submissions`
  ADD CONSTRAINT `assignment_submissions_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assignment_submissions_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `class_schedules`
--
ALTER TABLE `class_schedules`
  ADD CONSTRAINT `class_schedules_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_schedules_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_schedules_ibfk_3` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`);

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`),
  ADD CONSTRAINT `enrollments_ibfk_3` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `grades`
--
ALTER TABLE `grades`
  ADD CONSTRAINT `grades_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grades_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grades_teacher_fk` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `schedule_enrollments`
--
ALTER TABLE `schedule_enrollments`
  ADD CONSTRAINT `schedule_enrollments_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `class_schedules` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `schedule_enrollments_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_subjects`
--
ALTER TABLE `student_subjects`
  ADD CONSTRAINT `student_subjects_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  ADD CONSTRAINT `teacher_subjects_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD CONSTRAINT `wallet_transactions_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
