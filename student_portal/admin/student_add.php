<?php
require "../config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name           = $_POST['name'];
    $roll_no        = $_POST['roll_no'];
    $college        = $_POST['college'];
    $email          = $_POST['email'];
    $contact        = $_POST['contact'];
    $parent_contact = $_POST['parent_contact'];
    $password       = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if roll number already exists
    $check_stmt = $conn->prepare("SELECT * FROM students WHERE roll_no = ?");
    $check_stmt->bind_param("s", $roll_no);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        echo "<script>alert('Roll Number already exists! Please use a different roll number.'); 
              window.location.href='manage_students.php';</script>";
        exit();
    }

    $check_stmt->close();

    // Insert new student
    $stmt = $conn->prepare("
        INSERT INTO students 
        (name, roll_no, college, email, contact, parent_contact, password, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    $stmt->bind_param(
        "sssssss",
        $name,
        $roll_no,
        $college,
        $email,
        $contact,
        $parent_contact,
        $password
    );

    if ($stmt->execute()) {
        echo "<script>alert('Student Added Successfully'); window.location.href='manage_students.php';</script>";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
