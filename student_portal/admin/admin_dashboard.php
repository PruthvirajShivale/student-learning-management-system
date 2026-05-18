<?php
session_start();
require "../config.php";

if (!isset($_SESSION['admin_email'])) {
    header("Location: ../login.php");
    exit();
}

// Fetch counts
$students    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM students"))['total'];
$courses     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM courses"))['total'];
$assignments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM assignments"))['total'];

// ===============================
// RECENT SYSTEM ACTIVITY (Last 10)
// ===============================
$activity_query = "
(
    SELECT 
        'student' AS type,
        s.name AS title,
        CONCAT('New student registered (', s.email, ')') AS description,
        s.created_at AS activity_time
    FROM students s
)
UNION ALL
(
    SELECT 
        'enrollment' AS type,
        s.name AS title,
        CONCAT('Enrolled in course ID ', sc.course_id) AS description,
        sc.registered_at AS activity_time
    FROM student_courses sc
    JOIN students s ON sc.student_id = s.id
)
UNION ALL
(
    SELECT 
        'assignment' AS type,
        a.title AS title,
        'New assignment created' AS description,
        a.created_at AS activity_time
    FROM assignments a
)
UNION ALL
(
    SELECT 
        'submission' AS type,
        s.name AS title,
        'Submitted an assignment' AS description,
        sub.submitted_at AS activity_time
    FROM submissions sub
    JOIN students s ON sub.student_id = s.id
)
ORDER BY activity_time DESC
LIMIT 10
";
$activities = mysqli_query($conn, $activity_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin HQ | Modern Dashboard</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --primary: #4f46e5;       /* Indigo 600 */
            --primary-dark: #4338ca;
            --secondary: #64748b;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --bg-body: #f1f5f9;       /* Slate 100 */
            --bg-card: #ffffff;
            --sidebar-width: 260px;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-body);
            color: #0f172a;
            overflow-x: hidden;
        }

        /* --- Sidebar --- */
        .sidebar {
            width: var(--sidebar-width);
            background: #1e1b4b; /* Deep Indigo */
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding: 24px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            box-shadow: 4px 0 24px rgba(0,0,0,0.1);
        }

        .brand-logo {
            color: #fff;
            font-size: 1.4rem;
            font-weight: 800;
            margin-bottom: 40px;
            display: flex;
            align-items: center;
            gap: 12px;
            letter-spacing: -0.5px;
        }

        .nav-link {
            color: #a5b4fc;
            padding: 14px 16px;
            border-radius: 12px;
            margin-bottom: 8px;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 12px;
            border: 1px solid transparent;
        }

        .nav-link:hover {
            color: #fff;
            background: rgba(255,255,255,0.08);
            transform: translateX(5px);
        }

        .nav-link.active {
            background: var(--primary);
            color: #fff;
            box-shadow: 0 0 20px rgba(79, 70, 229, 0.4);
            border: 1px solid rgba(255,255,255,0.1);
        }

        /* --- Main Content Area --- */
        .main-wrapper {
            margin-left: var(--sidebar-width);
            padding: 24px 32px;
            min-height: 100vh;
        }

        /* --- Top Bar --- */
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }
        
        .search-container {
            position: relative;
            width: 300px;
        }
        .search-container input {
            background: #fff;
            border: none;
            padding: 12px 20px 12px 45px;
            border-radius: 50px;
            width: 100%;
            box-shadow: var(--shadow-sm);
            font-size: 0.9rem;
        }
        .search-container i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }

        /* --- Cards --- */
        .dashboard-card {
            background: var(--bg-card);
            border-radius: 20px;
            padding: 24px;
            box-shadow: var(--shadow-md);
            border: 1px solid rgba(255,255,255,0.8);
            height: 100%;
            transition: transform 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        
        /* Subtle decoration circle on cards */
        .dashboard-card::after {
            content: '';
            position: absolute;
            top: -20px;
            right: -20px;
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.1), rgba(79, 70, 229, 0));
            border-radius: 50%;
        }

        .stat-value { font-size: 2.2rem; font-weight: 800; margin: 10px 0; line-height: 1; }
        .stat-label { color: #64748b; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .icon-box {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            margin-bottom: 12px;
        }

        /* --- Timeline --- */
        .timeline-container {
            position: relative;
            padding-left: 20px;
        }
        .timeline-item {
            position: relative;
            padding-bottom: 30px;
            padding-left: 24px;
            border-left: 2px solid #e2e8f0;
        }
        .timeline-item:last-child { border-left: transparent; padding-bottom: 0; }
        
        .timeline-dot {
            position: absolute;
            left: -9px;
            top: 0;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #fff;
            border: 3px solid var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
        }

        /* --- Responsive --- */
        @media(max-width: 992px) {
            .sidebar { width: 80px; padding: 16px 12px; }
            .brand-logo span, .nav-link span, .menu-label { display: none; }
            .main-wrapper { margin-left: 80px; padding: 20px; }
            .nav-link { justify-content: center; padding: 12px; }
            .search-container { display: none; }
        }
    </style>
