<?php
session_start();
require "../config.php";

if (!isset($_SESSION['admin_email'])) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['course_id'])) {
    die("Course not specified.");
}
$course_id = intval($_GET['course_id']);

$course_q = mysqli_query($conn, "SELECT * FROM courses WHERE id='$course_id'");
if (mysqli_num_rows($course_q) == 0) {
    die("Course not found.");
}
$course = mysqli_fetch_assoc($course_q);

// [BACKEND LOGIC REMAINS 100% UNCHANGED]
$lectures = mysqli_query($conn, "SELECT * FROM course_lectures WHERE course_id='$course_id' ORDER BY lecture_date ASC, created_at ASC");

$upload_dir = "../uploads/course_materials/";
if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $lecture_date = !empty($_POST['lecture_date']) ? $_POST['lecture_date'] : null;
    $lecture_time = !empty($_POST['lecture_time']) ? $_POST['lecture_time'] : null;

    $file_name = null;
    $file_type = null;

    if ($action === 'add') {
        if (!empty($_FILES['file']['name'])) {
            $file_name = time() . "_" . basename($_FILES['file']['name']);
            if (!move_uploaded_file($_FILES['file']['tmp_name'], $upload_dir . $file_name)) {
                die("File upload failed.");
            }
            $file_type = pathinfo($file_name, PATHINFO_EXTENSION);
        }

        $sql = "INSERT INTO course_lectures 
            (course_id, title, description, file_path, file_type, lecture_date, lecture_time)
            VALUES ('$course_id','$title','$description','$file_name','$file_type','$lecture_date','$lecture_time')";
        mysqli_query($conn, $sql);
        header("Location: course_materials.php?course_id=$course_id");
        exit();
    }

    if ($action === 'edit') {
        $lecture_id = intval($_POST['lecture_id']);
        $old_file = $_POST['old_file_path'];

        if (!empty($_FILES['file']['name'])) {
            $file_name = time() . "_" . basename($_FILES['file']['name']);
            if (!move_uploaded_file($_FILES['file']['tmp_name'], $upload_dir . $file_name)) {
                die("File upload failed.");
            }
            $file_type = pathinfo($file_name, PATHINFO_EXTENSION);

            if ($old_file && file_exists($upload_dir . $old_file)) {
                unlink($upload_dir . $old_file);
            }
        } else {
            $file_name = $old_file;
            if ($old_file) {
                $file_type = pathinfo($old_file, PATHINFO_EXTENSION);
            }
        }

        $sql = "UPDATE course_lectures SET 
                    title='$title',
                    description='$description',
                    file_path='$file_name',
                    file_type='$file_type',
                    lecture_date='$lecture_date',
                    lecture_time='$lecture_time'
                WHERE id='$lecture_id'";
        mysqli_query($conn, $sql);
        header("Location: course_materials.php?course_id=$course_id");
        exit();
    }
}

