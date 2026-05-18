<?php
require "../config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id          = $_POST['id'];
    $course_name = $_POST['course_name'];
    $course_code = $_POST['course_code'];

    // check duplicate course code
    $check = mysqli_query(
        $conn,
        "SELECT * FROM courses WHERE course_code='$course_code' AND id != '$id'"
    );

    if (mysqli_num_rows($check) > 0) {
        echo "<script>
                alert('Course Code already exists!');
                window.location.href='manage_courses.php';
              </script>";
        exit();
    }

    // update query
    $query = "UPDATE courses SET
                course_name='$course_name',
                course_code='$course_code'
              WHERE id='$id'";

    if (mysqli_query($conn, $query)) {
        echo "<script>
                alert('Course Updated Successfully');
                window.location.href='manage_courses.php';
              </script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>
