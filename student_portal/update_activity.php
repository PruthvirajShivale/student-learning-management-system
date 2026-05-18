<?php
session_start();
require 'config.php';

$student_id = $_SESSION['student_id'];
$data = json_decode(file_get_contents('php://input'), true);

$lecture_id = intval($data['lecture_id']);
$active_minutes = intval($data['active_minutes']);

// Fetch lecture duration
$stmt = $conn->prepare("SELECT duration_minutes, course_id FROM course_lectures WHERE id=?");
$stmt->bind_param("i", $lecture_id);
$stmt->execute();
$stmt->bind_result($duration, $course_id);
$stmt->fetch();
$stmt->close();

$min_active = 10; // minimum minutes
$attendance_percent = 0;

if($active_minutes >= $min_active){
    $attendance_percent = min(100, ($active_minutes / $duration) * 100);
} else {
    // Negative marking
    $attendance_percent = max(0, ($active_minutes / $duration) * 100 - 5);
}

// Insert or update activity
$stmt = $conn->prepare("
    INSERT INTO student_activity (student_id, course_id, lecture_id, join_time, attendance_percent)
    VALUES (?, ?, ?, NOW(), ?)
    ON DUPLICATE KEY UPDATE attendance_percent=?
");
$stmt->bind_param("iiiii", $student_id, $course_id, $lecture_id, $attendance_percent, $attendance_percent);
$stmt->execute();
$stmt->close();

// Update overall course attendance
$stmt = $conn->prepare("
    UPDATE student_courses sc
    JOIN (
        SELECT student_id, course_id, AVG(attendance_percent) AS avg_attendance
        FROM student_activity
        WHERE student_id = ?
        GROUP BY course_id
    ) t ON sc.student_id=t.student_id AND sc.course_id=t.course_id
    SET sc.attendance_percent = t.avg_attendance
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$stmt->close();
?>
