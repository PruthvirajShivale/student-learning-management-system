<?php
session_start();
require "../config.php";

if (!isset($_SESSION['admin_email'])) {
    header("Location: ../login.php");
    exit();
}

// Fetch all courses
$courses = mysqli_query($conn, "SELECT * FROM courses ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses | Academic Admin</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <style>
        :root {
            --sidebar-bg: #0f172a;
            --accent-color: #6366f1;
            --accent-hover: #4f46e5;
            --bg-body: #f1f5f9;
            --text-main: #1e293b;
            --card-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.05), 0 4px 6px -4px rgb(0 0 0 / 0.05);
        }

        body {
            font-family: "Plus Jakarta Sans", sans-serif;
            background: var(--bg-body);
            color: var(--text-main);
            overflow-x: hidden;
            letter-spacing: -0.01em;
        }

        /* Sidebar Styling */
        .sidebar {
            width: 270px;
            height: 95vh;
            background: var(--sidebar-bg);
            position: fixed;
            top: 2.5vh;
            left: 20px;
            padding: 2rem 1.25rem;
            color: white;
            z-index: 1000;
            border-radius: 24px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .sidebar-brand {
            font-weight: 800;
            font-size: 1.5rem;
            margin-bottom: 3rem;
            padding-left: 0.75rem;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #fff;
        }

        .sidebar nav a {
            display: flex;
            align-items: center;
            padding: 14px 18px;
            color: #94a3b8;
            text-decoration: none;
            margin-bottom: 8px;
            border-radius: 14px;
            font-weight: 600;
            transition: all 0.25s ease;
        }

        .sidebar nav a i { margin-right: 12px; font-size: 1.25rem; }

        .sidebar nav a:hover {
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
            transform: translateX(4px);
        }

        .sidebar nav a.active {
            background: var(--accent-color);
            color: white;
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.4);
        }

        .logout-section {
            margin-top: auto;
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 1.5rem;
        }

        /* Content Layout */
        .content { margin-left: 320px; padding: 50px 40px; }

        .header-section { margin-bottom: 3.5rem; }

        /* Course Grid & Cards */
        .course-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 30px;
        }

        .course-card {
            background: white;
            border-radius: 24px;
            border: 1px solid #e2e8f0;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow: hidden;
        }

        .course-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 30px 40px -15px rgba(0, 0, 0, 0.08);
            border-color: var(--accent-color);
        }

        .card-top {
            padding: 30px;
            position: relative;
            flex-grow: 1;
        }

        .description-box {
            background: #f8fafc;
            border-radius: 16px;
            padding: 16px;
            margin-top: 20px;
            font-size: 0.85rem;
            color: #64748b;
            border: 1px dashed #e2e8f0;
        }

        .description-text {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.6;
        }

        .description-text.expanded { display: block; }

        .read-more-btn {
            background: none;
            border: none;
            color: var(--accent-color);
            font-weight: 700;
            font-size: 0.75rem;
            margin-top: 8px;
            padding: 0;
            text-transform: uppercase;
        }

        .badge-seats {
            background: #f0fdf4;
            color: #16a34a;
            padding: 6px 14px;
            border-radius: 10px;
            font-size: 0.75rem;
            font-weight: 800;
        }

        .course-code-tag {
            color: var(--accent-color);
            font-size: 0.75rem;
            font-weight: 800;
            background: rgba(99, 102, 241, 0.1);
            padding: 4px 10px;
            border-radius: 6px;
            text-transform: uppercase;
        }

        .course-name {
            font-size: 1.4rem;
            font-weight: 800;
            color: #0f172a;
            margin: 15px 0 20px 0;
            line-height: 1.3;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.9rem;
            color: #475569;
            margin-bottom: 12px;
        }

        .info-item i { 
            width: 32px;
            height: 32px;
            background: #f1f5f9;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
        }

        /* Action Footer */
        .card-actions {
            background: #fff;
            padding: 20px 30px;
            border-top: 1px solid #f1f5f9;
            display: flex;
            gap: 12px;
        }

        .btn-action {
            height: 42px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s;
            border: 1px solid #e2e8f0;
            padding: 0 15px;
            text-decoration: none;
            color: #475569;
        }

        .btn-materials { background: #0f172a; color: #fff; border: none; flex-grow: 2; }
        .btn-materials:hover { background: #334155; color: #fff; }

        .btn-edit { background: white; color: #64748b; flex-grow: 1; }
        .btn-edit:hover { border-color: var(--accent-color); color: var(--accent-color); }

        .btn-delete { color: #ef4444; width: 42px; padding: 0; }
        .btn-delete:hover { background: #fef2f2; border-color: #fecaca; }

        .btn-primary-custom {
            background: var(--accent-color);
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 16px;
            font-weight: 700;
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3);
        }

        /* Modals */
        .modal-content { border-radius: 28px; border: none; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.2); }
        .form-control { border-radius: 12px; padding: 12px 16px; border: 1px solid #e2e8f0; background: #f8fafc; }
        .form-control:focus { box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); border-color: var(--accent-color); }

        @media (max-width: 1024px) {
            .sidebar { width: 85px; left: 10px; }
            .sidebar span, .sidebar-brand span { display: none; }
            .content { margin-left: 110px; padding: 30px; }
        }
    </style>
</head>
<body>

<div class="sidebar d-flex flex-column">
    <div class="sidebar-brand">
        <i class="bi bi-rocket-takeoff-fill text-primary"></i> <span>Academic</span>
    </div>
    <nav class="d-flex flex-column h-100">
        <a href="admin_dashboard.php"><i class="bi bi-grid-1x2"></i> <span>Dashboard</span></a>
        <a href="manage_students.php"><i class="bi bi-people"></i> <span>Students</span></a>
        <a href="manage_courses.php" class="active"><i class="bi bi-journal-bookmark"></i> <span>Courses</span></a>
        <a href="manage_assignments.php"><i class="bi bi-check2-square"></i> <span>Assignments</span></a>
        
        <div class="logout-section">
            <a href="../login.php" class="text-danger">
                <i class="bi bi-power"></i> <span>Sign Out</span>
            </a>
        </div>
    </nav>
</div>

<div class="content">
    <div class="header-section d-flex justify-content-between align-items-center">
        <div>
            <h2 class="fw-800 mb-1" style="font-weight:800; font-size: 2.2rem; letter-spacing:-0.03em;">Course Management</h2>
            <p class="text-muted mb-0">Review, update, and organize your academic catalog.</p>
        </div>
        <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addCourseModal">
            <i class="bi bi-plus-lg me-2"></i> Create Course
        </button>
    </div>

    <div class="course-grid">
        <?php while ($row = mysqli_fetch_assoc($courses)): ?>
        <div class="course-card">
            <div class="card-top">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="course-code-tag"><?= htmlspecialchars($row['course_code']); ?></span>
                    <span class="badge-seats"><?= $row['total_seats']; ?> Total Seats</span>
                </div>
                
                <div class="course-name"><?= htmlspecialchars($row['course_name']); ?></div>

                <div class="info-group">
                    <div class="info-item">
                        <i class="bi bi-person"></i>
                        <span><strong>Instructor:</strong> <?= htmlspecialchars($row['instructor_name']); ?></span>
                    </div>
                    <div class="info-item">
                        <i class="bi bi-calendar3"></i>
                        <span><strong>Schedule:</strong> <?= htmlspecialchars($row['schedule_day']); ?></span>
                    </div>
                </div>

                <?php if(!empty($row['description'])): ?>
                <div class="description-box">
                    <div id="desc-<?= $row['id']; ?>" class="description-text">
                        <?= nl2br(htmlspecialchars($row['description'])); ?>
                    </div>
                    <button class="read-more-btn" onclick="toggleDescription(<?= $row['id']; ?>)" id="btn-<?= $row['id']; ?>">Read More</button>
                </div>
                <?php endif; ?>
            </div>

            <div class="card-actions">
                <a href="course_materials.php?course_id=<?= $row['id']; ?>" class="btn-action btn-materials">
                    <i class="bi bi-folder2-open me-2"></i> Materials
                </a>

                <button class="btn-action btn-edit" 
                        data-bs-toggle="modal" 
                        data-bs-target="#infoModal" 
                        onclick="setInfoModal('<?= $row['id']; ?>', '<?= htmlspecialchars($row['description'], ENT_QUOTES); ?>')">
                    <i class="bi bi-info-circle"></i>
                </button>
                
                <button class="btn-action btn-edit" 
                    data-bs-toggle="modal"
                    data-bs-target="#editCourseModal"
                    onclick="editCourse(
                        '<?= $row['id']; ?>',
                        '<?= htmlspecialchars($row['course_name'], ENT_QUOTES); ?>',
                        '<?= htmlspecialchars($row['course_code'], ENT_QUOTES); ?>',
                        '<?= htmlspecialchars($row['instructor_name'], ENT_QUOTES); ?>',
                        '<?= htmlspecialchars($row['schedule_day'], ENT_QUOTES); ?>',
                        '<?= htmlspecialchars($row['schedule_time'], ENT_QUOTES); ?>',
                        '<?= $row['total_seats']; ?>'
                    )">
                    <i class="bi bi-pencil-square"></i>
                </button>

                <a href="course_delete.php?id=<?= $row['id']; ?>" 
                   onclick="return confirm('Are you sure you want to delete this course?');"
                   class="btn-action btn-delete">
                    <i class="bi bi-trash3"></i>
                </a>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<div class="modal fade" id="infoModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form action="update_course_info.php" method="POST">
        <div class="modal-header border-0 pt-4 px-4">
          <h5 class="modal-title fw-bold">Manage Course Information</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body px-4">
          <input type="hidden" name="course_id" id="info_course_id">
          <div class="mb-3">
              <label class="form-label small fw-bold">Detailed Description</label>
              <textarea name="description" id="info_description" class="form-control" rows="6" placeholder="Add course details..."></textarea>
          </div>
        </div>
        <div class="modal-footer border-0 pb-4 px-4">
          <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success px-4 fw-bold text-white" style="border-radius:12px;">Save Info</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="addCourseModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form action="course_add.php" method="POST">
        <div class="modal-header border-0 pt-4 px-4">
          <h5 class="modal-title fw-bold">New Course</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body px-4">
          <div class="row g-3">
            <div class="col-12">
                <label class="form-label small fw-bold">Course Title</label>
                <input type="text" name="course_name" required class="form-control" placeholder="e.g. Advanced Web Development">
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-bold">Course Code</label>
                <input type="text" name="course_code" required class="form-control" placeholder="CS-202">
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-bold">Total Seats</label>
                <input type="number" name="total_seats" class="form-control" value="60">
            </div>
            <div class="col-12">
                <label class="form-label small fw-bold">Instructor Name</label>
                <input type="text" name="instructor_name" class="form-control" placeholder="Dr. Sarah Johnson">
            </div>
            <div class="col-6">
                <label class="form-label small fw-bold">Schedule Day</label>
                <input type="text" name="schedule_day" class="form-control" placeholder="Mon, Wed">
            </div>
            <div class="col-6">
                <label class="form-label small fw-bold">Schedule Time</label>
                <input type="text" name="schedule_time" class="form-control" placeholder="10:00 AM">
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 pb-4 px-4">
          <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary-custom px-4">Create Course</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="editCourseModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form action="course_edit.php" method="POST">
        <div class="modal-header border-0 pt-4 px-4">
          <h5 class="modal-title fw-bold">Update Course Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body px-4">
          <input type="hidden" name="id" id="edit_id">
          <div class="row g-3">
            <div class="col-12">
                <label class="form-label small fw-bold">Course Name</label>
                <input id="edit_course_name" type="text" name="course_name" required class="form-control">
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-bold">Course Code</label>
                <input id="edit_course_code" type="text" name="course_code" required class="form-control">
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-bold">Total Seats</label>
                <input id="edit_total_seats" type="number" name="total_seats" class="form-control">
            </div>
            <div class="col-12">
                <label class="form-label small fw-bold">Instructor Name</label>
                <input id="edit_instructor_name" type="text" name="instructor_name" class="form-control">
            </div>
            <div class="col-6">
                <label class="form-label small fw-bold">Schedule Day</label>
                <input id="edit_schedule_day" type="text" name="schedule_day" class="form-control">
            </div>
            <div class="col-6">
                <label class="form-label small fw-bold">Schedule Time</label>
                <input id="edit_schedule_time" type="text" name="schedule_time" class="form-control">
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 pb-4 px-4">
          <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-warning fw-bold px-4 text-white" style="border-radius:12px;">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// All Original JavaScript Logic Maintained
function editCourse(id, name, code, instructor, day, time, seats) {
    document.getElementById("edit_id").value = id;
    document.getElementById("edit_course_name").value = name;
    document.getElementById("edit_course_code").value = code;
    document.getElementById("edit_instructor_name").value = instructor;
    document.getElementById("edit_schedule_day").value = day;
    document.getElementById("edit_schedule_time").value = time;
    document.getElementById("edit_total_seats").value = seats;
}

function setInfoModal(id, description) {
    document.getElementById("info_course_id").value = id;
    document.getElementById("info_description").value = description;
}

function toggleDescription(id) {
    const text = document.getElementById('desc-' + id);
    const btn = document.getElementById('btn-' + id);
    if (text.classList.contains('expanded')) {
        text.classList.remove('expanded');
        btn.innerText = 'Read More';
    } else {
        text.classList.add('expanded');
        btn.innerText = 'Show Less';
    }
}
</script>

</body>
</html>