<?php
session_start(); 
require 'config.php';

// If token provided, allow public download
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $stmt = $conn->prepare("SELECT file_path, file_name FROM files WHERE share_token = ? AND is_shared = 1 LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute(); $res = $stmt->get_result();
    if ($res->num_rows === 0) { echo "File not found or not shared."; exit; }
    $row = $res->fetch_assoc();
    $path = __DIR__ . '/' . $row['file_path'];
    $name = $row['file_name'];
} elseif (isset($_GET['file_id'])) {
    // download for logged in user if owner
    if (!isset($_SESSION['student_id'])) { echo "Login required."; exit; }
    $file_id = intval($_GET['file_id']);
    $stmt = $conn->prepare("SELECT file_path, file_name, student_id FROM files WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $file_id);
    $stmt->execute(); $res = $stmt->get_result();
    if ($res->num_rows === 0) { echo "File not found."; exit; }
    $row = $res->fetch_assoc();
    if ($row['student_id'] != $_SESSION['student_id']) { echo "Unauthorized."; exit; }
    $path = __DIR__ . '/' . $row['file_path'];
    $name = $row['file_name'];
} else {
    echo "No file specified."; exit;
}

if (!file_exists($path)) { echo "File missing on server."; exit; }
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($name) . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;