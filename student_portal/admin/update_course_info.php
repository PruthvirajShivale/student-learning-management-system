<?php
session_start();
require "../config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $course_id = mysqli_real_escape_string($conn, $_POST['course_id']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);

    $query = "UPDATE courses SET description = '$description' WHERE id = '$course_id'";
    
    if (mysqli_query($conn, $query)) {
        header("Location: manage_courses.php?msg=InfoUpdated");
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>