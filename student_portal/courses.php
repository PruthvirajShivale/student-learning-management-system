<?php
session_start(); 
require 'config.php';

if (!isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit;
}

$student_id = $_SESSION['student_id'];
$message = "";

// Register course logic remains untouched
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = intval($_POST['course_id']);
    $check = $conn->prepare("SELECT id FROM student_courses WHERE student_id = ? AND course_id = ?");
    $check->bind_param("ii", $student_id, $course_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $message = "⚠️ You have already registered for this course!";
    } else {
        $courseCheck = $conn->prepare("SELECT id FROM courses WHERE id = ?");
        $courseCheck->bind_param("i", $course_id);
        $courseCheck->execute();
        $courseCheck->store_result();

        if ($courseCheck->num_rows == 0) {
            $message = "❌ Invalid Course ID!";
        } else {
            $stmt = $conn->prepare("INSERT INTO student_courses (student_id, course_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $student_id, $course_id);
            if ($stmt->execute()) {
                $message = "✅ Course registered successfully!";
            } else {
                $message = "❌ Error registering course!";
            }
            $stmt->close();
        }
        $courseCheck->close();
    }
    $check->close();
}

$courses = [];
$res = $conn->query("SELECT * FROM courses ORDER BY id ASC");
if ($res) {
    while ($r = $res->fetch_assoc()) $courses[] = $r;
}

