<?php
session_start(); 
require 'config.php';
if (!isset($_SESSION['student_id'])) { header('Location: login.php'); exit; }
if (!isset($_GET['course_id'])) { header('Location: dashboard.php'); exit; }
$student_id = $_SESSION['student_id'];
$course_id = intval($_GET['course_id']);
$stmt = $conn->prepare("DELETE FROM student_courses WHERE student_id = ? AND course_id = ?");
$stmt->bind_param("ii", $student_id, $course_id);
$stmt->execute();
header('Location: dashboard.php'); exit;