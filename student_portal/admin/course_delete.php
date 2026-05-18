<?php
require "../config.php";

if (isset($_GET['id'])) {

    $id = $_GET['id'];

    $query = "DELETE FROM courses WHERE id='$id'";

    if (mysqli_query($conn, $query)) {
        echo "<script>
                alert('Course Deleted Successfully');
                window.location.href='manage_courses.php';
              </script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>