$regStmt = $conn->prepare("SELECT c.id, c.course_code, c.course_name, c.instructor_name, 
                                  c.schedule_day, c.schedule_time, sc.registered_at
                           FROM student_courses sc
                           JOIN courses c ON sc.course_id = c.id
                           WHERE sc.student_id = ?
                           ORDER BY sc.registered_at DESC");
$regStmt->bind_param("i", $student_id);
$regStmt->execute();
$regRes = $regStmt->get_result();
$registered_courses = $regRes->fetch_all(MYSQLI_ASSOC);
$regStmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Catalog | Enrollment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --bg-soft: #f8fafc;
            --card-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05);
        }

        body {
            background-color: var(--bg-soft);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #1e293b;
        }

        /* Hero Section */
        .enroll-hero {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            padding: 80px 0 120px;
            color: white;
            border-radius: 0 0 50px 50px;
        }

        /* Floating Search/Enroll Box */
        .quick-enroll-container {
            margin-top: -60px;
            z-index: 10;
            position: relative;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
            padding: 2rem;
        }

        /* Course Cards */
        .course-card {
            border: none;
            border-radius: 20px;
            transition: all 0.3s ease;
            height: 100%;
            background: white;
            box-shadow: var(--card-shadow);
        }

        .course-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .course-badge {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 5px 12px;
            border-radius: 50px;
            background: #eef2ff;
            color: var(--primary);
            font-weight: 700;
        }

        .instructor-avatar {
            width: 32px;
            height: 32px;
            background: #f1f5f9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            color: #64748b;
        }

        /* Table Overrides */
        .reg-table-card {
            border: none;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
        }

        .btn-enroll {
            background: var(--primary);
            color: white;
            border-radius: 12px;
            font-weight: 600;
            padding: 12px 24px;
            border: none;
        }

        .btn-enroll:hover {
            background: var(--primary-hover);
            color: white;
        }

        .section-title {
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg bg-white border-bottom py-3">
    <div class="container">
        <a class="navbar-brand fw-800 d-flex align-items-center" href="#">
            <div class="bg-primary text-white rounded-3 p-1 me-2" style="line-height: 1;">
                <i class="bi bi-book-half fs-4"></i>
            </div>
            <span class="text-dark">Enrollment Center</span>
        </a>
        <a href="dashboard.php" class="btn btn-light rounded-pill px-4 fw-600">
            <i class="bi bi-house-door me-2"></i>Dashboard
        </a>
    </div>
</nav>

<div class="enroll-hero text-center">
    <div class="container">
        <h1 class="display-5 fw-800 mb-3">Find Your Next Course</h1>
        <p class="lead opacity-75">Browse the catalog and secure your seat in seconds.</p>
    </div>
</div>

<div class="container">
    <div class="row justify-content-center quick-enroll-container">
        <div class="col-lg-7">
            <div class="glass-card">
                <?php if ($message): ?>
                    <div class="alert alert-dismissible fade show mb-4 <?php 
                        if (strpos($message, '✅') !== false) echo 'alert-success';
                        elseif (strpos($message, '⚠️') !== false) echo 'alert-warning';
                        else echo 'alert-danger';
                    ?>" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" class="row g-2">
                    <div class="col-md-8">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 rounded-start-4"><i class="bi bi-search text-muted"></i></span>
                            <input type="number" name="course_id" class="form-control border-start-0 py-3 rounded-end-4" placeholder="Fast Enroll with Course ID..." required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-enroll w-100 h-100 shadow-sm" type="submit">
                            Register Course
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="mt-5 pt-4">
        <h3 class="section-title"><i class="bi bi-compass text-primary"></i> Available Courses</h3>
        <div class="row g-4">
            <?php foreach ($courses as $c): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card course-card">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <span class="course-badge">ID: <?php echo $c['id']; ?></span>
                            <span class="text-muted small fw-bold"><?php echo htmlspecialchars($c['course_code']); ?></span>
                        </div>
                        <h5 class="fw-700 mb-2"><?php echo htmlspecialchars($c['course_name']); ?></h5>
                        
                        <div class="d-flex align-items-center mb-4">
                            <div class="instructor-avatar me-2">
                                <i class="bi bi-person-fill"></i>
                            </div>
                            <span class="small text-muted"><?php echo htmlspecialchars($c['instructor_name']); ?></span>
                        </div>

                        <div class="bg-light rounded-4 p-3 mb-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="small text-muted"><i class="bi bi-calendar-event me-1"></i> Day</span>
                                <span class="small fw-600 text-dark"><?php echo htmlspecialchars($c['schedule_day']); ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="small text-muted"><i class="bi bi-clock me-1"></i> Time</span>
                                <span class="small fw-600 text-dark"><?php echo htmlspecialchars($c['schedule_time']); ?></span>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <div class="small">
                                <span class="fw-700 text-primary"><?php echo htmlspecialchars($c['total_seats']); ?></span> 
                                <span class="text-muted">Seats Available</span>
                            </div>
                            <i class="bi bi-arrow-right-circle-fill text-primary fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="mt-5 mb-5">
        <h3 class="section-title"><i class="bi bi-mortarboard text-success"></i> My Enrolled Schedule</h3>
        <div class="card reg-table-card">
            <div class="card-body p-0">
                <?php if (empty($registered_courses)): ?>
                    <div class="text-center py-5">
                        <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png" width="80" class="opacity-25 mb-3" alt="Empty">
                        <p class="text-muted">You are not enrolled in any courses yet.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4 py-3 border-0">Course</th>
                                    <th class="py-3 border-0">Instructor</th>
                                    <th class="py-3 border-0">Schedule</th>
                                    <th class="py-3 border-0">Status</th>
                                    <th class="py-3 border-0 pe-4 text-end">Access</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($registered_courses as $rc): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-700"><?php echo htmlspecialchars($rc['course_name']); ?></div>
                                        <div class="small text-muted"><?php echo htmlspecialchars($rc['course_code']); ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars($rc['instructor_name']); ?></td>
                                    <td>
                                        <div class="small fw-600"><?php echo htmlspecialchars($rc['schedule_day']); ?></div>
                                        <div class="small text-muted"><?php echo htmlspecialchars($rc['schedule_time']); ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill">
                                            <i class="bi bi-check2-circle me-1"></i> Enrolled
                                        </span>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <a href="course.php?course_id=<?php echo $rc['id']; ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                                            Enter Class <i class="bi bi-chevron-right ms-1"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>