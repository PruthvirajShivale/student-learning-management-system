-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 18, 2026 at 06:27 PM
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
-- Database: `student`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `email`, `password`) VALUES
(3, 'admin@gmail.com', 'admin123');

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

CREATE TABLE `assignments` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `due_date` date NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `assign_to` varchar(50) NOT NULL DEFAULT 'all'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assignment_messages`
--

CREATE TABLE `assignment_messages` (
  `id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assignment_messages`
--

INSERT INTO `assignment_messages` (`id`, `assignment_id`, `student_id`, `message`, `sent_at`) VALUES
(4, 17, 14, 're submit your assignment', '2026-02-04 10:48:09');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `lecture_id` int(11) NOT NULL,
  `session_date` date NOT NULL,
  `status` enum('present','absent') DEFAULT 'absent',
  `marked_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `course_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `instructor_name` varchar(100) DEFAULT NULL,
  `schedule_day` varchar(20) DEFAULT NULL,
  `schedule_time` varchar(20) DEFAULT NULL,
  `total_seats` int(11) DEFAULT 60,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `course_code`, `course_name`, `description`, `instructor_name`, `schedule_day`, `schedule_time`, `total_seats`, `created_at`) VALUES
(15, 'CS1020', 'Python Programming', 'This course is designed for absolute beginners to learn Python programming from scratch. Students will gain a strong foundation in programming fundamentals, understand Python syntax, work with variables, data types, conditional statements, loops, functions, and hands-on exercises to apply their learning.\r\n\r\nCourse Duration: 4–6 weeks (adjust as per your course)\r\n\r\nMode of Learning: Online / Video Lectures + Notes + Exercises\r\n\r\nCourse Highlights:\r\n\r\nStep-by-step learning from basics to advanced topics\r\n\r\nTheory notes + practice exercises for each lecture\r\n\r\nHands-on coding examples to reinforce concepts\r\n\r\nSuitable for school/college students and beginners in programming\r\n\r\nTarget Audience:\r\n\r\nAbsolute beginners in programming\r\n\r\nStudents preparing for placements\r\n\r\nAnyone interested in learning Python and programming fundamentals\r\n\r\nOutcome / Learning Objectives:\r\nBy the end of this course, students will be able to:\r\n\r\nUnderstand programming fundamentals and Python basics\r\n\r\nWrite Python programs using variables, loops, and functions\r\n\r\nSolve simple real-world problems with Python\r\n\r\nPrepare for coding assessments and build a foundation for advanced Python topics', 'Pruthviraj Shivale - Python Trainer & Software Developer', 'Monday', '06:00 PM', 120, '2026-02-03 04:43:54'),
(17, 'CS1021', 'C++ Programming', 'This course is designed for absolute beginners to learn Python programming from scratch. Students will gain a strong foundation in programming fundamentals, understand Python syntax, work with variables, data types, conditional statements, loops, functions, and hands-on exercises to apply their learning.\r\n\r\nCourse Duration: 4–6 weeks (adjust as per your course)\r\n\r\nMode of Learning: Online / Video Lectures + Notes + Exercises\r\n\r\nCourse Highlights:\r\n\r\nStep-by-step learning from basics to advanced topics\r\n\r\nTheory notes + practice exercises for each lecture\r\n\r\nHands-on coding examples to reinforce concepts\r\n\r\nSuitable for school/college students and beginners in programming\r\n\r\nTarget Audience:\r\n\r\nAbsolute beginners in programming\r\n\r\nStudents preparing for placements\r\n\r\nAnyone interested in learning Python and programming fundamentals\r\n\r\nOutcome / Learning Objectives:\r\nBy the end of this course, students will be able to:\r\n\r\nUnderstand programming fundamentals and Python basics\r\n\r\nWrite Python programs using variables, loops, and functions\r\n\r\nSolve simple real-world problems with Python\r\n\r\nPrepare for coding assessments and build a foundation for advanced Python topics', 'Pruthviraj Shivale - Python Trainer & Software Developer', 'Monday', '06:00 PM', 60, '2026-02-03 09:05:49'),
(18, 'CS1023', 'DSA', 'This course is designed for absolute beginners to learn Python programming from scratch. Students will gain a strong foundation in programming fundamentals, understand Python syntax, work with variables, data types, conditional statements, loops, functions, and hands-on exercises to apply their learning.\r\n\r\n', 'Pruthviraj Shivale - Full stack  Software Developer', 'Monday', '06:00 PM', 60, '2026-04-22 05:58:02');

-- --------------------------------------------------------

--
-- Table structure for table `course_lectures`
--

CREATE TABLE `course_lectures` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `lecture_date` date DEFAULT NULL,
  `lecture_time` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course_lectures`
