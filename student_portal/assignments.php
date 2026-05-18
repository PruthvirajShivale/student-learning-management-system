<?php
session_start();
require "config.php";

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = intval($_SESSION['student_id']);

$course_id     = isset($_GET['course_id']) ? intval($_GET['course_id']) : null;
$assignment_id = isset($_GET['assignment_id']) ? intval($_GET['assignment_id']) : null;

/* ===============================
   FETCH COURSE INFO (ONLY IF COURSE PAGE)
================================ */
$course = null;

if ($course_id) {
    $course_q = $conn->prepare("
        SELECT c.* FROM courses c
        JOIN student_courses sc ON sc.course_id = c.id
        WHERE c.id=? AND sc.student_id=?
    ");
    $course_q->bind_param("ii", $course_id, $student_id);
    $course_q->execute();
    $course = $course_q->get_result()->fetch_assoc();
    $course_q->close();

    if (!$course) {
        die("You are not enrolled in this course.");
    }
}

/* ===============================
   BUILD QUERY
================================ */
$where = [];
$params = [];
$types  = "";

$where[] = "sc.student_id = ?";
$params[] = $student_id;
$types .= "i";

$where[] = "a.status='active'";
$where[] = "(a.assign_to='all' OR a.assign_to=CONCAT('student:', ?))";
$params[] = $student_id;
$types .= "i";

if ($course_id) {
    $where[] = "a.course_id=?";
    $params[] = $course_id;
    $types .= "i";
}

if ($assignment_id) {
    $where[] = "a.id=?";
    $params[] = $assignment_id;
    $types .= "i";
}

$sql = "
SELECT 
    a.*,
    c.course_name,
    c.course_code,
    c.instructor_name
FROM assignments a
JOIN courses c ON a.course_id = c.id
JOIN student_courses sc ON sc.course_id = a.course_id
WHERE " . implode(" AND ", $where) . "
ORDER BY c.course_name ASC, a.due_date ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$assignments = $stmt->get_result();

/* ===============================
   FETCH SUBMISSIONS
================================ */
$subs = [];
$res = mysqli_query($conn,
    "SELECT * FROM submissions WHERE student_id='$student_id'"
);
while ($r = mysqli_fetch_assoc($res)) {
    $subs[$r['assignment_id']] = $r;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignments | Student Portal</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --brand-primary: #6366f1; /* Indigo */
            --brand-primary-dark: #4f46e5;
            --brand-bg: #f8fafc;
            --surface: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-subtle: #e2e8f0;
            --radius-xl: 24px;
            --radius-lg: 16px;
        }

        body { 
            background-color: var(--brand-bg); 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            color: var(--text-main);
            padding-bottom: 100px;
            -webkit-font-smoothing: antialiased;
        }

        /* --- Glassmorphism Navbar --- */
        .navbar-glass {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(226, 232, 240, 0.6);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        /* --- Header Card --- */
        .section-header {
            background: var(--surface);
            padding: 3rem 2rem;
            border-radius: var(--radius-xl);
            margin-bottom: 2.5rem;
            position: relative;
            overflow: hidden;
            border: 1px solid var(--border-subtle);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.02);
        }
        
        /* Subtle decorative gradient blob */
        .section-header::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(99,102,241,0.1) 0%, rgba(255,255,255,0) 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        /* --- Assignment Card --- */
        .assignment-card {
            background: var(--surface);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-lg);
            padding: 0;
            margin-bottom: 2rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        .assignment-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.01);
            border-color: #cbd5e1;
        }

        .card-body-content {
            padding: 1.75rem;
        }

        /* Course Grouping Label */
        .course-header-group {
            margin-top: 3rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
        }
        .course-header-group:first-child { margin-top: 0; }
        
        .course-pill {
            background: var(--surface);
            border: 1px solid var(--border-subtle);
            padding: 8px 16px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--text-main);
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .course-code-tag {
            background: #e0e7ff;
            color: var(--brand-primary);
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 0.75rem;
            text-transform: uppercase;
        }

        /* Badges */
        .status-badge {
            padding: 6px 14px;
            border-radius: 100px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .badge-submitted { background: #dcfce7; color: #166534; } /* Green */
        .badge-late { background: #fee2e2; color: #991b1b; }      /* Red */
        .badge-pending { background: #f1f5f9; color: #475569; }   /* Gray */

        /* File Links */
        .resource-link {
            background: #f8fafc;
            border: 1px solid var(--border-subtle);
            padding: 10px 16px;
            border-radius: 10px;
            text-decoration: none;
            color: var(--brand-primary);
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: 0.2s;
        }
        .resource-link:hover {
            background: #eef2ff;
            border-color: #c7d2fe;
        }

        /* Upload Area */
        .upload-zone-modern {
            background: #f8fafc;
            border: 2px dashed #cbd5e1;
            padding: 1.5rem;
            border-radius: 12px;
            transition: 0.2s;
            position: relative;
        }

        .upload-zone-modern:hover, .upload-zone-modern:focus-within {
            background: #fff;
            border-color: var(--brand-primary);
        }

        .upload-input-styled {
            width: 100%;
            font-size: 0.875rem;
            color: var(--text-muted);
        }
        .upload-input-styled::file-selector-button {
            margin-right: 1rem;
            background: #fff;
            border: 1px solid #e2e8f0;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            color: var(--text-main);
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }
        .upload-input-styled:hover::file-selector-button {
            background: #f1f5f9;
        }

        /* Buttons */
        .btn-submit-premium {
            background: var(--brand-primary);
            color: white;
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.4);
            transition: all 0.2s;
        }
        .btn-submit-premium:hover {
            background: var(--brand-primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 6px 12px -2px rgba(99, 102, 241, 0.5);
        }

        .submitted-panel {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        @media (max-width: 768px) {
            .submitted-panel { flex-direction: column; align-items: flex-start; gap: 1rem; }
            .submitted-panel a { width: 100%; text-align: center; }
        }
    </style>
</head>

<body>

<nav class="navbar-glass mb-4">
    <div class="container d-flex justify-content-between align-items-center">
        <a href="dashboard.php" class="text-decoration-none d-flex align-items-center gap-2">
            <div class="bg-primary text-white rounded-3 d-flex align-items-center justify-content-center" style="width:32px; height:32px;">
                <i class="bi bi-mortarboard-fill small"></i>
            </div>
            <span class="fw-bold text-dark tracking-tight">Student Portal</span>
        </a>
        
        <?php if ($course_id): ?>
            <a href="go_to_course.php?course_id=<?= $course_id ?>" class="btn btn-light border btn-sm rounded-pill px-4 fw-semibold text-muted">
                <i class="bi bi-arrow-left me-1"></i> Back to Course
            </a>
        <?php else: ?>
             <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-2">Assignment Center</span>
        <?php endif; ?>
    </div>
</nav>

<div class="container" style="max-width: 960px;">

    <div class="section-header">
        <div class="row align-items-center position-relative" style="z-index: 2;">
            <div class="col-md-8">
                <?php if ($course_id): ?>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge bg-primary text-white rounded-2"><?= htmlspecialchars($course['course_code']) ?></span>
                        <span class="text-muted small fw-bold text-uppercase">Course Content</span>
                    </div>
                    <h1 class="display-6 fw-bold mb-1"><?= htmlspecialchars($course['course_name']) ?></h1>
                    <p class="text-muted mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-person-circle"></i> <?= htmlspecialchars($course['instructor_name']) ?>
                    </p>
                <?php else: ?>
                    <h1 class="display-6 fw-bold mb-2">My Assignments</h1>
                    <p class="text-muted mb-0 lead fs-6">
                        Stay organized. Track your upcoming deadlines and submissions across all courses.
                    </p>
                <?php endif; ?>
            </div>
            <div class="col-md-4 text-md-end mt-4 mt-md-0">
                <div class="d-inline-block text-start">
                    <div class="text-muted small fw-bold text-uppercase mb-1">Total Tasks</div>
                    <div class="h2 fw-bold mb-0 text-dark"><?= $assignments->num_rows ?></div>
                </div>
            </div>
        </div>
    </div>

    <?php
    $last_course = null;
    
    if ($assignments->num_rows == 0):
    ?>
        <div class="text-center py-5">
            <div class="mb-4">
                <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem; opacity: 0.2;"></i>
            </div>
            <h3 class="fw-bold text-dark">You're all caught up!</h3>
            <p class="text-muted">No pending assignments found at this moment.</p>
        </div>
    <?php else: ?>

    <div class="row">
        <div class="col-12">
            <?php while ($a = $assignments->fetch_assoc()): ?>

            <?php
            $is_submitted = isset($subs[$a['id']]);
            $is_late = (!$is_submitted && strtotime($a['due_date']) < time());
            
            // LOGIC FOR GROUPING BY COURSE
            // We only show the course header if no specific course_id is set in URL
            // AND the course name has changed from the previous row (since SQL is ordered by course_name)
            if (!$course_id && $last_course !== $a['course_name']):
            ?>
                <div class="course-header-group">
                    <div class="course-pill">
                        <span class="course-code-tag"><?= htmlspecialchars($a['course_code']) ?></span>
                        <?= htmlspecialchars($a['course_name']) ?>
                    </div>
                    <div class="ms-auto text-muted small fw-semibold">
                        <i class="bi bi-person me-1"></i> <?= htmlspecialchars($a['instructor_name']) ?>
                    </div>
                </div>
                <hr class="border-secondary-subtle opacity-25 mb-4 mt-0">
            <?php
            $last_course = $a['course_name'];
            endif;
            ?>

            <div class="assignment-card">
                <div class="card-body-content">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                        <div>
                            <h4 class="h5 fw-bold mb-1 text-dark"><?= htmlspecialchars($a['title']) ?></h4>
                            <div class="d-flex align-items-center gap-3 text-muted small mt-2">
                                <span class="<?= $is_late ? 'text-danger fw-semibold' : '' ?>">
                                    <i class="bi bi-calendar-event me-1"></i> 
                                    Due <?= date("M d, Y", strtotime($a['due_date'])) ?>
                                    <span class="opacity-50 mx-1">|</span>
                                    <?= date("h:i A", strtotime($a['due_date'])) ?>
                                </span>
                            </div>
                        </div>
                        
                        <div>
                            <?php if ($is_submitted): ?>
                                <span class="status-badge badge-submitted">
                                    <i class="bi bi-check-lg"></i> Submitted
                                </span>
                            <?php elseif ($is_late): ?>
                                <span class="status-badge badge-late">
                                    <i class="bi bi-exclamation-octagon"></i> Overdue
                                </span>
                            <?php else: ?>
                                <span class="status-badge badge-pending">
                                    <i class="bi bi-hourglass-split"></i> Pending
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="text-secondary mb-4" style="line-height: 1.7; font-size: 0.95rem;">
                        <?= nl2br(htmlspecialchars($a['description'])) ?>
                    </div>

                    <?php if (!empty($a['file_path'])): ?>
                        <div class="mb-4">
                            <a class="resource-link" href="uploads/assignments/<?= htmlspecialchars($a['file_path']) ?>" target="_blank">
                                <div class="bg-white border rounded p-1 d-flex align-items-center justify-content-center text-primary" style="width: 32px; height: 32px;">
                                    <i class="bi bi-paperclip"></i>
                                </div>
                                <span>Download Reference Material</span>
                            </a>
                        </div>
                    <?php endif; ?>

                    <div class="pt-3 border-top border-light-subtle">
                        <?php if ($is_submitted): ?>
                            <div class="submitted-panel">
                                <div>
                                    <div class="text-success fw-bold small text-uppercase mb-1">
                                        <i class="bi bi-patch-check-fill me-1"></i> Submission Received
                                    </div>
                                    <div class="text-dark small">
                                        <?= date("F j, Y \a\\t g:i A", strtotime($subs[$a['id']]['submitted_at'])) ?>
                                    </div>
                                </div>
                                <a class="btn btn-white bg-white border shadow-sm rounded-pill btn-sm px-4 fw-bold text-dark" 
                                   href="uploads/submissions/<?= htmlspecialchars($subs[$a['id']]['file_path']) ?>" target="_blank">
                                   View Your Work
                                </a>
                            </div>
                        <?php else: ?>
                            <form action="submit_assignment.php" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="assignment_id" value="<?= $a['id'] ?>">
                                <div class="upload-zone-modern">
                                    <div class="row align-items-center g-3">
                                        <div class="col-md-8">
                                            <label class="form-label small fw-bold text-muted text-uppercase mb-2">Upload Solution</label>
                                            <input type="file" name="file" class="form-control upload-input-styled" required>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <button class="btn btn-submit-premium w-100">
                                                Submit Assignment
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>