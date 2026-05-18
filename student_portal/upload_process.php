<?php
session_start(); 
require 'config.php';
if (!isset($_SESSION['student_id'])) { header('Location: login.php'); exit; }
$student_id = $_SESSION['student_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['file'])) {
    header('Location: upload.php'); exit;
}

$allowed = ['pdf','doc','docx','xls','xlsx','jpg','jpeg','png','gif'];
$file = $_FILES['file'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowed)) {
    echo "File type not allowed. <a href='upload.php'>Back</a>"; exit;
}
if ($file['error'] !== 0) {
    echo "Upload error. <a href='upload.php'>Back</a>"; exit;
}
$maxSize = 10 * 1024 * 1024; // 10MB
if ($file['size'] > $maxSize) {
    echo "File too large. <a href='upload.php'>Back</a>"; exit;
}

$unique = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
$destFolder = __DIR__ . '/uploads/';
if (!is_dir($destFolder)) mkdir($destFolder, 0755, true);
$destPath = $destFolder . $unique;
if (move_uploaded_file($file['tmp_name'], $destPath)) {
    $fpath = 'uploads/' . $unique;
    $fname = $file['name'];
    $file_type = $_POST['file_type'] ?? 'other';
    $stmt = $conn->prepare("INSERT INTO files (student_id, file_name, file_path, file_type) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $student_id, $fname, $fpath, $file_type);
    if ($stmt->execute()) {
        header('Location: dashboard.php'); exit;
    } else {
        echo "DB error: " . $conn->error;
    }
} else {
    echo "Could not move uploaded file. <a href='upload.php'>Back</a>";
}