--

INSERT INTO `course_lectures` (`id`, `course_id`, `title`, `description`, `file_path`, `file_type`, `lecture_date`, `lecture_time`, `created_at`) VALUES
(10, 15, 'Introduction to Python & Programming Fundamentals ', 'In this lecture, students will learn the fundamentals of programming and get introduced to the Python language. We will explore what programming is, why Python is widely used, and how it is applied in real-world projects.\r\nBy the end of this session, students will be able to install Python, set up their development environment, and write their first Python program.\r\n\r\nTopics Covered:\r\n\r\nWhat is Programming?\r\nWhy Choose Python?\r\nInstalling Python (Step-by-Step Guide)\r\nSetting up an IDE (VS Code / PyCharm)\r\nWriting Your First Program (Hello World)\r\nUnderstanding Basic Syntax', '1778751389_Python.mp4', 'mp4', '2026-02-20', '18:00', '2026-02-03 04:48:59'),
(11, 15, 'Variables, Data Types & Basic Operators', 'Students will learn about variables, different data types in Python, and how to use operators. The lecture focuses on understanding data storage, performing arithmetic operations, and using logical operators.\r\n\r\nCovered Topics:\r\n\r\nWhat is a variable? Naming rules & conventions\r\n\r\nPython Data Types: int, float, str, bool\r\n\r\nType conversion (int(), str(), etc.)\r\n\r\nArithmetic, assignment, comparison, and logical operators', '1778751455_Python 2.mp4', 'mp4', '2026-02-22', '18:00', '2026-02-03 06:55:37'),
(12, 15, 'Conditional Statements & Loops', 'This lecture focuses on controlling program flow using conditional statements and loops. Students will learn to make decisions and execute repetitive tasks efficiently.\r\n\r\nCovered Topics:\r\nIf, elif, else statements\r\nNested conditions\r\nLogical & comparison operators in conditions\r\nWhile loop syntax & examples\r\nFor loop syntax, range, list iteration\r\nBreak & Continue statements', '1778751478_WhatsApp Video 2026-02-03 at 1.44.27 PM.mp4', 'mp4', '2026-02-24', '18:00', '2026-02-03 08:19:31'),
(13, 15, 'Functions & Modular Programming', 'Students will learn how to organize code using functions. This lecture explains defining functions, passing parameters, returning values, and understanding variable scope. Students will practice creating small modular programs.\r\n\r\nCovered Topics:\r\n\r\nWhat is a function? Why use functions?\r\n\r\nDefining a function (def)\r\n\r\nFunction parameters & return statement\r\n\r\nLocal vs global variables\r\n\r\nBuilt-in functions vs user-defined functions', '1778751508_Python.mp4', 'mp4', '2026-02-26', '13:51', '2026-02-03 08:22:24');

-- --------------------------------------------------------

--
-- Table structure for table `course_notes`
--

