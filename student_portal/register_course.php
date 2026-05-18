<?php
session_start(); 
require 'config.php';
if (!isset($_SESSION['student_id'])) { header('Location: login.php'); exit; }
if (!isset($_GET['course_id'])) { header('Location: courses.php'); exit; }
$student_id = $_SESSION['student_id'];
$course_id = intval($_GET['course_id']);

// check already registered
$stmt = $conn->prepare("SELECT id FROM student_courses WHERE student_id = ? AND course_id = ? LIMIT 1");
$stmt->bind_param("ii", $student_id, $course_id);
$stmt->execute(); $stmt->store_result();
if ($stmt->num_rows > 0) {
    header('Location: dashboard.php'); exit;
}
$stmt->close();

// insert
$ins = $conn->prepare("INSERT INTO student_courses (student_id, course_id) VALUES (?, ?)");
$ins->bind_param("ii", $student_id, $course_id);
$ins->execute();
header('Location: dashboard.php'); exit;