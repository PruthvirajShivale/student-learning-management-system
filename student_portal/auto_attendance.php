<?php
require 'config.php';

// Minimum minutes to be considered present
$min_duration = 30;

// Get all lectures today or all pending lectures
$lectures = $conn->query("SELECT id, course_id FROM course_lectures");

// Loop through lectures
while ($lec = $lectures->fetch_assoc()) {
    $lecture_id = $lec['id'];
    $course_id  = $lec['course_id'];

    // Get students registered in this course
    $students = $conn->query("SELECT student_id FROM student_courses WHERE course_id = $course_id");

    while ($stu = $students->fetch_assoc()) {
        $student_id = $stu['student_id'];

        // Check total duration for this lecture
        $stmt = $conn->prepare("
            SELECT SUM(duration_minutes) 
            FROM student_activity 
            WHERE student_id=? AND lecture_id=? 
        ");
        $stmt->bind_param("ii", $student_id, $lecture_id);
        $stmt->execute();
        $stmt->bind_result($total_minutes);
        $stmt->fetch();
        $stmt->close();

        $status = ($total_minutes >= $min_duration) ? 'present' : 'absent';

        // Insert or update attendance
        $stmt = $conn->prepare("
            INSERT INTO attendance (student_id, course_id, lecture_id, status)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE status=?
        ");
        $stmt->bind_param("iiiss", $student_id, $course_id, $lecture_id, $status, $status);
        $stmt->execute();
        $stmt->close();
    }
}
?>
