<?php
session_start();
require "config.php";
if (!isset($_SESSION['student_id'])) { header("Location: login.php"); exit(); }
$student_id = $_SESSION['student_id'];
$upload_dir = "uploads/submissions/";
if (!is_dir($upload_dir)) mkdir($upload_dir,0777,true);

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $assignment_id = intval($_POST['assignment_id']);
    if (empty($_FILES['file']['name'])) { echo "<script>alert('Choose file'); window.location='assignments.php'</script>"; exit; }

    // check assignment exists and visible to student
    $aq = mysqli_query($conn, "SELECT assign_to FROM assignments WHERE id='$assignment_id' LIMIT 1");
    if (!$aq || mysqli_num_rows($aq)==0) { echo "<script>alert('Assignment not found'); window.location='assignments.php'</script>"; exit; }
    $assign = mysqli_fetch_assoc($aq);
    if (!($assign['assign_to']==='all' || $assign['assign_to']==='student:'.$student_id)) {
        echo "<script>alert('You are not allowed to submit for this assignment'); window.location='assignments.php'</script>"; exit;
    }

    // handle file
    $name = time().'_'.basename($_FILES['file']['name']);
    if (!move_uploaded_file($_FILES['file']['tmp_name'], $upload_dir.$name)) {
        echo "<script>alert('Upload failed'); window.location='assignments.php'</script>"; exit;
    }

    // if existing submission -> replace
    $check = mysqli_query($conn, "SELECT id,file_path FROM submissions WHERE assignment_id='$assignment_id' AND student_id='$student_id' LIMIT 1");
    if ($check && mysqli_num_rows($check)) {
        $old = mysqli_fetch_assoc($check);
        if (!empty($old['file_path']) && file_exists($upload_dir.$old['file_path'])) unlink($upload_dir.$old['file_path']);
        mysqli_query($conn, "UPDATE submissions SET file_path='".mysqli_real_escape_string($conn,$name)."', submitted_at=NOW() WHERE id=".$old['id']);
    } else {
        mysqli_query($conn, "INSERT INTO submissions (assignment_id,student_id,file_path,submitted_at) VALUES ('$assignment_id','$student_id','".mysqli_real_escape_string($conn,$name)."',NOW())");
    }

    echo "<script>alert('Assignment submitted successfully'); window.location='assignments.php'</script>";
    exit;
}
?>
