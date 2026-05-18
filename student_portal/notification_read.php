<?php
session_start();
require "config.php";

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit;
}

$student_id = $_SESSION['student_id'];
$id = intval($_GET['id']);

mysqli_query($conn, "UPDATE notifications SET is_read=1 WHERE id='$id' AND student_id='$student_id'");

header("Location: notifications.php");
exit;
?>
