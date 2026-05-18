<?php
session_start();
require "../config.php";

if (!isset($_SESSION['admin_email'])) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: manage_assignments.php");
    exit();
}

$assignment_id = intval($_GET['id']);

/* ===============================
   FETCH ASSIGNMENT + COURSE INFO
=================================*/
$aq = mysqli_query($conn, "
    SELECT a.*, c.course_name
    FROM assignments a
    LEFT JOIN courses c ON a.course_id = c.id
    WHERE a.id='$assignment_id'
");

if (!$aq || mysqli_num_rows($aq) == 0) {
    die("Assignment not found");
}
$assign = mysqli_fetch_assoc($aq);

/* ===============================
   FETCH STUDENTS & SUBMISSIONS
=================================*/
$students = mysqli_query($conn, "SELECT id, name, roll_no, email FROM students ORDER BY name");

$subs = [];
$res = mysqli_query($conn, "
    SELECT * FROM submissions
    WHERE assignment_id='$assignment_id'
");
while ($s = mysqli_fetch_assoc($res)) {
    $subs[$s['student_id']] = $s;
}

/* ===============================
   SEND CSV HEADERS
=================================*/
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="assignment_' . $assignment_id . '_submissions.csv"');
$output = fopen('php://output', 'w');

/* CSV HEADER */
fputcsv($output, [
    'Name', 
    'Roll No', 
    'Email', 
    'Status', 
    'Submitted At', 
    'File'
]);

/* CSV DATA */
while ($stu = mysqli_fetch_assoc($students)) {
    $sid = $stu['id'];
    $s = $subs[$sid] ?? null;

    fputcsv($output, [
        $stu['name'],
        $stu['roll_no'],
        $stu['email'],
        $s ? 'Submitted' : 'Pending',
        $s ? $s['submitted_at'] : '',
        $s ? $s['file_path'] : ''
    ]);
}

fclose($output);
exit;
