<?php
session_start();
require "config.php";

$role     = $_POST['role'];
$email    = $_POST['email'];
$password = $_POST['password'];


// ----------------------------------------------------
// ADMIN LOGIN
// ----------------------------------------------------
if ($role === "admin") {

    $query = mysqli_query($conn, "SELECT * FROM admin WHERE email='$email'");

    if (mysqli_num_rows($query) === 1) {

        $admin = mysqli_fetch_assoc($query);

        // Admin password is NOT hashed
        if ($password === $admin['password']) {

            $_SESSION['admin_id']    = $admin['id'];
            $_SESSION['admin_email'] = $admin['email'];

            header("Location: admin/admin_dashboard.php");
            exit();
        }
    }

    echo "<script>alert('Invalid Admin Login'); window.location='login.php';</script>";
    exit();
}



// ----------------------------------------------------
// STUDENT LOGIN
// ----------------------------------------------------
if ($role === "student") {

    $query = mysqli_query($conn, "SELECT * FROM students WHERE email='$email'");

    if (mysqli_num_rows($query) === 1) {

        $student = mysqli_fetch_assoc($query);

        // STUDENT password IS hashed (bcrypt)
        if (password_verify($password, $student['password'])) {

            $_SESSION['student_id']    = $student['id'];
            $_SESSION['student_email'] = $student['email'];
            $_SESSION['student_name']  = $student['name'];

            header("Location: dashboard.php");
            exit();
        }
    }

    echo "<script>alert('Invalid Student Login'); window.location='login.php';</script>";
    exit();
}
?>