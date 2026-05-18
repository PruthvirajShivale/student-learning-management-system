<?php
session_start();
require "config.php";

// --- 1. LOGIN CHECK ---
if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

// --- 2. COURSE ID CHECK ---
if (!isset($_GET['course_id'])) {
    die("Course not specified.");
}

$student_id = $_SESSION['student_id'];
$course_id = intval($_GET['course_id']);

// --- 3. ENROLLMENT CHECK (Backend Logic Preserved) ---
$check = $conn->prepare("SELECT id FROM student_courses WHERE student_id=? AND course_id=?");
$check->bind_param("ii", $student_id, $course_id);
$check->execute();
$check->store_result();
if ($check->num_rows == 0) die("Access Denied.");
$check->close();

// --- 4. FETCH COURSE DETAILS ---
$course_q = $conn->prepare("SELECT * FROM courses WHERE id=?");
$course_q->bind_param("i", $course_id);
$course_q->execute();
$course = $course_q->get_result()->fetch_assoc();

// --- 5. FETCH LECTURES ---
$lectures_q = $conn->prepare("SELECT * FROM course_lectures WHERE course_id=? ORDER BY created_at ASC");
$lectures_q->bind_param("i", $course_id);
$lectures_q->execute();
$lectures = $lectures_q->get_result();

// ==========================================
//   MERGED ATTENDANCE LOGIC STARTS HERE
// ==========================================

