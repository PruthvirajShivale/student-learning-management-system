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
    SELECT a.*, c.course_name, c.id AS course_id
    FROM assignments a
    LEFT JOIN courses c ON a.course_id = c.id
    WHERE a.id='$assignment_id'
");

if (!$aq || mysqli_num_rows($aq) == 0) {
    die("Assignment not found");
}
$assign = mysqli_fetch_assoc($aq);


/* ===============================
    SEND MESSAGE + NOTIFICATION
=================================*/
if ($_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['send_message'])) {

    $student_id = intval($_POST['student_id']);
    $message = trim($_POST['message']);

    if ($message !== '') {

        /* 1️⃣ Store REAL message */
        $stmt = $conn->prepare("
            INSERT INTO assignment_messages
            (assignment_id, student_id, message)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param("iis", $assignment_id, $student_id, $message);
        $stmt->execute();
        $stmt->close();

        /* 2️⃣ Store notification trigger */
        $course_id = intval($assign['course_id']);
        $notif_message = "You received a new message regarding assignment: " . $assign['title'];

        $stmt = $conn->prepare("
            INSERT INTO notifications
            (student_id, title, message, link, course_id, assignment_id, created_at)
            VALUES (?, 'New Message From Teacher', ?, 'assignments.php', ?, ?, NOW())
        ");
        $stmt->bind_param(
            "isii",
            $student_id,
            $notif_message,
            $course_id,
            $assignment_id
        );
        $stmt->execute();
        $stmt->close();

        echo "<script>alert('Message sent successfully & student notified');</script>";
    }
}


/* ===============================
    FETCH STUDENTS (ONLY REGISTERED)
=================================*/
$course_id = intval($assign['course_id']);

$students = mysqli_query($conn, "
    SELECT s.id, s.name, s.roll_no, s.email
    FROM students s
    INNER JOIN student_courses sc 
        ON s.id = sc.student_id
    WHERE sc.course_id = '$course_id'
    ORDER BY s.name
");


/* ===============================
    FETCH SUBMISSIONS
=================================*/
$subs = [];
$res = mysqli_query($conn, "
    SELECT * FROM submissions
    WHERE assignment_id='$assignment_id'
");
while ($s = mysqli_fetch_assoc($res)) {
    $subs[$s['student_id']] = $s;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submissions | Modern LMS Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #4f46e5;
            --primary-light: #eef2ff;
            --success: #10b981;
            --warning: #f59e0b;
            --slate-50: #f8fafc;
            --slate-100: #f1f5f9;
            --slate-200: #e2e8f0;
            --slate-700: #334155;
            --slate-900: #0f172a;
            --shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -2px rgba(0,0,0,0.1);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--slate-50);
            color: var(--slate-900);
            padding-bottom: 50px;
        }

        /* Nav Bar */
        .top-nav {
            background: white;
            border-bottom: 1px solid var(--slate-200);
            padding: 1rem 0;
            margin-bottom: 2rem;
            position: sticky;
            top: 0;
            z-index: 1020;
        }

        /* Header Card */
        .header-section {
            background: white;
            border-radius: 24px;
            padding: 2rem;
            border: 1px solid var(--slate-200);
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
        }

        .course-tag {
            background: var(--primary-light);
            color: var(--primary);
            padding: 6px 14px;
            border-radius: 100px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Dashboard Sidebar Stats */
        .stat-box {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            border: 1px solid var(--slate-200);
            text-align: center;
            height: 100%;
            transition: transform 0.2s;
        }
        .stat-box:hover { transform: translateY(-3px); }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
        }

        /* Modern Student List */
        .submission-card {
            background: white;
            border-radius: 20px;
            padding: 1.25rem;
            border: 1px solid var(--slate-200);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            transition: all 0.2s ease;
        }
        .submission-card:hover {
            border-color: var(--primary);
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);
        }

        .student-avatar {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            background: var(--slate-100);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: var(--slate-700);
            font-size: 1.2rem;
            flex-shrink: 0;
            border: 1px solid var(--slate-200);
        }

        .student-details { flex-grow: 1; }
        .student-details h6 { margin: 0; font-weight: 700; }
        .meta-info { font-size: 0.8rem; color: #64748b; margin-top: 4px; }

        .status-pill {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        /* Action Buttons */
        .btn-action {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--slate-200);
            background: white;
            color: var(--slate-700);
            transition: all 0.2s;
        }
        .btn-action:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        /* FIX: Stable Modal Styling to prevent blinking */
        .modal {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
        }
        .modal-content {
            border-radius: 24px;
            border: none;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
        }
        .modal-header { border-bottom: 1px solid var(--slate-100); padding: 1.5rem; }
        .modal-body { padding: 1.5rem; }

        textarea.form-control {
            background: var(--slate-50);
            border: 1px solid var(--slate-200);
            border-radius: 16px;
            padding: 1rem;
            transition: all 0.2s;
        }
        textarea.form-control:focus {
            background: white;
            box-shadow: 0 0 0 4px var(--primary-light);
            border-color: var(--primary);
        }
    </style>
</head>
<body>

<nav class="top-nav">
    <div class="container d-flex justify-content-between align-items-center">
        <a href="manage_assignments.php" class="text-decoration-none text-dark fw-bold">
            <i class="bi bi-arrow-left me-2"></i>Dashboard
        </a>
        <div class="text-muted small fw-medium">Admin Panel</div>
    </div>
</nav>

<div class="container">
    <div class="header-section">
        <div class="row align-items-center">
            <div class="col-md-8">
                <span class="course-tag mb-3 d-inline-block"><?= htmlspecialchars($assign['course_name']) ?></span>
                <h1 class="fw-bold h2"><?= htmlspecialchars($assign['title']) ?></h1>
                <div class="d-flex gap-3 mt-2">
                    <span class="text-muted small"><i class="bi bi-calendar3 me-2"></i>Due: <?= date("M j, Y", strtotime($assign['due_date'])) ?></span>
                    <span class="text-muted small"><i class="bi bi-clock me-2"></i>Time: 11:59 PM</span>
                </div>
            </div>
            <div class="col-md-4 text-md-end mt-4 mt-md-0">
                <a href="download_assignment_report.php?id=<?= $assignment_id ?>" class="btn btn-dark px-4 py-2 rounded-pill fw-bold">
                    <i class="bi bi-file-earmark-spreadsheet me-2"></i>Download Analytics
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-3 mb-4">
            <div class="row g-3">
                <div class="col-12 col-md-6 col-lg-12">
                    <div class="stat-box">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div class="h3 fw-bold mb-0"><?= mysqli_num_rows($students) ?></div>
                        <div class="text-muted small fw-bold">Total Students</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-12">
                    <div class="stat-box">
                        <div class="stat-icon bg-success bg-opacity-10 text-success">
                            <i class="bi bi-file-earmark-check-fill"></i>
                        </div>
                        <div class="h3 fw-bold mb-0"><?= count($subs) ?></div>
                        <div class="text-muted small fw-bold">Submissions</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            <div class="mb-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">Student Roster</h5>
                <span class="text-muted small"><?= date("F j, Y") ?></span>
            </div>

            <?php while ($stu = mysqli_fetch_assoc($students)): 
                $sid = $stu['id'];
                $s = $subs[$sid] ?? null;
                $initials = strtoupper(substr($stu['name'], 0, 1));
            ?>
            <div class="submission-card">
                <div class="student-avatar">
                    <?= $initials ?>
                </div>

                <div class="student-details">
                    <h6><?= htmlspecialchars($stu['name']) ?></h6>
                    <div class="meta-info d-flex gap-3 flex-wrap">
                        <span><i class="bi bi-hash me-1"></i><?= htmlspecialchars($stu['roll_no']) ?></span>
                        <span><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($stu['email']) ?></span>
                    </div>
                </div>

                <div class="submission-status text-end d-none d-md-block">
                    <?php if ($s): ?>
                        <span class="status-pill bg-success bg-opacity-10 text-success border border-success border-opacity-25">Received</span>
                        <div class="text-muted mt-1" style="font-size: 0.7rem;"><?= date("M j, H:i", strtotime($s['submitted_at'])) ?></div>
                    <?php else: ?>
                        <span class="status-pill bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25">Pending</span>
                    <?php endif; ?>
                </div>

                <div class="actions d-flex gap-2">
                    <?php if ($s): ?>
                        <a href="../uploads/submissions/<?= htmlspecialchars($s['file_path']) ?>" 
                           target="_blank" 
                           class="btn-action" 
                           title="Open File">
                            <i class="bi bi-file-earmark-arrow-down"></i>
                        </a>
                    <?php endif; ?>
                    <button class="btn-action" 
                            data-bs-toggle="modal" 
                            data-bs-target="#msgModal<?= $sid ?>" 
                            title="Direct Message">
                        <i class="bi bi-chat-left-text"></i>
                    </button>
                </div>
            </div>

            <div class="modal fade" id="msgModal<?= $sid ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content shadow-lg">
                        <div class="modal-header border-0">
                            <h5 class="fw-bold mb-0">Direct Message</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="d-flex align-items-center mb-4 p-3 bg-light rounded-4">
                                <div class="student-avatar me-3" style="width:40px; height:40px; font-size: 0.9rem;">
                                    <?= $initials ?>
                                </div>
                                <div>
                                    <p class="mb-0 fw-bold"><?= htmlspecialchars($stu['name']) ?></p>
                                    <p class="mb-0 text-muted small">Send private feedback or notice</p>
                                </div>
                            </div>
                            
                            <form method="POST">
                                <input type="hidden" name="student_id" value="<?= $sid ?>">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold text-muted text-uppercase">Message content</label>
                                    <textarea name="message" 
                                              class="form-control" 
                                              rows="4" 
                                              placeholder="Write your message here..." 
                                              required></textarea>
                                </div>
                                <button type="submit" name="send_message" class="btn btn-primary w-100 py-3 rounded-4 fw-bold shadow-sm mt-2">
                                    <i class="bi bi-send-fill me-2"></i>Send & Notify Student
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>