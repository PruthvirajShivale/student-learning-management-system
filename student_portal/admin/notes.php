<?php
/* --- BACKEND LOGIC --- */
ini_set('upload_max_filesize', '512M');
ini_set('post_max_size', '520M');
ini_set('memory_limit', '1024M');
ini_set('max_execution_time', '600');

session_start();
require "../config.php";

if (!isset($_SESSION['admin_email'])) { header("Location: ../login.php"); exit(); }
if (!isset($_GET['course_id'], $_GET['lecture_id'])) { die("Invalid request"); }

$course_id  = intval($_GET['course_id']);
$lecture_id = intval($_GET['lecture_id']);
$upload_dir = "../uploads/course_notes/";
if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

// Feature: Toggle Pin
if (isset($_GET['toggle_pin'])) {
    $pin_id = intval($_GET['toggle_pin']);
    mysqli_query($conn, "UPDATE course_notes SET is_pinned = 1 - is_pinned WHERE id='$pin_id'");
    header("Location: notes.php?course_id=$course_id&lecture_id=$lecture_id");
    exit();
}

// Logic: Delete Note
if (isset($_GET['delete_note_id'])) {
    $delete_id = intval($_GET['delete_note_id']);
    $file_q = mysqli_query($conn, "SELECT file_path FROM course_notes WHERE id='$delete_id'");
    $file_data = mysqli_fetch_assoc($file_q);
    if ($file_data && !empty($file_data['file_path'])) { @unlink($upload_dir . $file_data['file_path']); }
    mysqli_query($conn, "DELETE FROM course_notes WHERE id='$delete_id'");
    header("Location: notes.php?course_id=$course_id&lecture_id=$lecture_id");
    exit();
}

$lec_q = mysqli_query($conn, "SELECT cl.*, c.course_name FROM course_lectures cl JOIN courses c ON cl.course_id = c.id WHERE cl.id='$lecture_id' AND cl.course_id='$course_id'");
$lecture = mysqli_fetch_assoc($lec_q);

// Logic: Handle Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $note = trim($_POST['note'] ?? '');
    $file_name = null; $file_type = null;
    if (isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
        $allowed = ['jpg','jpeg','png','gif','webp','pdf', 'zip', 'docx'];
        $original_name = $_FILES['file']['name'];
        $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $file_name = time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $original_name);
            $file_type = $ext;
            move_uploaded_file($_FILES['file']['tmp_name'], $upload_dir . $file_name);
        }
    }
    if ($note !== '' || $file_name !== null) {
        $stmt = $conn->prepare("INSERT INTO course_notes (course_id, lecture_id, note, file_path, file_type, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("iisss", $course_id, $lecture_id, $note, $file_name, $file_type);
        $stmt->execute(); $stmt->close();
    }
    header("Location: notes.php?course_id=$course_id&lecture_id=$lecture_id");
    exit();
}

