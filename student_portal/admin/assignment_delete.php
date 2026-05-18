<?php
require "../config.php";

$upload_dir = "../uploads/assignments/";

if (isset($_GET['id'])) {

    $id = $_GET['id'];

    // Get assignment file from DB
    $get_file = mysqli_query($conn, "SELECT file_path FROM assignments WHERE id='$id'");
    $data = mysqli_fetch_assoc($get_file);
    $file = $data['file_path'];

    // Delete file from folder
    if (!empty($file) && file_exists($upload_dir . $file)) {
        unlink($upload_dir . $file);
    }

    // Delete assignment record
    $delete_query = "DELETE FROM assignments WHERE id='$id'";

    if (mysqli_query($conn, $delete_query)) {
        echo "<script>
                alert('Assignment Deleted Successfully!');
                window.location.href='manage_assignments.php';
              </script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>
