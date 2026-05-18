<?php
session_start();
require 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit;
}

$student_id = $_SESSION['student_id'];

// -----------------------------------------
// FETCH LOGGED-IN STUDENT DETAILS
// -----------------------------------------
$stmt = $conn->prepare("
    SELECT name, roll_no, college, email, contact, parent_contact, created_at 
    FROM students 
    WHERE id = ?
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$stmt->bind_result($name, $roll_no, $college, $email, $contact, $parent_contact, $created_at);
$stmt->fetch();
$stmt->close();

// -----------------------------------------
// FETCH UNREAD NOTIFICATIONS COUNT
// -----------------------------------------
$noti = $conn->query("SELECT COUNT(*) AS total FROM notifications WHERE student_id=$student_id AND is_read=0");
$noti_count = $noti->fetch_assoc()['total'];

// -----------------------------------------
// FETCH UPLOADED FILES
// -----------------------------------------
$files = [];
$res = $conn->query("
    SELECT id, file_name, file_type, file_path, is_shared, uploaded_at, share_token 
    FROM files 
    WHERE student_id = $student_id 
    ORDER BY uploaded_at DESC
");
while ($r = $res->fetch_assoc()) $files[] = $r;

// -----------------------------------------
// FETCH REGISTERED COURSES
// -----------------------------------------
$courseStmt = $conn->prepare("
    SELECT c.id, c.course_code, c.course_name, c.instructor_name, 
           c.schedule_day, c.schedule_time, sc.registered_at
    FROM courses c 
    JOIN student_courses sc ON c.id = sc.course_id 
    WHERE sc.student_id = ?
");
$courseStmt->bind_param("i", $student_id);
$courseStmt->execute();
$courseRes = $courseStmt->get_result();
$registered_courses = $courseRes->fetch_all(MYSQLI_ASSOC);
$courseStmt->close(); 

// -----------------------------------------
// CALCULATE ATTENDANCE (MERGED LOGIC)
// -----------------------------------------
$totalLectures = 0;
$attendedLectures = 0;

foreach($registered_courses as $course) {
    $course_id = $course['id'];

    // Total lectures in this course
    $stmt = $conn->prepare("SELECT COUNT(*) FROM course_lectures WHERE course_id=?");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $stmt->bind_result($lecturesCount);
    $stmt->fetch();
    $stmt->close();

    $totalLectures += $lecturesCount;

    // Lectures attended by student
    $stmt = $conn->prepare("SELECT COUNT(*) FROM student_activity WHERE student_id=? AND course_id=?");
    $stmt->bind_param("ii", $student_id, $course_id);
    $stmt->execute();
    $stmt->bind_result($attendedCount);
    $stmt->fetch();
    $stmt->close();

    $attendedLectures += $attendedCount;
}

// Prevent division by zero
$attendancePercent = ($totalLectures > 0) ? round(($attendedLectures / $totalLectures) * 100) : 0;

// Calculate Greeting
$hour = date('H');
if ($hour < 12) $greeting = "Good Morning";
elseif ($hour < 18) $greeting = "Good Afternoon";
else $greeting = "Good Evening";

$firstName = explode(' ', $name)[0];
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | <?php echo htmlspecialchars($name); ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --font-main: 'Plus Jakarta Sans', sans-serif;
            
            /* Modern Palette - Indigo/Violet */
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --secondary: #64748b;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            
            /* Light Mode */
            --bg-body: #f3f4f6;
            --bg-sidebar: #ffffff;
            --bg-card: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --glass-bg: rgba(255, 255, 255, 0.7);
            --glass-border: rgba(255, 255, 255, 0.5);
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        [data-theme="dark"] {
            --bg-body: #0f172a;
            --bg-sidebar: #1e293b;
            --bg-card: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: #334155;
            --glass-bg: rgba(30, 41, 59, 0.7);
            --glass-border: rgba(255, 255, 255, 0.05);
            --shadow-sm: none;
            --shadow-md: none;
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.5);
        }

        body {
            font-family: var(--font-main);
            background-color: var(--bg-body);
            color: var(--text-main);
            overflow-x: hidden;
            transition: background-color 0.3s ease;
        }

        /* --- Layout --- */
        .wrapper { display: flex; width: 100%; min-height: 100vh; }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            background: var(--bg-sidebar);
            border-right: 1px solid var(--border-color);
            position: fixed;
            top: 0; left: 0; height: 100vh;
            z-index: 100;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .logo {
            display: flex; align-items: center; gap: 12px;
            font-weight: 800; font-size: 1.25rem;
            color: var(--text-main);
            margin-bottom: 2.5rem;
        }
        .logo i { color: var(--primary); font-size: 1.5rem; }

        .nav-link {
            display: flex; align-items: center; gap: 12px;
            padding: 0.85rem 1rem;
            color: var(--text-muted);
            text-decoration: none;
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.2s;
            margin-bottom: 0.25rem;
        }
        .nav-link:hover, .nav-link.active {
            background: linear-gradient(90deg, var(--primary) 0%, #6366f1 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }
        .nav-link i { font-size: 1.25rem; }

        /* Main Content */
        .main {
            flex: 1;
            margin-left: 280px;
            padding: 1.5rem 2rem;
            transition: margin 0.3s ease;
        }

        /* Header */
        .header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 2rem;
            padding: 1rem;
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 16px;
            border: 1px solid var(--glass-border);
            position: sticky; top: 1rem; z-index: 90;
            box-shadow: var(--shadow-md);
        }

        .search-bar {
            position: relative;
            width: 300px;
        }
        .search-bar input {
            width: 100%;
            padding: 0.6rem 1rem 0.6rem 2.5rem;
            border-radius: 10px;
            border: 1px solid var(--border-color);
            background: var(--bg-body);
            color: var(--text-main);
            outline: none;
        }
        .search-bar i {
            position: absolute; left: 12px; top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        /* Cards */
        .glass-card {
            background: var(--bg-card);
            border-radius: 20px;
            padding: 1.5rem;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
            transition: transform 0.2s, box-shadow 0.2s;
            height: 100%;
            position: relative;
            overflow: hidden;
        }
        .glass-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #4f46e5 0%, #8b5cf6 100%);
            color: white;
            border: none;
            position: relative;
        }
        .hero::before {
            content: '';
            position: absolute;
            top: -50%; right: -20%;
            width: 400px; height: 400px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            filter: blur(60px);
        }

        /* Action Buttons */
        .btn-action {
            width: 40px; height: 40px;
            border-radius: 10px;
            display: grid; place-items: center;
            border: 1px solid var(--border-color);
            background: var(--bg-card);
            color: var(--text-main);
            transition: all 0.2s;
            cursor: pointer;
        }
        .btn-action:hover { background: var(--bg-body); color: var(--primary); }

        /* Course Grid */
        .course-card {
            display: flex; flex-direction: column;
            justify-content: space-between;
            height: 100%;
        }
        .course-tag {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* File List */
        .file-item {
            display: flex; align-items: center;
            padding: 12px;
            border-radius: 12px;
            transition: background 0.2s;
            border-bottom: 1px solid var(--border-color);
        }
        .file-item:last-child { border-bottom: none; }
        .file-item:hover { background: var(--bg-body); }
        .file-icon {
            width: 45px; height: 45px;
            border-radius: 10px;
            background: rgba(79, 70, 229, 0.1);
            color: var(--primary);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.25rem;
            margin-right: 1rem;
        }

        /* Responsive */
        @media (max-width: 991px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.show { transform: translateX(0); }
            .main { margin-left: 0; padding: 1rem; }
        }

        /* Utilities */
        .text-gradient {
            background: linear-gradient(to right, #4f46e5, #ec4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .pulse { animation: pulse 2s infinite; }
        @keyframes pulse { 0% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.1); opacity: 0.8; } 100% { transform: scale(1); opacity: 1; } }
    </style>
</head>
<body>

<div class="wrapper">
    <aside class="sidebar" id="sidebar">
        <div class="logo">
            <i class="ri-graduation-cap-fill"></i>
            <span>StudentOS</span>
        </div>

        <nav class="flex-grow-1">
            <a href="#" class="nav-link active"><i class="ri-dashboard-line"></i> Dashboard</a>
            <a href="courses.php" class="nav-link"><i class="ri-book-open-line"></i> Courses</a>
            <a href="assignments.php" class="nav-link"><i class="ri-task-line"></i> Assignments</a>
            <a href="upload.php" class="nav-link"><i class="ri-folder-cloud-line"></i> Files</a>
           
        </nav>

        <div class="mt-auto pt-4 border-top" style="border-color: var(--border-color) !important;">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width:40px; height:40px; font-size:1.1rem;">
                    <?php echo substr($name, 0, 1); ?>
                </div>
                <div style="line-height: 1.2;">
                    <div class="fw-bold fs-6"><?php echo explode(' ', $name)[0]; ?></div>
                    <div class="text-muted small" style="font-size: 0.75rem;">ID: <?php echo $roll_no; ?></div>
                </div>
            </div>
            <a href="logout.php" class="btn w-100 btn-danger-subtle text-danger fw-bold d-flex align-items-center justify-content-center gap-2">
                <i class="ri-logout-box-r-line"></i> Sign Out
            </a>
        </div>
    </aside>

    <main class="main">
        <header class="header">
            <div class="d-flex align-items-center gap-3">
                <button class="btn-action d-lg-none" onclick="document.getElementById('sidebar').classList.toggle('show')">
                    <i class="ri-menu-2-line"></i>
                </button>
                <div class="d-flex flex-column">
                    <span class="text-muted small fw-bold text-uppercase">Academic Dashboard</span>
                    <span class="fw-bold fs-5"><?php echo $college; ?></span>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                <div class="search-bar d-none d-md-block">
                    <i class="ri-search-line"></i>
                    <input type="text" placeholder="Search courses, files...">
                </div>
                
                <button class="btn-action position-relative" onclick="window.location.href='notifications.php'">
                    <i class="ri-notification-3-line"></i>
                    <?php if ($noti_count > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"></span>
                    <?php endif; ?>
                </button>
                
                <button class="btn-action" id="themeToggle">
                    <i class="ri-moon-line"></i>
                </button>
            </div>
        </header>

        <div class="container-fluid p-0">
            
            <div class="row g-4 mb-4">
                <div class="col-lg-8">
                    <div class="glass-card hero p-4 d-flex align-items-center justify-content-between">
                        <div class="z-1">
                            <span class="badge bg-white bg-opacity-25 backdrop-blur text-white mb-2">
                                <i class="ri-calendar-check-line me-1"></i> <?php echo date('l, F jS'); ?>
                            </span>
                            <h1 class="display-5 fw-bold mb-2"><?php echo $greeting; ?>, <?php echo $firstName; ?>!</h1>
                            <p class="mb-4 text-white-50 fs-5">You have <?php echo count($registered_courses); ?> active courses this semester.</p>
                            
                            <div class="d-flex gap-3">
                                <a href="courses.php" class="btn btn-light fw-bold px-4 rounded-pill shadow-sm text-primary">View Schedule</a>
                                <a href="upload.php" class="btn btn-outline-light fw-bold px-4 rounded-pill">Upload File</a>
                            </div>
                        </div>
                        <div class="d-none d-md-block z-1 opacity-75">
                            <i class="ri-macbook-line" style="font-size: 8rem;"></i>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="glass-card bg-primary-subtle border-0 h-100 d-flex flex-column justify-content-center align-items-center text-center p-4">
                        <div id="nextClassWidget">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-2 text-muted small">Loading schedule...</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-6 col-md-3">
                    <div class="glass-card p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-blue-100 text-primary p-3 bg-opacity-10" style="background: #e0e7ff;">
                                <i class="ri-book-open-line fs-4"></i>
                            </div>
                            <div>
                                <h3 class="mb-0 fw-bold"><?php echo count($registered_courses); ?></h3>
                                <span class="text-muted small fw-bold">Enrolled</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="glass-card p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle text-success p-3 bg-opacity-10" style="background: #dcfce7;">
                                <i class="ri-folder-shield-2-line fs-4"></i>
                            </div>
                            <div>
                                <h3 class="mb-0 fw-bold"><?php echo count($files); ?></h3>
                                <span class="text-muted small fw-bold">Resources</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="glass-card p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle text-warning p-3 bg-opacity-10" style="background: #fef3c7;">
                                <i class="ri-notification-badge-line fs-4"></i>
                            </div>
                            <div>
                                <h3 class="mb-0 fw-bold"><?php echo $noti_count; ?></h3>
                                <span class="text-muted small fw-bold">Alerts</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="glass-card p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle text-info p-3 bg-opacity-10" style="background: #e0f2fe;">
                                <i class="ri-pie-chart-line fs-4"></i>
                            </div>
                            <div>
                                <h3 class="mb-0 fw-bold"><?php echo $attendancePercent; ?>%</h3>
                                <span class="text-muted small fw-bold">Attendance</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                
                <div class="col-lg-8">
                    <div class="glass-card">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="fw-bold m-0"><i class="ri-bookmark-3-fill text-primary me-2"></i>My Courses</h5>
                            <button class="btn btn-sm btn-light border rounded-pill px-3">Filter</button>
                        </div>

                        <?php if(count($registered_courses) > 0): ?>
                            <div class="row g-3">
                                <?php foreach($registered_courses as $course): 
                                    // Random gradient for course tag
                                    $colors = ['bg-primary', 'bg-success', 'bg-warning', 'bg-info'];
                                    $randColor = $colors[array_rand($colors)];
                                ?>
                                <div class="col-md-6">
                                    <div class="glass-card course-card p-3" style="min-height: 140px; background: var(--bg-body);">
                                        <div>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="course-tag text-white <?php echo $randColor; ?> bg-opacity-75">
                                                    <?php echo htmlspecialchars($course['course_code']); ?>
                                                </span>
                                                <span class="text-muted small"><i class="ri-time-line"></i> <?php echo $course['schedule_day']; ?></span>
                                            </div>
                                            <h6 class="fw-bold mb-1 text-truncate" title="<?php echo htmlspecialchars($course['course_name']); ?>">
                                                <?php echo htmlspecialchars($course['course_name']); ?>
                                            </h6>
                                            <p class="text-muted small mb-0"><?php echo htmlspecialchars($course['instructor_name']); ?></p>
                                        </div>
                                        <div class="mt-3 pt-2 border-top border-light d-flex justify-content-between align-items-center">
                                            <small class="text-muted fw-bold"><?php echo $course['schedule_time']; ?></small>
                                            <a href="#" class="btn btn-sm btn-white rounded-circle shadow-sm" style="width:32px; height:32px; display:grid; place-items:center;"><i class="ri-arrow-right-line"></i></a>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5 text-muted">
                                <i class="ri-ghost-line fs-1 d-block mb-2"></i>
                                No courses registered yet.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="glass-card p-3 mb-4 bg-primary text-white" style="background: linear-gradient(45deg, #111827, #1f2937);">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold m-0">Quick Actions</h6>
                             <span class="badge bg-white text-dark"><?php echo count($files); ?> Files</span>
                        </div>
                        
                        <div class="d-flex gap-2 mb-3">
                            <a href="upload.php" class="btn btn-sm btn-light flex-fill fw-bold border-0"><i class="ri-upload-cloud-line text-primary"></i> Upload</a>
                            <a href="upload.php" class="btn btn-sm btn-white bg-white bg-opacity-10 text-white flex-fill fw-bold"><i class="ri-folder-open-line"></i> View All</a>
                        </div>
                        
                        <h6 class="small fw-bold text-white-50 text-uppercase ls-1 mb-2">Recent Uploads</h6>
                        <div class="d-flex flex-column gap-2" style="max-height: 250px; overflow-y: auto;">
                            <?php if(count($files) > 0): ?>
                                <?php foreach (array_slice($files, 0, 4) as $f): 
                                    $ext = strtolower(pathinfo($f['file_name'], PATHINFO_EXTENSION));
                                    $icon = 'ri-file-line';
                                    if(in_array($ext, ['pdf'])) $icon = 'ri-file-pdf-line';
                                    elseif(in_array($ext, ['doc','docx'])) $icon = 'ri-file-word-line';
                                    elseif(in_array($ext, ['jpg','png'])) $icon = 'ri-image-line';
                                ?>
                                <div class="d-flex align-items-center bg-white bg-opacity-10 p-2 rounded">
                                    <div class="me-3 text-white opacity-75">
                                        <i class="<?php echo $icon; ?> fs-5"></i>
                                    </div>
                                    <div class="flex-grow-1 overflow-hidden">
                                        <div class="fw-bold text-truncate text-white" style="font-size: 0.9rem;"><?php echo htmlspecialchars($f['file_name']); ?></div>
                                        <div class="d-flex justify-content-between align-items-center mt-1">
                                            <span class="badge <?php echo $f['is_shared'] ? 'bg-success' : 'bg-secondary'; ?> rounded-pill" style="font-size:0.6rem; padding: 2px 6px;">
                                                <?php echo $f['is_shared'] ? 'Public' : 'Private'; ?>
                                            </span>
                                            <span class="text-white-50 small" style="font-size: 0.7rem;"><?php echo date('M d', strtotime($f['uploaded_at'])); ?></span>
                                        </div>
                                    </div>
                                    <a href="download.php?file_id=<?php echo $f['id']; ?>" class="btn btn-sm btn-link text-white-50"><i class="ri-download-line"></i></a>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-center text-white-50 small py-3">No files found.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // --------------------------------
    // 1. Dark Mode Logic
    // --------------------------------
    const themeBtn = document.getElementById('themeToggle');
    const root = document.documentElement;
    const icon = themeBtn.querySelector('i');

    const toggleTheme = () => {
        const isDark = root.getAttribute('data-theme') === 'dark';
        const newTheme = isDark ? 'light' : 'dark';
        
        root.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        
        icon.className = newTheme === 'dark' ? 'ri-sun-line' : 'ri-moon-line';
    };

    if (localStorage.getItem('theme') === 'dark') {
        root.setAttribute('data-theme', 'dark');
        icon.className = 'ri-sun-line';
    }

    themeBtn.addEventListener('click', toggleTheme);

    // --------------------------------
    // 2. Real-time "Next Class" Widget
    // --------------------------------
    // Passing PHP data to JS safely
    const courses = <?php echo json_encode($registered_courses); ?>;
    
    function updateNextClass() {
        const now = new Date();
        const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        const currentDayIndex = now.getDay();
        const currentMinutes = now.getHours() * 60 + now.getMinutes();

        let nextClass = null;
        let minDiff = Infinity;

        courses.forEach(c => {
            // Assume format "Monday" and "14:30" or "09:00 AM"
            // This is a simplified parser. You might need to adjust based on exact DB string format
            const courseDayIndex = days.indexOf(c.schedule_day); 
            
            if(courseDayIndex === -1) return; // Invalid day string

            // Parse Time (Assuming HH:MM or HH:MM:SS)
            let [timePart, modifier] = c.schedule_time.split(' ');
            let [h, m] = timePart.split(':');
            h = parseInt(h); m = parseInt(m);
            if(modifier === 'PM' && h < 12) h += 12;
            if(modifier === 'AM' && h === 12) h = 0;
            
            const classMinutes = h * 60 + m;

            // Calculate difference in minutes from NOW
            // 1. Calculate scheduling index (DayIndex * 1440 + Minutes)
            let currentTotal = currentDayIndex * 1440 + currentMinutes;
            let classTotal = courseDayIndex * 1440 + classMinutes;

            // If class is earlier in week than now, add a week (7 * 1440)
            if (classTotal < currentTotal) {
                classTotal += 10080; 
            }

            const diff = classTotal - currentTotal;

            if (diff < minDiff && diff >= 0) {
                minDiff = diff;
                nextClass = { ...c, diff: diff };
            }
        });

        const widget = document.getElementById('nextClassWidget');
        
        if (nextClass) {
            const hoursAway = Math.floor(nextClass.diff / 60);
            const minsAway = nextClass.diff % 60;
            let timeString = "";
            
            if(hoursAway > 24) timeString = `in ${Math.floor(hoursAway/24)} days`;
            else if(hoursAway > 0) timeString = `in ${hoursAway} hr ${minsAway} min`;
            else timeString = `in ${minsAway} min`;

            widget.innerHTML = `
                <div class="text-primary mb-2"><i class="ri-time-fill fs-1"></i></div>
                <h6 class="text-muted text-uppercase small fw-bold ls-1 mb-1">Next Class</h6>
                <h4 class="fw-bold mb-1">${nextClass.course_code}</h4>
                <div class="text-primary fw-bold">${timeString}</div>
                <small class="text-muted d-block mt-1">${nextClass.schedule_day} @ ${nextClass.schedule_time}</small>
            `;
        } else {
            widget.innerHTML = `
                <div class="text-success mb-2"><i class="ri-cup-line fs-1"></i></div>
                <h5 class="fw-bold">No upcoming classes</h5>
                <p class="text-muted small">Enjoy your free time!</p>
            `;
        }
    }

    // Run on load
    if(courses.length > 0) {
        updateNextClass();
        setInterval(updateNextClass, 60000); // Update every minute
    } else {
         document.getElementById('nextClassWidget').innerHTML = `
            <div class="text-muted mb-2"><i class="ri-calendar-2-line fs-1"></i></div>
            <p class="small text-muted">No schedule data</p>
        `;
    }
</script>

</body>
</html>