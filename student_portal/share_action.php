<?php
session_start(); 
require 'config.php';
if (!isset($_SESSION['student_id'])) { header('Location: login.php'); exit; }
$student_id = $_SESSION['student_id'];

if (!isset($_GET['file_id'])) { header('Location: dashboard.php'); exit; }
$file_id = intval($_GET['file_id']);
$stmt = $conn->prepare("SELECT student_id, is_shared FROM files WHERE id = ? LIMIT 1");
$stmt->bind_param("i",$file_id);
$stmt->execute(); $res = $stmt->get_result();
if ($res->num_rows === 0) { echo "File not found."; exit; }
$row = $res->fetch_assoc();
if ($row['student_id'] != $student_id) { echo "Unauthorized."; exit; }

if ($row['is_shared']) {
    // unshare
    $stmt = $conn->prepare("UPDATE files SET is_shared = 0, share_token = NULL WHERE id = ?");
    $stmt->bind_param("i", $file_id);
    $stmt->execute();
    header('Location: dashboard.php'); exit;
} else {
    $token = bin2hex(random_bytes(16));
    $stmt = $conn->prepare("UPDATE files SET is_shared = 1, share_token = ? WHERE id = ?");
    $stmt->bind_param("si", $token, $file_id);
    $stmt->execute();
    // show share link
    $link = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']
            . dirname($_SERVER['REQUEST_URI']) . '/download.php?token=' . $token;
    echo "Shared! Public link: <a href='$link' target='_blank'>$link</a><br><a href='dashboard.php'>Back</a>";
    exit;
}