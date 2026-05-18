<?php
session_start();   // 🔥 REQUIRED — this fixes the issue
require 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit;
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { 
    header('Location: dashboard.php'); 
    exit; 
}

$id = $_SESSION['student_id'];
$name = trim($_POST['name']);
$college = trim($_POST['college']);
$email = trim($_POST['email']);
$contact = trim($_POST['contact']);
$parent_contact = trim($_POST['parent_contact']);
$password = $_POST['password'];

// If password entered → hash it
if (!empty($password)) {

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("
        UPDATE students 
        SET name=?, college=?, email=?, contact=?, parent_contact=?, password=? 
        WHERE id=?
    ");
    $stmt->bind_param("ssssssi", $name, $college, $email, $contact, $parent_contact, $hash, $id);

} else {

    $stmt = $conn->prepare("
        UPDATE students 
        SET name=?, college=?, email=?, contact=?, parent_contact=? 
        WHERE id=?
    ");
    $stmt->bind_param("sssssi", $name, $college, $email, $contact, $parent_contact, $id);
}

// Execute update
if ($stmt->execute()) {
    echo "<script>alert('Profile updated successfully'); window.location='dashboard.php';</script>";
    exit;
} else {
    echo "Update failed: " . $conn->error;
}