</head>
<body>

<nav class="sidebar">
    <div class="brand-logo">
        <div style="width: 35px; height: 35px; background: #4f46e5; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
            <i class="bi bi-grid-fill text-white" style="font-size: 18px;"></i>
        </div>
        <span>Nexus<span class="text-primary-subtle">Admin</span></span>
    </div>

    <div class="d-flex flex-column gap-1">
        <span class="menu-label text-white-50 small fw-bold px-3 mb-2" style="font-size: 11px;">MAIN MENU</span>
        <a href="admin_dashboard.php" class="nav-link active">
            <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
        </a>
        <a href="manage_students.php" class="nav-link">
            <i class="bi bi-people"></i> <span>Students</span>
        </a>
        <a href="manage_courses.php" class="nav-link">
            <i class="bi bi-journal-album"></i> <span>Courses</span>
        </a>
        <a href="manage_assignments.php" class="nav-link">
            <i class="bi bi-clipboard-check"></i> <span>Assignments</span>
        </a>
        <a href="send_notification.php" class="nav-link">
            <i class="bi bi-bell"></i> <span>Notifications</span>
        </a>
    </div>

    <div class="mt-auto">
        <div class="p-3 rounded-4 mb-3" style="background: rgba(255,255,255,0.05);">
            <small class="text-white-50 d-block mb-2">Logged in as:</small>
            <div class="text-white fw-bold text-truncate"><?= $_SESSION['admin_email']; ?></div>
        </div>
        <a href="../login.php" class="nav-link text-danger border border-danger-subtle bg-danger-subtle bg-opacity-10 justify-content-center">
            <i class="bi bi-box-arrow-right"></i> <span>Sign Out</span>
        </a>
    </div>
</nav>

