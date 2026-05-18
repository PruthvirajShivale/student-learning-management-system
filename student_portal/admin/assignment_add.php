<?php
require "../config.php";

$upload_dir = "../uploads/assignments/";
if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $due_date = $_POST['due_date'];
    $status = $_POST['status'];

    // COURSE ID
    $course_id = !empty($_POST['course_id']) ? intval($_POST['course_id']) : null;

    // ASSIGN MODE
    $assign_mode = $_POST['assign_mode'] ?? 'all';
    if ($assign_mode === 'specific' && !empty($_POST['specific_student'])) {
        $assign_to = "student:" . intval($_POST['specific_student']);
    } else {
        $assign_to = "all";
    }

    // FILE UPLOAD
    $file_name = '';
    if (!empty($_FILES['file']['name'])) {
        $file_name = time() . '_' . basename($_FILES['file']['name']);
        if (!move_uploaded_file($_FILES['file']['tmp_name'], $upload_dir . $file_name)) {
            echo "<script>alert('Upload failed'); window.location='manage_assignments.php';</script>";
            exit;
        }
    }

    // INSERT ASSIGNMENT
    $insert_sql = "
        INSERT INTO assignments 
        (title, description, file_path, due_date, status, assign_to, course_id, created_at)
        VALUES 
        ('$title', '$description', '$file_name', '$due_date', '$status', '$assign_to', '$course_id', NOW())
    ";

    if (mysqli_query($conn, $insert_sql)) {

        $assignment_id = mysqli_insert_id($conn);

        // ===============================
        // 🟢 NOTIFICATION SYSTEM (FIXED)
        // ===============================

        if ($assign_to === "all") {

            // ✅ ONLY VALID STUDENTS (JOIN WITH students)
            $students = mysqli_query($conn, "
                SELECT sc.student_id 
                FROM student_courses sc
                INNER JOIN students s ON sc.student_id = s.id
                WHERE sc.course_id = '$course_id'
            ");

            while ($s = mysqli_fetch_assoc($students)) {

                $sid = intval($s['student_id']);

                mysqli_query($conn, "
                    INSERT INTO notifications 
                    (student_id, title, message, link, course_id, assignment_id, created_at)
                    VALUES (
                        '$sid',
                        'New Assignment Added',
                        '$title assignment is now available.',
                        'assignments.php',
                        '$course_id',
                        '$assignment_id',
                        NOW()
                    )
                ");
            }

        } 
        else if (strpos($assign_to, "student:") === 0) {

            $student_id = intval(substr($assign_to, 8));

            // ✅ CHECK STUDENT EXISTS + REGISTERED
            $check = mysqli_query($conn, "
                SELECT s.id 
                FROM students s
                INNER JOIN student_courses sc ON s.id = sc.student_id
                WHERE s.id = '$student_id' 
                AND sc.course_id = '$course_id'
            ");

            if (mysqli_num_rows($check) > 0) {

                mysqli_query($conn, "
                    INSERT INTO notifications 
                    (student_id, title, message, link, course_id, assignment_id, created_at)
                    VALUES (
                        '$student_id',
                        'New Assignment Added',
                        '$title assignment is now available.',
                        'assignments.php',
                        '$course_id',
                        '$assignment_id',
                        NOW()
                    )
                ");
            }
        }

        echo "<script>alert('Assignment added successfully!'); window.location='manage_assignments.php';</script>";

    } else {
        echo "Database Error: " . mysqli_error($conn);
    }
}
?>