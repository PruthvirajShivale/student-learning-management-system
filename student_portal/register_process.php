`<?php
require 'config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.php'); exit;
}

$name = trim($_POST['name']);
$roll_no = trim($_POST['roll_no']);
$college = trim($_POST['college']);
$email = trim($_POST['email']);
$contact = trim($_POST['contact']);
$parent_contact = trim($_POST['parent_contact']);
$password = $_POST['password'];
$confirm = $_POST['confirm_password'];

// basic validation
if ($password !== $confirm) {
    echo "Passwords do not match. <a href='register.php'>Go back</a>";
    exit;
}
if (empty($name) || empty($roll_no) || empty($college) || empty($email) || empty($contact)) {
    echo "Please fill all required fields. <a href='register.php'>Go back</a>";
    exit;
}

// check duplicates
$stmt = $conn->prepare("SELECT id FROM students WHERE roll_no=? OR email=? LIMIT 1");
$stmt->bind_param("ss", $roll_no, $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo "Roll No or Email already registered. <a href='register.php'>Go back</a>";
    exit;
}
$stmt->close();

// insert
$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO students (name, roll_no, college, email, contact, parent_contact, password) VALUES (?,?,?,?,?,?,?)");
$stmt->bind_param("sssssss", $name, $roll_no, $college, $email, $contact, $parent_contact, $hash);
if ($stmt->execute()) {
    // after successful registration redirect to login
    header('Location: login.php');
    exit;
} else {
    echo "Registration failed: " . $conn->error;
}