// Sorted by Pinned First, then Date ASC
$notes_result = mysqli_query($conn, "SELECT * FROM course_notes WHERE lecture_id='$lecture_id' ORDER BY is_pinned DESC, created_at ASC");
$notes = mysqli_fetch_all($notes_result, MYSQLI_ASSOC);
$total_notes = count($notes);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workspace | <?= htmlspecialchars($lecture['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

    <style>
        :root {
            --brand-primary: #6366f1;
            --brand-dark: #0f172a;
            --brand-surface: #ffffff;
            --brand-bg: #f8fafc;
            --text-main: #334155;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--brand-bg);
            color: var(--text-main);
            overflow: hidden;
        }

        /* --- MODERN SIDEBAR --- */
        .sidebar {
            width: 320px;
            background: var(--brand-dark);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            padding: 2rem;
            color: #fff;
            z-index: 1000;
            display: flex;
            flex-direction: column;
        }

        .course-tag {
            background: rgba(99, 102, 241, 0.2);
            color: #818cf8;
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .index-list {
            margin-top: 2rem;
            flex-grow: 1;
            overflow-y: auto;
        }

        .index-item {
            padding: 10px;
            border-radius: 8px;
            font-size: 0.85rem;
            color: #94a3b8;
            cursor: pointer;
            transition: 0.2s;
            border-left: 2px solid transparent;
        }
        .index-item:hover { background: rgba(255,255,255,0.05); color: #fff; }
        .index-item.active { border-left-color: var(--brand-primary); background: rgba(99, 102, 241, 0.1); color: #fff; }

        /* --- MAIN VIEW --- */
        .main-view {
            margin-left: 320px;
            height: 100vh;
            overflow-y: auto;
            scroll-behavior: smooth;
        }

        /* --- FLOATING EDITOR --- */
        .editor-wrapper {
            position: sticky;
            top: 0;
            z-index: 900;
            padding: 2rem 4rem;
            background: rgba(248, 250, 252, 0.9);
            backdrop-filter: blur(10px);
        }

        .modern-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
        }

        #editor {
            height: 150px;
            border: none !important;
            font-size: 1.1rem;
        }

        .ql-toolbar.ql-snow { border: none !important; border-bottom: 1px solid #f1f5f9 !important; }

        /* --- FEED --- */
        .notes-feed {
            max-width: 850px;
            margin: 0 auto;
            padding: 0 4rem 5rem;
        }

        .note-card {
            background: #fff;
            border-radius: 20px;
            margin-bottom: 2rem;
            border: 1px solid #e2e8f0;
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
        }

        .note-card:hover { transform: translateY(-2px); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.05); }

        .note-card.pinned { border: 2px solid #6366f1; }
        .pin-badge {
            position: absolute;
            right: 20px;
            top: -12px;
            background: #6366f1;
            color: #fff;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 700;
        }

        .note-content { padding: 25px; line-height: 1.8; color: #334155; }

        .note-meta {
            padding: 12px 25px;
            background: #f8fafc;
            border-bottom: 1px solid #f1f5f9;
            border-top-left-radius: 20px;
            border-top-right-radius: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.8rem;
            color: #64748b;
        }

        .btn-action {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: 0.2s;
            color: #94a3b8;
            text-decoration: none;
        }
        .btn-action:hover { background: #f1f5f9; color: var(--brand-primary); }
        .btn-delete:hover { background: #fee2e2; color: #ef4444; }

        .attachment-box {
            background: #f1f5f9;
            border-radius: 12px;
            padding: 15px;
            margin-top: 15px;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: #1e293b;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .img-preview {
            width: 100%;
            border-radius: 12px;
            margin-top: 15px;
            border: 1px solid #e2e8f0;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="mb-4">
        <span class="course-tag"><?= htmlspecialchars($lecture['course_name']) ?></span>
        <h2 class="h4 mt-3 fw-bold text-white"><?= htmlspecialchars($lecture['title']) ?></h2>
    </div>

    <div class="index-list">
        <p class="text-uppercase small fw-bold text-muted mb-3" style="letter-spacing: 1px;">Resource Index</p>
        <?php if ($total_notes > 0): ?>
            <?php foreach($notes as $idx => $n): ?>
                <div class="index-item">
                    <span class="me-2 text-primary">#<?= $idx + 1 ?></span>
                    <?= !empty($n['note']) ? strip_tags(substr($n['note'], 0, 30)).'...' : 'Attachment' ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="small text-muted">No entries yet.</p>
        <?php endif; ?>
    </div>

    <div class="mt-auto">
        <a href="course_materials.php?course_id=<?= $course_id ?>" class="text-decoration-none text-muted small d-flex align-items-center gap-2">
            <i class="bi bi-arrow-left"></i> Exit Workspace
        </a>
    </div>
</aside>

<main class="main-view">
    
    <div class="editor-wrapper">
        <div class="modern-card p-2">
            <form method="POST" enctype="multipart/form-data" id="noteForm">
                <div id="toolbar">
                    <span class="ql-formats">
                        <button class="ql-bold"></button>
                        <button class="ql-italic"></button>
                        <button class="ql-list" value="bullet"></button>
                    </span>
                    <span class="ql-formats">
                        <select class="ql-color"></select>
                    </span>
                </div>
                <div id="editor"></div>
                <input type="hidden" name="note" id="noteInput">
                
                <div class="d-flex justify-content-between align-items-center p-3 border-top">
                    <label class="btn btn-light btn-sm text-muted fw-semibold mb-0">
                        <i class="bi bi-paperclip me-1"></i> <span id="fn">Attach Resource</span>
                        <input type="file" name="file" class="d-none" onchange="document.getElementById('fn').innerText = this.files[0].name">
                    </label>
                    <button type="submit" class="btn btn-primary px-4 fw-bold">Save Note</button>
                </div>
            </form>
        </div>
    </div>

    <div class="notes-feed">
        <?php if ($total_notes > 0): ?>
            <?php foreach($notes as $n): ?>
                <div class="note-card <?= $n['is_pinned'] ? 'pinned' : '' ?>">
                    <?php if($n['is_pinned']): ?>
                        <span class="pin-badge"><i class="bi bi-pin-angle-fill me-1"></i> PINNED</span>
                    <?php endif; ?>

                    <div class="note-meta">
                        <div>
                            <i class="bi bi-calendar3 me-2"></i> 
                            <?= date("M d, Y • h:i A", strtotime($n['created_at'])) ?>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="?course_id=<?= $course_id ?>&lecture_id=<?= $lecture_id ?>&toggle_pin=<?= $n['id'] ?>" class="btn-action" title="Pin Note">
                                <i class="bi bi-pin-angle"></i>
                            </a>
                            <a href="?course_id=<?= $course_id ?>&lecture_id=<?= $lecture_id ?>&delete_note_id=<?= $n['id'] ?>" 
                               class="btn-action btn-delete" onclick="return confirm('Archive this note?')" title="Delete">
                                <i class="bi bi-trash3"></i>
                            </a>
                        </div>
                    </div>

                    <div class="note-content">
                        <?php if (!empty($n['note'])): ?>
                            <div class="mb-0 text-article"><?= $n['note'] ?></div>
                        <?php endif; ?>

                        <?php if (!empty($n['file_path'])): ?>
                            <?php 
                                $ext = strtolower($n['file_type']);
                                $file_url = $upload_dir . $n['file_path'];
                                if (in_array($ext, ['jpg','jpeg','png','gif','webp'])): 
                            ?>
                                <a href="<?= $file_url ?>" target="_blank">
                                    <img src="<?= $file_url ?>" class="img-preview">
                                </a>
                            <?php else: ?>
                                <a href="<?= $file_url ?>" target="_blank" class="attachment-box">
                                    <i class="bi bi-file-earmark-arrow-down-fill text-primary fs-4"></i>
                                    <div>
                                        <div class="mb-0">Lecture Reference .<?= strtoupper($ext) ?></div>
                                        <div class="small text-muted fw-normal">Click to download resource</div>
                                    </div>
                                    <i class="bi bi-chevron-right ms-auto"></i>
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center py-5">
                <div class="display-1 text-muted opacity-25"><i class="bi bi-journal-plus"></i></div>
                <h4 class="fw-bold text-muted mt-3">The Workspace is Quiet</h4>
                <p class="text-muted">Break down the lecture into manageable notes or upload slides.</p>
            </div>
        <?php endif; ?>
    </div>

</main>

<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script>
    var quill = new Quill('#editor', {
        modules: { toolbar: '#toolbar' },
        placeholder: 'What did you learn in this lecture?',
        theme: 'snow'
    });

    const form = document.getElementById('noteForm');
    form.onsubmit = function() {
        const noteInput = document.getElementById('noteInput');
        // Handle empty Quill editor content properly
        if (quill.getText().trim().length === 0 && !quill.root.innerHTML.includes('<img')) {
            noteInput.value = '';
        } else {
            noteInput.value = quill.root.innerHTML;
        }
    };
</script>

</body>
</html>