CREATE TABLE `course_notes` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `lecture_id` int(11) NOT NULL,
  `note` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `is_pinned` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `category` enum('general','code','important','task') DEFAULT 'general',
  `last_edited` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course_notes`
--

INSERT INTO `course_notes` (`id`, `course_id`, `lecture_id`, `note`, `file_path`, `file_type`, `is_pinned`, `created_at`, `category`, `last_edited`) VALUES
(12, 15, 10, '<p><strong><u>Module 1 – Introduction to Python – Theory Notes</u></strong></p><ul><li>Programming basics &amp; examples</li><li>Python overview &amp; advantages</li><li>Installing Python &amp; IDE setup</li><li>Basic syntax: indentation, comments, naming rules</li><li>Introduction to operators</li></ul><p><br></p>', '1770104872_May_Jun_2024.pdf', 'pdf', 0, '2026-02-03 13:17:53', 'general', '2026-05-15 03:35:58'),
(14, 15, 10, '<p><strong>Module 1 – Introduction to Python – Practice Notes</strong></p><ul><li>Hello World program</li><li>Print your name</li><li>Add two numbers</li><li>Take input from user</li></ul><p><br></p>', '1770113709_SPOS_UNIT_5__1_.pdf', 'pdf', 1, '2026-02-03 15:45:11', 'general', '2026-05-15 03:35:58');

-- --------------------------------------------------------

--
-- Table structure for table `files`
--

CREATE TABLE `files` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `is_shared` tinyint(1) DEFAULT 0,
  `share_token` varchar(100) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `files`
--

INSERT INTO `files` (`id`, `student_id`, `file_name`, `file_path`, `file_type`, `is_shared`, `share_token`, `uploaded_at`) VALUES
(8, 17, 'WT-miniproject-report_format.pdf', 'uploads/1776834395_b5b042fb797a.pdf', 'notes', 0, NULL, '2026-04-22 05:06:35'),
(9, 17, 'WhatsApp Image 2026-05-11 at 5.29.45 PM.jpeg', 'uploads/1778750177_1b828d670dfc.jpeg', 'marksheet', 0, NULL, '2026-05-14 09:16:17');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `course_id` int(11) DEFAULT NULL,
  `assignment_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `student_id`, `title`, `message`, `link`, `is_read`, `created_at`, `course_id`, `assignment_id`) VALUES
(43, 17, 'Notice', 'Today is Last date of Submission', NULL, 0, '2026-04-22 10:34:42', 15, NULL),
(45, 17, 'New Course Added', 'A new course \"DSA\" has been added. Check courses page.', 'courses.php', 0, '2026-04-22 11:28:02', NULL, NULL),
(46, 18, 'New Course Added', 'A new course \"DSA\" has been added. Check courses page.', 'courses.php', 0, '2026-04-22 11:28:02', NULL, NULL),
(47, 17, '📅 Final Examination Timetable - Spring 2026', 'The official timetable for the upcoming final examinations has been released. You can find your specific slot, room number, and seat assignment in the attached document. Please arrive at least 15 minutes before your scheduled start time. Good luck with your studies!', 'uploads/notifications/1778755818_Final Examination Timetable - Spring 2026.pdf', 0, '2026-05-14 16:20:19', NULL, NULL),
(48, 18, '📅 Final Examination Timetable - Spring 2026', 'The official timetable for the upcoming final examinations has been released. You can find your specific slot, room number, and seat assignment in the attached document. Please arrive at least 15 minutes before your scheduled start time. Good luck with your studies!', 'uploads/notifications/1778755818_Final Examination Timetable - Spring 2026.pdf', 0, '2026-05-14 16:20:19', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `roll_no` varchar(50) NOT NULL,
  `college` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `contact` varchar(20) NOT NULL,
  `parent_contact` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `name`, `roll_no`, `college`, `email`, `contact`, `parent_contact`, `password`, `created_at`) VALUES
(17, 'Pruthviraj Shivale ', '71', 'JSMP\'s BSIOTR Wagholi Pune', 'pruthvirajshivale20@gmail.com', '8123456790', '9123456780', '$2y$10$Qv0mNaxPeYphH2yUz2A3X.tgcA9.IB2h3Vh64jqguZEsg7Lu860au', '2026-04-12 08:52:04'),
(18, 'Om ', '73', 'JSMP\'s BSIOTR Wagholi Pune', 'ochaudhari2004@gmail.com', '8765432190', '9876543210', '$2y$10$iLmXdVDnG4R8aJS2mHkf3O5sgiAQ5nvGJWDhMQe7liaZo.K1P/eR.', '2026-04-22 05:57:09');

