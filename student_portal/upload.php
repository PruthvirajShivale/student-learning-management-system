<?php 
session_start(); 
require 'config.php';

if (!isset($_SESSION['student_id'])) { 
    header('Location: login.php'); 
    exit; 
}

$student_id = $_SESSION['student_id'];

/*
|--------------------------------------------------------------------------
| DELETE FILE (Logic Unchanged)
|--------------------------------------------------------------------------
*/
if(isset($_GET['delete'])){
    $delete_id = intval($_GET['delete']);
    $stmt = $conn->prepare("SELECT file_path FROM files WHERE id = ? AND student_id = ?");
    $stmt->bind_param("ii", $delete_id, $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        $file = $result->fetch_assoc();
        if(file_exists($file['file_path'])){
            unlink($file['file_path']);
        }
        $delete_stmt = $conn->prepare("DELETE FROM files WHERE id = ? AND student_id = ?");
        $delete_stmt->bind_param("ii", $delete_id, $student_id);
        $delete_stmt->execute();
        $delete_stmt->close();
    }
    $stmt->close();
    header("Location: upload.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| FETCH FILES (Logic Unchanged)
|--------------------------------------------------------------------------
*/
$query = "SELECT * FROM files WHERE student_id = '$student_id' ORDER BY id DESC";
$files_query = mysqli_query($conn, $query);
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Media Vault | My Drive</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --drive-bg: #f7f9fc;
            --sidebar-width: 280px;
            --google-blue: #1a73e8;
            --google-red: #ea4335;
            --border-color: #e0e0e0;
        }

        body {
            background-color: var(--drive-bg);
            font-family: 'Roboto', sans-serif;
            color: #3c4043;
            overflow-x: hidden;
        }

        /* Top Navbar */
        .drive-nav {
            background: white;
            padding: 8px 20px;
            border-bottom: 1px solid var(--border-color);
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1030;
        }

        .search-container {
            max-width: 720px;
            width: 100%;
            background: #f1f3f4;
            border-radius: 8px;
            padding: 8px 15px;
            display: flex;
            align-items: center;
        }

        .search-container input {
            background: transparent;
            border: none;
            width: 100%;
            padding-left: 10px;
        }

        .search-container input:focus { outline: none; }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            top: 64px;
            left: 0;
            padding: 16px;
            background: var(--drive-bg);
        }

        .btn-new {
            background: white;
            border-radius: 24px;
            padding: 12px 24px;
            box-shadow: 0 1px 2px 0 rgba(60,64,67,0.3), 0 1px 3px 1px rgba(60,64,67,0.15);
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            cursor: pointer;
            transition: box-shadow 0.2s;
        }

        .btn-new:hover { box-shadow: 0 4px 8px 0 rgba(60,64,67,0.3); }

        .nav-link-custom {
            display: flex;
            align-items: center;
            padding: 10px 24px;
            border-radius: 0 20px 20px 0;
            margin-left: -16px;
            color: #3c4043;
            text-decoration: none;
            gap: 15px;
        }

        .nav-link-custom.active {
            background-color: #e8f0fe;
            color: var(--google-blue);
            font-weight: 500;
        }

        /* Main Content */
        .main-wrapper {
            margin-left: var(--sidebar-width);
            margin-top: 64px;
            padding: 24px;
        }

        .content-card {
            background: white;
            border-radius: 16px;
            min-height: 80vh;
            border: 1px solid var(--border-color);
            padding: 20px;
        }

        /* File Grid */
        .file-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .file-card {
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
            transition: background 0.2s;
        }

        .file-card:hover {
            background: #f1f3f4;
        }

        .file-preview {
            height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            font-size: 3rem;
            border-bottom: 1px solid var(--border-color);
        }

        .file-details {
            padding: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .file-title-text {
            font-size: 0.875rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            flex-grow: 1;
        }

        /* Icon Colors */
        .text-notes { color: #4285f4; }
        .text-marksheet { color: #34a853; }
        .text-prn { color: #fbbc05; }
        .text-image { color: #ea4335; }
        .text-video { color: #6f42c1; }

        @media (max-width: 992px) {
            .sidebar { display: none; }
            .main-wrapper { margin-left: 0; }
        }
    </style>
</head>
<body>

<!-- Top Navbar -->
<nav class="drive-nav d-flex align-items-center">
    <div class="d-flex align-items-center" style="width: var(--sidebar-width)">
        <i class="bi bi-hdd-stack fs-3 me-2 text-primary"></i>
        <span class="fs-5 fw-medium">MediaVault</span>
    </div>
    <div class="search-container mx-4 d-none d-md-flex">
        <i class="bi bi-search text-muted"></i>
        <input type="text" placeholder="Search files, videos, documents...">
    </div>
    <div class="ms-auto d-flex align-items-center gap-3">
        <i class="bi bi-question-circle fs-5"></i>
        <i class="bi bi-gear fs-5"></i>
        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:32px; height:32px;">
            <span class="small">S</span>
        </div>
    </div>
</nav>

<!-- Sidebar -->
<aside class="sidebar">
    <div class="btn-new" data-bs-toggle="modal" data-bs-target="#uploadModal">
        <svg width="24" height="24" viewBox="0 0 36 36"><path fill="#34A853" d="M16 16v14h4V20z"/><path fill="#4285F4" d="M30 16H20l-4 4h14z"/><path fill="#FBBC05" d="M6 16v4h10l4-4z"/><path fill="#EA4335" d="M20 16V6h-4v14z"/><path fill="none" d="M0 0h36v36H0z"/></svg>
        <span>New Upload</span>
    </div>

    <a href="#" class="nav-link-custom active">
        <i class="bi bi-folder2"></i> My Drive
    </a>
    <a href="#" class="nav-link-custom">
        <i class="bi bi-people"></i> Shared Items
    </a>
    <a href="#" class="nav-link-custom">
        <i class="bi bi-camera-video"></i> All Videos
    </a>
    <a href="#" class="nav-link-custom">
        <i class="bi bi-star"></i> Starred
    </a>
    <a href="#" class="nav-link-custom mt-4 text-danger">
        <i class="bi bi-trash"></i> Bin
    </a>
</aside>

<!-- Main Content -->
<main class="main-wrapper">
    <div class="content-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0">Storage Explorer</h5>
            <div class="btn-group">
                <button class="btn btn-sm btn-outline-secondary active"><i class="bi bi-grid-3x3-gap"></i></button>
                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-list-task"></i></button>
            </div>
        </div>

        <?php if(mysqli_num_rows($files_query) > 0){ ?>
            <div class="file-grid">
                <?php while($file = mysqli_fetch_assoc($files_query)){ 
                    $type = $file['file_type'];
                    $path = $file['file_path'];
                    $icon = "bi-file-earmark";
                    $colorClass = "text-muted";
                    $isVideo = false;

                    // Mapping Categories & Icons
                    if($type == 'notes') { $icon = "bi-journal-text"; $colorClass = "text-notes"; }
                    elseif($type == 'marksheet') { $icon = "bi-mortarboard-fill"; $colorClass = "text-marksheet"; }
                    elseif($type == 'prn') { $icon = "bi-person-badge-fill"; $colorClass = "text-prn"; }
                    elseif($type == 'image') { $icon = "bi-image-fill"; $colorClass = "text-image"; }
                    elseif($type == 'video' || $type == 'tutorial' || $type == 'lecture_recording') { 
                        $icon = "bi-play-circle-fill"; 
                        $colorClass = "text-video"; 
                        $isVideo = true;
                    }
                    else { $icon = "bi-file-earmark-zip-fill"; }
                ?>
                    <div class="file-card">
                        <div class="file-preview">
                            <i class="bi <?php echo $icon . ' ' . $colorClass; ?>"></i>
                        </div>
                        <div class="file-details">
                            <i class="bi <?php echo $icon . ' ' . $colorClass; ?> fs-5"></i>
                            <div class="file-title-text" title="<?php echo htmlspecialchars($file['file_name']); ?>">
                                <?php echo htmlspecialchars($file['file_name']); ?>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-link btn-sm text-muted p-0" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <?php if($isVideo): ?>
                                        <li><a class="dropdown-item" href="#" onclick="playVideo('<?php echo $path; ?>', '<?php echo htmlspecialchars($file['file_name']); ?>')"><i class="bi bi-play-btn me-2"></i> Play Video</a></li>
                                    <?php else: ?>
                                        <li><a class="dropdown-item" href="<?php echo $path; ?>" target="_blank"><i class="bi bi-eye me-2"></i> Preview</a></li>
                                    <?php endif; ?>
                                    <li><a class="dropdown-item" href="<?php echo $path; ?>" download><i class="bi bi-download me-2"></i> Download</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="?delete=<?php echo $file['id']; ?>" onclick="return confirm('Permanently remove this file?')"><i class="bi bi-trash me-2"></i> Remove</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        <?php } else { ?>
            <div class="text-center py-5 mt-5">
                <img src="https://ssl.gstatic.com/docs/doclist/images/empty_state_my_drive_v2.svg" alt="Empty" style="width: 250px;">
                <h5 class="mt-4">Start your vault</h5>
                <p class="text-muted">Upload notes, videos, and documents with no size limits.</p>
            </div>
        <?php } ?>
    </div>
</main>

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 28px; padding: 10px;">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">Add to Cloud</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="upload_process.php" method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">CATEGORY</label>
                        <select name="file_type" class="form-select form-select-lg" required>
                            <option value="notes">Lecture Notes</option>
                            <option value="video">Personal Video</option>
                            <option value="lecture_recording">Recorded Lecture</option>
                            <option value="tutorial">Video Tutorial</option>
                            <option value="marksheet">Marksheets/Results</option>
                            <option value="prn">PRN/Identity Docs</option>
                            <option value="image">Photos/Images</option>
                            <option value="assignment">Assignment File</option>
                            <option value="lab_manual">Lab Manual</option>
                            <option value="syllabus">Syllabus Copy</option>
                            <option value="project">Project Document</option>
                            <option value="ebook">E-Book/PDF</option>
                            <option value="audio">Audio/Voice Note</option>
                            <option value="archive">Compressed (Zip/RAR)</option>
                            <option value="other">Miscellaneous</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">SELECT FILE (IMAGE, VIDEO, PDF, ETC.)</label>
                        <input type="file" name="file" class="form-control" required>
                        <div class="form-text">No file size limit (Subject to server configuration)</div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Start Uploading</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Video Player Modal -->
<div class="modal fade" id="videoModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-dark text-white" style="border-radius: 15px; overflow: hidden;">
            <div class="modal-header border-0">
                <h6 class="modal-title" id="vTitle">Video Player</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" onclick="stopVideo()"></button>
            </div>
            <div class="modal-body p-0">
                <video id="mainPlayer" controls class="w-100" style="max-height: 70vh;">
                    <source id="videoSource" src="" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const vModal = new bootstrap.Modal(document.getElementById('videoModal'));
    const player = document.getElementById('mainPlayer');
    const source = document.getElementById('videoSource');
    const title = document.getElementById('vTitle');

    function playVideo(path, fileName) {
        source.src = path;
        title.innerText = fileName;
        player.load();
        vModal.show();
        player.play();
    }

    function stopVideo() {
        player.pause();
        source.src = "";
    }
</script>
</body>
</html>