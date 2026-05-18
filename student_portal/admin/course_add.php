<?php
require "../config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // ==========================
    // GET FORM DATA
    // ==========================
    $course_name     = mysqli_real_escape_string($conn, $_POST['course_name']);
    $course_code     = mysqli_real_escape_string($conn, $_POST['course_code']);
    $instructor_name = mysqli_real_escape_string($conn, $_POST['instructor_name']);
    $schedule_day    = mysqli_real_escape_string($conn, $_POST['schedule_day']);
    $schedule_time   = mysqli_real_escape_string($conn, $_POST['schedule_time']);
    $total_seats     = intval($_POST['total_seats']);

    // ==========================
    // VALIDATION
    // ==========================
    if (empty($course_name) || empty($course_code) || empty($instructor_name) 
        || empty($schedule_day) || empty($schedule_time) || empty($total_seats)) {

        echo "<script>
                alert('All fields are required!');
                window.location.href='manage_courses.php';
              </script>";
        exit();
    }

    // ==========================
    // CHECK DUPLICATE COURSE CODE
    // ==========================
    $check = mysqli_query($conn, "SELECT id FROM courses WHERE course_code='$course_code'");
    if (mysqli_num_rows($check) > 0) {
        echo "<script>
                alert('Course Code already exists!');
                window.location.href='manage_courses.php';
              </script>";
        exit();
    }

    // ==========================
    // INSERT COURSE
    // ==========================
    $query = "
        INSERT INTO courses 
        (course_name, course_code, instructor_name, schedule_day, schedule_time, total_seats, created_at)
        VALUES 
        ('$course_name', '$course_code', '$instructor_name', '$schedule_day', '$schedule_time', '$total_seats', NOW())
    ";

    if (mysqli_query($conn, $query)) {

        // ================================
        // 🟢 NOTIFICATION SYSTEM
        // Notify ALL STUDENTS
        // ================================
        $students = mysqli_query($conn, "SELECT id FROM students");

        while ($s = mysqli_fetch_assoc($students)) {

            $student_id = $s['id'];

            mysqli_query($conn, "
                INSERT INTO notifications (student_id, title, message, link)
                VALUES (
                    '$student_id',
                    'New Course Added',
                    'A new course \"$course_name\" has been added. Check courses page.',
                    'courses.php'
                )
            ");
        }

        echo "<script>
                alert('Course Added Successfully');
                window.location.href='manage_courses.php';
              </script>";

    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>