if (isset($_GET['delete_id'])) {
    $del_id = intval($_GET['delete_id']);
    $old_q = mysqli_query($conn, "SELECT file_path FROM course_lectures WHERE id='$del_id'");
    $old = mysqli_fetch_assoc($old_q);
    if (!empty($old['file_path']) && file_exists($upload_dir.$old['file_path'])) unlink($upload_dir.$old['file_path']);

    mysqli_query($conn, "DELETE FROM course_lectures WHERE id='$del_id'");
    header("Location: course_materials.php?course_id=$course_id");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Materials - <?= htmlspecialchars($course['course_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <style>
        :root {
            --brand-primary: #4f46e5;
            --brand-secondary: #6366f1;
            --bg-body: #f1f5f9;
            --text-dark: #1e293b;
            --card-radius: 20px;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-dark);
        }

        .top-nav {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 1.2rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .hero-section {
            background: linear-gradient(135deg, #4f46e5 0%, #312e81 100%);
            border-radius: var(--card-radius);
            padding: 3rem;
            color: white;
            margin-top: 2rem;
            margin-bottom: 3rem;
            box-shadow: 0 20px 25px -5px rgba(79, 70, 229, 0.2);
            position: relative;
            overflow: hidden;
        }

        .lecture-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
            padding-bottom: 5rem;
        }

        .lecture-card {
            background: white;
            border-radius: var(--card-radius);
            border: 1px solid rgba(0,0,0,0.05);
            padding: 1.25rem;
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            display: flex;
            flex-direction: column;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
        }

        .lecture-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
        }

        /* UPDATED: VIDEO PREVIEW FITS PERFECTLY */
        .media-preview {
            width: 100%;
            height: 200px;
            background: #e2e8f0; 
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 1.25rem;
            position: relative;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .media-preview video { 
            width: 100%; 
            height: 100%; 
            object-fit: cover; /* This makes the video/thumbnail fill the area completely */
            display: block;
        }

        .file-icon-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: var(--brand-primary);
        }
        .file-icon-box i { font-size: 3.5rem; }

        .lec-label {
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--brand-primary);
            margin-bottom: 0.5rem;
            display: block;
        }

        .meta-badge {
            background: #f8fafc;
            color: #475569;
            padding: 6px 12px;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 600;
            border: 1px solid #e2e8f0;
        }

        .desc-text {
            color: #64748b;
            font-size: 0.9rem;
            margin-top: 1rem;
            line-height: 1.6;
        }

        .desc-collapsed {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .read-more-btn {
            color: var(--brand-primary);
            cursor: pointer;
            font-weight: 700;
            font-size: 0.8rem;
            margin-top: 5px;
            display: inline-block;
        }

        .card-actions {
            margin-top: auto;
            padding-top: 1.5rem;
            display: flex;
            gap: 10px;
        }

        .btn-modern {
            border-radius: 12px;
            font-weight: 600;
            padding: 10px 18px;
            transition: 0.3s;
            font-size: 0.9rem;
        }

        .btn-primary-modern {
            background: var(--brand-primary);
            color: white;
            border: none;
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.3);
        }
        
        .btn-primary-modern:hover {
            background: #4338ca;
            transform: translateY(-2px);
            box-shadow: 0 15px 20px -5px rgba(79, 70, 229, 0.4);
            color: white;
        }

        .btn-action-outline {
            background: white;
            border: 1px solid #e2e8f0;
            color: #475569;
        }
        .btn-action-outline:hover {
            background: #f8fafc;
            border-color: var(--brand-primary);
            color: var(--brand-primary);
        }

        .modal-content { border-radius: 24px; border: none; }
        .form-control { border-radius: 10px; padding: 12px; border: 1px solid #e2e8f0; }
        .form-control:focus { box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1); border-color: var(--brand-primary); }
    </style>
</head>
<body>

<nav class="top-nav">
    <div class="container d-flex justify-content-between align-items-center">
        <a href="manage_courses.php" class="text-decoration-none text-dark fw-bold d-flex align-items-center">
            <i class="bi bi-chevron-left me-2"></i> Dashboard
        </a>
        <div class="fw-bold text-primary"><i class="bi bi-mortarboard-fill me-2"></i>Admin Console</div>
    </div>
</nav>

<div class="container">
    <div class="hero-section">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <span class="badge bg-white text-primary mb-3 px-3 py-2">COURSE ID: #<?= $course_id ?></span>
                <h1 class="display-5 fw-bold mb-3"><?= htmlspecialchars($course['course_name']) ?></h1>
                <p class="lead opacity-75 mb-0">Organize and manage lectures, video content, and resources for your students.</p>
            </div>
            <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
                <button class="btn btn-modern btn-primary-modern px-4 py-3" data-bs-toggle="modal" data-bs-target="#addLectureModal">
                    <i class="bi bi-plus-circle-fill me-2"></i> Add New Material
                </button>
            </div>
        </div>
    </div>

    <div class="lecture-grid">
        <?php 
        $lec_count = 1; 
        while($lec = mysqli_fetch_assoc($lectures)): 
        ?>
            <div class="lecture-card">
                <div class="media-preview">
                    <?php if($lec['file_path']): ?>
                        <?php 
                        $ext = strtolower(pathinfo($lec['file_path'], PATHINFO_EXTENSION));
                        if(in_array($ext, ['mp4','webm','ogg'])): ?>
                            <video controls preload="metadata">
                                <source src="<?= $upload_dir.$lec['file_path'] ?>#t=0.1" type="video/<?= $ext ?>">
                                Your browser does not support the video tag.
                            </video>
                        <?php else: ?>
                            <div class="file-icon-box">
                                <i class="bi bi-file-earmark-pdf-fill"></i>
                                <div class="mt-2 small fw-bold opacity-50"><?= strtoupper($ext) ?> DOCUMENT</div>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="file-icon-box opacity-25">
                            <i class="bi bi-collection-play-fill"></i>
                            <div class="small fw-bold">No Media</div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="px-1">
                    <span class="lec-label">LECTURE <?= $lec_count++ ?></span>
                    <h5 class="fw-bold mb-3 text-dark"><?= htmlspecialchars($lec['title']) ?></h5>
                    
                    <div class="d-flex gap-2 mb-3">
                        <span class="meta-badge"><i class="bi bi-calendar-event me-2"></i><?= $lec['lecture_date'] ?: 'No Date' ?></span>
                        <span class="meta-badge"><i class="bi bi-clock me-2"></i><?= $lec['lecture_time'] ?: 'No Time' ?></span>
                    </div>
                    
                    <div id="desc-<?= $lec['id'] ?>" class="desc-text desc-collapsed">
                        <?= nl2br(htmlspecialchars($lec['description'])) ?>
                    </div>
                    <?php if (strlen($lec['description']) > 100): ?>
                        <span class="read-more-btn" onclick="toggleDesc(<?= $lec['id'] ?>)" id="btn-<?= $lec['id'] ?>">Show More <i class="bi bi-chevron-down ms-1"></i></span>
                    <?php endif; ?>
                </div>

                <div class="card-actions">
                    <a href="notes.php?course_id=<?= $course_id ?>&lecture_id=<?= $lec['id'] ?>" class="btn btn-modern btn-primary-modern flex-grow-1">
                        <i class="bi bi-journal-text me-2"></i> Notes
                    </a>
                    
                    <button class="btn btn-modern btn-action-outline"
                        data-bs-toggle="modal"
                        data-bs-target="#editLectureModal"
                        onclick='editLecture(<?= json_encode([
                            "id" => $lec["id"],
                            "title" => $lec["title"],
                            "description" => $lec["description"],
                            "date" => $lec["lecture_date"],
                            "time" => $lec["lecture_time"],
                            "file" => $lec["file_path"] ? $upload_dir.$lec["file_path"] : ""
                        ]) ?>)'>
                        <i class="bi bi-pencil"></i>
                    </button>
                    
                    <a href="?course_id=<?= $course_id ?>&delete_id=<?= $lec['id'] ?>" 
                       onclick="return confirm('Permanent delete this material?')"
                       class="btn btn-modern btn-action-outline text-danger border-danger-subtle">
                        <i class="bi bi-trash3"></i>
                    </a>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<div class="modal fade" id="addLectureModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add">
        <div class="modal-header border-0 pb-0">
            <h4 class="fw-bold pt-3 px-3">Add New Material</h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
            <div class="row g-4">
                <div class="col-12">
                    <label class="form-label fw-semibold">Material Title</label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. Introduction to Physics" required>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Full Description</label>
                    <textarea name="description" class="form-control" rows="4" placeholder="Enter details about this lecture..." required></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Scheduled Date</label>
                    <input type="date" name="lecture_date" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Time Slot</label>
                    <input type="time" name="lecture_time" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Upload Media (Video or PDF)</label>
                    <input type="file" name="file" class="form-control">
                </div>
            </div>
        </div>
        <div class="modal-footer border-0 p-4">
            <button type="button" class="btn btn-modern px-4" data-bs-dismiss="modal">Cancel</button>
            <button class="btn btn-modern btn-primary-modern px-5">Publish Material</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="editLectureModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="lecture_id" id="edit_lecture_id">
        <input type="hidden" id="edit_file_path" name="old_file_path">
        <div class="modal-header border-0 pb-0">
            <h4 class="fw-bold pt-3 px-3">Update Material</h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
            <div class="row g-4">
                <div class="col-12">
                    <label class="form-label fw-semibold">Title</label>
                    <input type="text" name="title" id="edit_title" class="form-control" required>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea name="description" id="edit_description" class="form-control" rows="4" required></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Date</label>
                    <input type="date" name="lecture_date" id="edit_date" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Time</label>
                    <input type="time" name="lecture_time" id="edit_time" class="form-control">
                </div>
                <div class="col-12">
                    <div id="existing_file_container" class="mb-2"></div>
                    <label class="form-label fw-semibold">Replace File (Optional)</label>
                    <input type="file" name="file" class="form-control">
                </div>
            </div>
        </div>
        <div class="modal-footer border-0 p-4">
            <button type="button" class="btn btn-modern px-4" data-bs-dismiss="modal">Close</button>
            <button class="btn btn-modern btn-primary-modern px-5">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function toggleDesc(id) {
    const desc = document.getElementById('desc-' + id);
    const btn = document.getElementById('btn-' + id);
    if (desc.classList.contains('desc-collapsed')) {
        desc.classList.remove('desc-collapsed');
        btn.innerHTML = 'Show Less <i class="bi bi-chevron-up ms-1"></i>';
    } else {
        desc.classList.add('desc-collapsed');
        btn.innerHTML = 'Show More <i class="bi bi-chevron-down ms-1"></i>';
    }
}

function editLecture(lec){
    document.getElementById("edit_lecture_id").value = lec.id;
    document.getElementById("edit_title").value = lec.title;
    document.getElementById("edit_description").value = lec.description;
    document.getElementById("edit_date").value = lec.date;
    document.getElementById("edit_time").value = lec.time;
    const container = document.getElementById("existing_file_container");
    container.innerHTML = lec.file ? `<div class='alert alert-info py-2 small'><i class='bi bi-file-check me-2'></i>Current: ${lec.file.split('/').pop()}</div>` : "";
    document.getElementById("edit_file_path").value = lec.file ? lec.file.replace('../uploads/course_materials/', '') : '';
}
</script>

</body>
</html>