-- --------------------------------------------------------

--
-- Table structure for table `student_activity`
--

CREATE TABLE `student_activity` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `lecture_id` int(11) NOT NULL,
  `join_time` datetime DEFAULT NULL,
  `leave_time` datetime DEFAULT NULL,
  `duration_minutes` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_activity`
--

INSERT INTO `student_activity` (`id`, `student_id`, `course_id`, `lecture_id`, `join_time`, `leave_time`, `duration_minutes`) VALUES
(20, 17, 15, 10, '2026-05-14 15:04:02', NULL, 0),
(21, 17, 15, 10, '2026-05-14 15:10:32', NULL, 0),
(22, 17, 15, 10, '2026-05-14 15:50:50', NULL, 0),
(23, 17, 15, 10, '2026-05-14 16:22:23', NULL, 0),
(24, 17, 15, 10, '2026-05-14 22:02:56', NULL, 0),
(25, 17, 15, 10, '2026-05-15 09:20:40', NULL, 0),
(26, 17, 15, 10, '2026-05-15 09:21:46', NULL, 0),
(27, 17, 15, 10, '2026-05-15 09:21:50', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `student_courses`
--

CREATE TABLE `student_courses` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `registered_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_courses`
--

INSERT INTO `student_courses` (`id`, `student_id`, `course_id`, `registered_at`) VALUES
(8, 14, 15, '2026-02-03 05:34:20'),
(9, 14, 17, '2026-02-04 09:07:41'),
(10, 15, 15, '2026-02-09 10:10:58'),
(11, 17, 15, '2026-04-12 08:53:16'),
(12, 17, 18, '2026-04-22 05:58:19');

-- --------------------------------------------------------

--
-- Table structure for table `submissions`
--

CREATE TABLE `submissions` (
  `id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_assignments_course` (`course_id`);

--
-- Indexes for table `assignment_messages`
--
ALTER TABLE `assignment_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_attendance` (`student_id`,`lecture_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `lecture_id` (`lecture_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `course_lectures`
--
ALTER TABLE `course_lectures`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `course_notes`
--
ALTER TABLE `course_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `lecture_id` (`lecture_id`);
ALTER TABLE `course_notes` ADD FULLTEXT KEY `note` (`note`);

--
-- Indexes for table `files`
--
ALTER TABLE `files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_files_student` (`student_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roll_no` (`roll_no`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `student_activity`
--
ALTER TABLE `student_activity`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `lecture_id` (`lecture_id`);

--
-- Indexes for table `student_courses`
--
ALTER TABLE `student_courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`,`course_id`),
  ADD KEY `idx_course_id` (`course_id`);

--
-- Indexes for table `submissions`
--
ALTER TABLE `submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assignment_id` (`assignment_id`),
  ADD KEY `student_id` (`student_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `assignment_messages`
--
ALTER TABLE `assignment_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `course_lectures`
--
ALTER TABLE `course_lectures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `course_notes`
--
ALTER TABLE `course_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `files`
--
ALTER TABLE `files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `student_activity`
--
ALTER TABLE `student_activity`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `student_courses`
--
ALTER TABLE `student_courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `submissions`
--
ALTER TABLE `submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `fk_assignments_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_lectures`
--
ALTER TABLE `course_lectures`
  ADD CONSTRAINT `fk_course_lectures` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_notes`
--
ALTER TABLE `course_notes`
  ADD CONSTRAINT `course_notes_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_notes_ibfk_2` FOREIGN KEY (`lecture_id`) REFERENCES `course_lectures` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `files`
--
ALTER TABLE `files`
  ADD CONSTRAINT `fk_files_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `submissions`
--
ALTER TABLE `submissions`
  ADD CONSTRAINT `submissions_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `submissions_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
