<?php
require "../config.php";

if (isset($_GET['id'])) {

    $id = $_GET['id'];

    $query = "DELETE FROM students WHERE id=$id";

    if (mysqli_query($conn, $query)) {
        echo "<script>alert('Student Deleted Successfully'); window.location.href='manage_students.php';</script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>