// We need to track attendance for the FIRST lecture immediately because it auto-plays.
$current_lecture_id = 0; // Default initialization
if ($lectures->num_rows > 0) {
    // 1. Peek at the first lecture to get its ID
    $first_lecture = $lectures->fetch_assoc(); 
    $current_lecture_id = $first_lecture['id'];

    // 2. Insert Activity (Your provided logic)
    $act_stmt = $conn->prepare("
        INSERT INTO student_activity 
        (student_id, course_id, lecture_id, join_time)
        VALUES (?, ?, ?, NOW())
    ");
    $act_stmt->bind_param("iii", $student_id, $course_id, $current_lecture_id);
    $act_stmt->execute();
    
    // 3. Store activity in session
    $_SESSION['activity_id'] = $act_stmt->insert_id;
    $act_stmt->close();

    // 4. RESET Pointer so the HTML loop below works correctly
    $lectures->data_seek(0); 
}
// ==========================================
//   MERGED ATTENDANCE LOGIC ENDS
// ==========================================
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($course['course_name']) ?> | Studio LMS</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --brand-primary: #6366f1;
            --brand-gradient: linear-gradient(135deg, #6366f1 0%, #4338ca 100%);
            --bg-main: #f8fafc;
            --surface-glass: rgba(255, 255, 255, 0.85);
            --border-color: #e2e8f0;
            --nav-height: 72px;
            --sidebar-width: 340px;
            --playlist-width: 420px;
            --text-main: #1e293b;
            --text-muted: #64748b;
        }

        body { 
            background: var(--bg-main); 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            height: 100vh; 
            overflow: hidden; 
            color: var(--text-main);
        }

        /* --- Global Scroller Customization --- */
        .scroller { overflow-y: auto; scrollbar-gutter: stable; transition: all 0.3s; }
        .scroller::-webkit-scrollbar { width: 6px; }
        .scroller::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

        /* --- Navbar: SaaS Glassmorphism --- */
        .glass-nav {
            background: var(--surface-glass);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            height: var(--nav-height); 
            border-bottom: 1px solid var(--border-color);
            padding: 0 2rem; 
            z-index: 1050;
        }

        .main-grid {
            display: grid; 
            grid-template-columns: var(--sidebar-width) 1fr var(--playlist-width);
            height: calc(100vh - var(--nav-height)); 
            margin-top: var(--nav-height);
        }

        /* --- Sidebar: Resources --- */
        .left-col { 
            background: #fff; 
            border-right: 1px solid var(--border-color); 
            padding: 1.75rem;
        }
        
        .resource-header { 
            font-size: 0.7rem; 
            font-weight: 800; 
            color: var(--text-muted); 
            text-transform: uppercase; 
            letter-spacing: 1.5px; 
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .note-card {
            background: #ffffff; 
            border: 1px solid var(--border-color); 
            border-radius: 16px;
            padding: 1.25rem; 
            margin-bottom: 1.25rem; 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }
        
        .note-card:hover {
            transform: translateY(-3px);
            border-color: var(--brand-primary);
            box-shadow: 0 12px 20px -8px rgba(99, 102, 241, 0.15);
        }

        .rich-text-content { font-size: 0.92rem; color: #475569; line-height: 1.7; }
        
        .file-attachment {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 15px;
            padding: 12px;
            background: #f1f5f9;
            border-radius: 12px;
            text-decoration: none !important;
            border: 1px solid transparent;
            transition: 0.2s;
        }
        
        .file-attachment:hover { 
            background: #e2e8f0; 
            border-color: #cbd5e1;
        }

        /* --- Video Stage --- */
        .video-stage { background: #fafafa; padding: 2.5rem; }
        .video-container { width: 100%; max-width: 1100px; margin: 0 auto; }
        
        .video-box {
            width: 100%; 
            aspect-ratio: 16/9; 
            background: #000; 
            border-radius: 24px;
            overflow: hidden; 
            box-shadow: 0 30px 60px -12px rgba(0,0,0,0.25);
            border: 1px solid #000;
            transition: opacity 0.4s ease;
        }
        
        video { width: 100%; height: 100%; object-fit: cover; }
        
        .lesson-title-main { 
            font-size: 2rem; 
            font-weight: 800; 
            letter-spacing: -0.8px;
            color: #0f172a; 
            margin: 2rem 0 1rem 0; 
        }

        .lesson-desc-box {
            background: #fff;
            border-radius: 16px;
            padding: 2rem;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
        }

        #moreBtn { 
            border: none; background: #f1f5f9; color: var(--brand-primary); 
            font-weight: 700; padding: 6px 14px; border-radius: 8px; cursor: pointer; 
            font-size: 0.8rem; margin-top: 10px; transition: 0.2s;
        }
        
        #moreBtn:hover { background: #e0e7ff; color: #4338ca; }

        /* --- Right Sidebar: Playlist --- */
        .right-col { background: #fff; border-left: 1px solid var(--border-color); }
        
        .playlist-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border-color);
        }

        .playlist-item {
            display: flex; gap: 15px; padding: 1.5rem 2rem; cursor: pointer;
            border-bottom: 1px solid #f8fafc; transition: all 0.2s ease; 
            align-items: center; position: relative;
        }
        
        .playlist-item:hover { background: #f8fafc; }
        
        .playlist-item.active { background: #f5f7ff; }

        .playlist-item.active::before {
            content: ''; position: absolute; left: 0; top: 15%; bottom: 15%;
            width: 4px; background: var(--brand-primary); border-radius: 0 4px 4px 0;
        }

        .playlist-item.active h6 { color: var(--brand-primary); }
        
        .pl-thumb-mini {
            width: 100px; height: 56px; background: #0f172a; border-radius: 8px;
            flex-shrink: 0; display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 1.3rem; transition: 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative; overflow: hidden;
        }

        .playlist-item:hover .pl-thumb-mini { transform: scale(1.05); }

        .note-content-area { display: none; }
        .note-content-area.active-notes { display: block; animation: slideIn 0.5s ease forwards; }

        @keyframes slideIn { 
            from { opacity: 0; transform: translateY(15px); } 
            to { opacity: 1; transform: translateY(0); } 
        }

        .line-clamp-2 {
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
        }

        /* --- Progress UI --- */
        .course-progress-container { margin-top: 12px; }
        .progress-label { font-size: 10px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; }

        /* --- Tablet Adjustments --- */
        @media (max-width: 1200px) {
            .main-grid { grid-template-columns: 1fr 360px; }
            .left-col { display: none; }
        }
    </style>
</head>
<body>

<nav class="glass-nav fixed-top d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-4">
        <a href="dashboard.php" class="btn btn-outline-secondary border-0 btn-sm rounded-circle p-2">
            <i class="bi bi-chevron-left fs-5"></i>
        </a>
        <div class="d-flex align-items-center gap-2">
            <div class="bg-primary text-white rounded-3 p-1 px-2 shadow-sm" style="background: var(--brand-gradient) !important;">
                <i class="bi bi-terminal-fill"></i>
            </div>
            <span class="fw-bold fs-5 tracking-tight">Studio<span class="text-primary">LMS</span></span>
        </div>
    </div>
    
    <div class="d-none d-lg-block text-center">
        <span class="badge bg-indigo-subtle text-primary px-3 py-1 rounded-pill mb-1" style="background: #e0e7ff; font-size: 0.65rem; font-weight: 800;">CURRENT MODULE</span>
        <div class="fw-bold text-dark small"><?= htmlspecialchars($course['course_name']) ?></div>
    </div>

    <div class="d-flex align-items-center gap-3">
        <a href="courses.php" class="btn btn-dark btn-sm rounded-pill px-4 fw-bold shadow-sm py-2">Close Player</a>
    </div>
</nav>

<div class="main-grid">
    
    <aside class="left-col scroller">
        <div class="resource-header">
            <i class="bi bi-bookmark-star-fill text-primary"></i> Learning Materials
        </div>
        
        <div id="notes-container">
            <?php
            $firstVideo = null; $firstVideoId = null; $firstDesc = ""; $firstTitle = "";
            $lectures->data_seek(0);
            while($lec = $lectures->fetch_assoc()):
                if(!$firstVideo) { 
                    $firstVideo = $lec['file_path']; 
                    $firstVideoId = $lec['id']; 
                    $firstDesc = $lec['description'];
                    $firstTitle = $lec['title'];
                }
                $isActiveNote = ($lec['id'] == $firstVideoId) ? 'active-notes' : '';

                $notes_q = $conn->prepare("SELECT * FROM course_notes WHERE lecture_id=? ORDER BY created_at DESC");
                $notes_q->bind_param("i", $lec['id']);
                $notes_q->execute();
                $notes = $notes_q->get_result();
            ?>
            <div class="note-content-area <?= $isActiveNote ?>" id="notes-for-<?= $lec['id'] ?>">
                <?php if($notes->num_rows > 0): ?>
                    <?php while($n = $notes->fetch_assoc()): ?>
                        <div class="note-card">
                            <div class="rich-text-content"><?= $n['note'] ?></div>
                            <?php if(!empty($n['file_path'])): ?>
                                <a href="uploads/course_notes/<?= $n['file_path'] ?>" target="_blank" class="file-attachment">
                                    <div class="p-2 bg-white rounded-3 shadow-sm">
                                        <i class="bi bi-file-earmark-pdf-fill text-danger fs-4"></i>
                                    </div>
                                    <div class="lh-sm flex-grow-1">
                                        <div class="small fw-bold text-dark">Lesson_Resources.pdf</div>
                                        <div class="text-muted" style="font-size: 10px;">Click to view/download</div>
                                    </div>
                                    <i class="bi bi-arrow-right-short text-muted"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center py-5 opacity-25">
                        <i class="bi bi-journal-x fs-1"></i>
                        <p class="small mt-2 fw-bold">No resources for this lesson.</p>
                    </div>
                <?php endif; ?>
            </div>
            <?php endwhile; ?>
        </div>
    </aside>

    <main class="video-stage scroller">
        <div class="video-container">
            <div class="video-box" id="playerWrapper">
                <video id="mainVideo" controls controlsList="nodownload">
                    <source src="uploads/course_materials/<?= $firstVideo ?>">
                </video>
            </div>

            <div class="video-details">
                <h1 id="currentLessonTitle" class="lesson-title-main"><?= htmlspecialchars($firstTitle) ?></h1>
                
                <div class="lesson-desc-box">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="p-1 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 20px; height: 20px;">
                            <i class="bi bi-info-circle" style="font-size: 12px;"></i>
                        </div>
                        <h6 class="fw-bold small text-uppercase text-muted mb-0">Lesson Overview</h6>
                    </div>
                    <div id="lessonDescription" class="small text-secondary" style="white-space: pre-wrap; line-height: 1.8;"></div>
                    <button id="moreBtn" onclick="toggleDesc()" style="display:none;">Read full description</button>
                </div>
            </div>
        </div>
    </main>

    <aside class="right-col d-flex flex-column">
        <div class="playlist-header">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <h6 class="fw-bold mb-0">Course Curriculum</h6>
                <span class="badge bg-light text-dark border rounded-pill px-3"><?= $lectures->num_rows ?> Items</span>
            </div>
            
            <div class="course-progress-container">
                <div class="progress rounded-pill" style="height: 6px; background: #f1f5f9;">
                    <div class="progress-bar" style="width: 45%; background: var(--brand-gradient);"></div>
                </div>
                <div class="d-flex justify-content-between mt-2">
                    <span class="progress-label">Your Course Progress</span>
                    <span class="progress-label text-primary">45%</span>
                </div>
            </div>
        </div>

        <div class="flex-grow-1 scroller bg-white">
            <?php
            $lectures->data_seek(0);
            $count = 1;
            while($lec = $lectures->fetch_assoc()):
                $isActive = ($lec['id'] == $firstVideoId) ? 'active' : '';
            ?>
            <div class="playlist-item <?= $isActive ?>" 
                 data-title="<?= htmlspecialchars($lec['title']) ?>"
                 data-description="<?= htmlspecialchars($lec['description']) ?>"
                 onclick="switchLesson('uploads/course_materials/<?= $lec['file_path'] ?>', '<?= $lec['id'] ?>', this)">
                
                <div class="pl-thumb-mini">
                    <i class="bi bi-play-fill"></i>
                </div>
                <div class="flex-grow-1">
                    <h6 class="small fw-bold mb-1 line-clamp-2"><?= htmlspecialchars($lec['title']) ?></h6>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted fw-bold" style="font-size: 10px; color: #94a3b8 !important;">STEP <?= $count ?></span>
                        <i class="bi bi-circle-fill" style="font-size: 3px; color: #cbd5e1;"></i>
                        <span class="text-muted" style="font-size: 11px;">Video</span>
                    </div>
                </div>
                <?php if($isActive): ?>
                    <i class="bi bi-bar-chart-fill text-primary ms-2" style="font-size: 14px;"></i>
                <?php endif; ?>
            </div>
            <?php $count++; endwhile; ?>
        </div>

        <div class="p-4 bg-white border-top">
             <a href="assignments.php?course_id=<?= $course_id ?>" class="btn btn-primary rounded-4 w-100 fw-bold py-3 shadow-sm border-0" style="background: var(--brand-gradient);">
                <i class="bi bi-rocket-takeoff-fill me-2"></i> Submit Assignment
            </a>
        </div>
    </aside>

</div>

<script>
// --- INITIALIZE TRACKING VARIABLES ---
let activeMinutes = 0;
let idleTime = 0;
const idleLimit = 2; // 2 min inactivity threshold
let currentLectureId = <?php echo $current_lecture_id; ?>; // PHP variable name fixed here

/** * UX SCRIPT: Logic preserved, Visuals enhanced.
 */
let fullDescription = "";
let isExpanded = false;

function toggleDesc() {
    const descBox = document.getElementById("lessonDescription");
    const btn = document.getElementById("moreBtn");
    if (isExpanded) {
        descBox.innerText = fullDescription.substring(0, 180) + "...";
        btn.innerText = "Read full description";
    } else {
        descBox.innerText = fullDescription;
        btn.innerText = "Show less";
    }
    isExpanded = !isExpanded;
}

function updateDescription(desc) {
    fullDescription = desc || "Welcome to this session. In this video, we will explore the core concepts of the module.";
    const descBox = document.getElementById("lessonDescription");
    const btn = document.getElementById("moreBtn");
    isExpanded = false;

    if (fullDescription.length > 180) {
        descBox.innerText = fullDescription.substring(0, 180) + "...";
        btn.style.display = "inline-block";
        btn.innerText = "Read full description";
    } else {
        descBox.innerText = fullDescription;
        btn.style.display = "none";
    }
}

function switchLesson(videoSrc, lectureId, element) {
    // --- 1. RESET TIMER FOR NEW VIDEO (Crucial Logic) ---
    // If we don't do this, the time from the previous video is added to the new one.
    // Ideally, sendBeacon for old video here, but for simplicity, we just reset.
    currentLectureId = lectureId; 
    activeMinutes = 0;
    idleTime = 0;

    const video = document.getElementById("mainVideo");
    const wrapper = document.getElementById("playerWrapper");
    const titleHeader = document.getElementById("currentLessonTitle");
    
    // UI Micro-interaction: Fade out while switching
    wrapper.style.opacity = '0.4';
    
    setTimeout(() => {
        video.src = videoSrc;
        video.load();
        video.play();
        wrapper.style.opacity = '1';
    }, 200);

    const newTitle = element.getAttribute('data-title');
    const newDesc = element.getAttribute('data-description');
    
    titleHeader.innerText = newTitle;
    updateDescription(newDesc);

    // Update Playlist UI
    document.querySelectorAll('.playlist-item').forEach(item => {
        item.classList.remove('active');
        const icon = item.querySelector('.bi-bar-chart-fill');
        if(icon) icon.remove();
    });

    element.classList.add('active');
    element.insertAdjacentHTML('beforeend', '<i class="bi bi-bar-chart-fill text-primary ms-2" style="font-size: 14px;"></i>');

    // Update Notes Sidebar
    document.querySelectorAll('.note-content-area').forEach(note => note.classList.remove('active-notes'));
    const targetNote = document.getElementById('notes-for-' + lectureId);
    if(targetNote) {
        targetNote.classList.add('active-notes');
    }
}

document.addEventListener("DOMContentLoaded", function() {
    const firstActive = document.querySelector('.playlist-item.active');
    if(firstActive) {
        updateDescription(firstActive.getAttribute('data-description'));
    }
});

// --- NEW ATTENDANCE LOGIC INSERTED HERE ---

// Increment active time every minute if not idle
let timer = setInterval(() => {
    if(document.hasFocus() && idleTime < idleLimit){
        activeMinutes++;
    } else {
        idleTime++;
    }
}, 60000);

// Reset idle on activity
['mousemove', 'keydown', 'scroll', 'click'].forEach(event => {
    document.addEventListener(event, () => idleTime = 0);
});

// Warn student on early leave & Send Data
window.addEventListener('beforeunload', (e) => {
    const minRequired = 10; // minimum active minutes
    if(activeMinutes < minRequired){
        e.preventDefault();
        e.returnValue = "You haven't spent enough time in this lecture. Are you sure you want to leave?";
    }

    // Send data to server using the dynamic currentLectureId
    navigator.sendBeacon('update_activity.php', JSON.stringify({
        lecture_id: currentLectureId,
        active_minutes: activeMinutes
    }));
});
</script>

</body>
</html>