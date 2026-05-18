<?php
require "../config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id             = $_POST['id'];
    $name           = $_POST['name'];
    $roll_no        = $_POST['roll_no'];
    $college        = $_POST['college'];
    $email          = $_POST['email'];
    $contact        = $_POST['contact'];
    $parent_contact = $_POST['parent_contact'];

    // CHECK DUPLICATE ROLL NUMBER
    $check_query = "SELECT id FROM students WHERE roll_no = ? AND id != ?";
    $check_stmt = mysqli_prepare($conn, $check_query);

    mysqli_stmt_bind_param($check_stmt, "si", $roll_no, $id);
    mysqli_stmt_execute($check_stmt);

    $check_result = mysqli_stmt_get_result($check_stmt);

    if (mysqli_num_rows($check_result) > 0) {

        echo "<script>
                alert('Roll Number already exists!');
                window.location.href='manage_students.php';
              </script>";
        exit();
    }

    // UPDATE QUERY
    $update_query = "UPDATE students SET
                        name = ?,
                        roll_no = ?,
                        college = ?,
                        email = ?,
                        contact = ?,
                        parent_contact = ?
                     WHERE id = ?";

    $update_stmt = mysqli_prepare($conn, $update_query);

    mysqli_stmt_bind_param(
        $update_stmt,
        "ssssssi",
        $name,
        $roll_no,
        $college,
        $email,
        $contact,
        $parent_contact,
        $id
    );

    if (mysqli_stmt_execute($update_stmt)) {

        echo "<script>
                alert('Student Updated Successfully!');
                window.location.href='manage_students.php';
              </script>";

    } else {

        echo "Error: " . mysqli_error($conn);

    }

    mysqli_stmt_close($check_stmt);
    mysqli_stmt_close($update_stmt);
    mysqli_close($conn);
}
?>