<?php
session_start(); 
require 'config.php';
if (!isset($_SESSION['student_id'])) { header('Location: login.php'); exit; }
if (!isset($_GET['file_id'])) { header('Location: dashboard.php'); exit; }
$file_id = intval($_GET['file_id']);

$stmt = $conn->prepare("SELECT file_path, student_id FROM files WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $file_id);
$stmt->execute(); $res = $stmt->get_result();
if ($res->num_rows === 0) { header('Location: dashboard.php'); exit; }
$row = $res->fetch_assoc();
if ($row['student_id'] != $_SESSION['student_id']) { echo "Unauthorized."; exit; }

// delete file from disk
$full = __DIR__ . '/' . $row['file_path'];
if (file_exists($full)) @unlink($full);

// delete DB row
$dstmt = $conn->prepare("DELETE FROM files WHERE id = ?");
$dstmt->bind_param("i", $file_id);
$dstmt->execute();
header('Location: dashboard.php'); exit;