<main class="main-wrapper">
    
    <header class="top-bar">
        <div>
            <h2 class="fw-bold m-0 text-dark">Dashboard Overview</h2>
            <p class="text-muted m-0 small">Welcome back, here's what's happening today.</p>
        </div>
        
        <div class="d-flex align-items-center gap-4">
            <div class="search-container">
                <i class="bi bi-search"></i>
                <input type="text" placeholder="Search for students, courses...">
            </div>
            <button class="btn btn-white position-relative p-2 rounded-circle bg-white shadow-sm">
                <i class="bi bi-bell text-secondary fs-5"></i>
                <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"></span>
            </button>
            <div class="bg-white p-1 rounded-pill shadow-sm pe-3 d-flex align-items-center gap-2 border">
                <img src="https://ui-avatars.com/api/?name=Admin&background=4f46e5&color=fff" class="rounded-circle" width="32" height="32" alt="Admin">
                <span class="fw-bold small text-dark d-none d-md-block">Admin Account</span>
            </div>
        </div>
    </header>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="dashboard-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="icon-box bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <span class="badge bg-success-subtle text-success rounded-pill px-3">+12%</span>
                </div>
                <div class="stat-value"><?= $students; ?></div>
                <div class="stat-label">Total Students</div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="dashboard-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="icon-box bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-journal-bookmark-fill"></i>
                    </div>
                    <span class="badge bg-warning-subtle text-warning rounded-pill px-3">Active</span>
                </div>
                <div class="stat-value"><?= $courses; ?></div>
                <div class="stat-label">Total Courses</div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="dashboard-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="icon-box bg-danger bg-opacity-10 text-danger">
                        <i class="bi bi-file-earmark-text-fill"></i>
                    </div>
                    <span class="badge bg-danger-subtle text-danger rounded-pill px-3">Pending</span>
                </div>
                <div class="stat-value"><?= $assignments; ?></div>
                <div class="stat-label">Assignments</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        
        <div class="col-lg-8">
            <div class="dashboard-card p-0 overflow-hidden">
                <div class="p-4 border-bottom d-flex justify-content-between align-items-center bg-light bg-opacity-25">
                    <h5 class="fw-bold m-0"><i class="bi bi-activity text-primary me-2"></i>Live Activity Feed</h5>
                    <button class="btn btn-sm btn-outline-primary rounded-pill">View All</button>
                </div>
                
                <div class="p-4">
                    <?php if(mysqli_num_rows($activities) > 0): ?>
                        <div class="timeline-container">
                        <?php while($act = mysqli_fetch_assoc($activities)): 
                            // Dynamic colors based on type
                            $color = 'primary';
                            $icon = 'bi-circle-fill';
                            if($act['type'] == 'enrollment') { $color = 'success'; $icon = 'bi-check-circle-fill'; }
                            if($act['type'] == 'assignment') { $color = 'warning'; $icon = 'bi-exclamation-circle-fill'; }
                            if($act['type'] == 'submission') { $color = 'info'; $icon = 'bi-cloud-upload-fill'; }
                        ?>
                            <div class="timeline-item">
                                <div class="timeline-dot" style="border-color: var(--<?= $color ?>); color: var(--<?= $color ?>);"></div>
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($act['title']) ?></h6>
                                        <p class="text-muted small mb-0"><?= htmlspecialchars($act['description']) ?></p>
                                    </div>
                                    <span class="badge bg-light text-secondary border">
                                        <?= date("H:i", strtotime($act['activity_time'])) ?>
                                    </span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png" width="80" class="opacity-25 mb-3">
                            <p class="text-muted fw-bold">No recent activities found.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="dashboard-card mb-4">
                <h6 class="fw-bold mb-4 text-dark">Data Distribution</h6>
                <div style="height: 250px; position: relative;">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>

            <div class="dashboard-card bg-dark text-white" style="background: linear-gradient(145deg, #1e293b, #0f172a);">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="spinner-grow spinner-grow-sm text-success" role="status"></div>
                    <small class="fw-bold text-success ls-1">SYSTEM OPERATIONAL</small>
                </div>
                <h5 class="fw-bold mb-3">Server Performance</h5>
                <div class="mb-3">
                    <div class="d-flex justify-content-between small text-white-50 mb-1">
                        <span>CPU Usage</span>
                        <span>24%</span>
                    </div>
                    <div class="progress bg-white bg-opacity-10" style="height: 6px;">
                        <div class="progress-bar bg-success" style="width: 24%"></div>
                    </div>
                </div>
                <div>
                    <div class="d-flex justify-content-between small text-white-50 mb-1">
                        <span>Database Load</span>
                        <span>Stable</span>
                    </div>
                    <div class="progress bg-white bg-opacity-10" style="height: 6px;">
                        <div class="progress-bar bg-primary" style="width: 45%"></div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<script>
    const ctx = document.getElementById('statusChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Students', 'Courses', 'Assignments'],
            datasets: [{
                data: [<?= $students ?>, <?= $courses ?>, <?= $assignments ?>],
                backgroundColor: ['#4f46e5', '#f59e0b', '#ef4444'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            cutout: '75%',
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, font: { family: 'Plus Jakarta Sans' } } }
            }
        }
    });
</script>

</body>
</html>