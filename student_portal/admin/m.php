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
            --accent-color: #4f46e5;
            --accent-hover: #4338ca;
            --bg-body: #f8fafc;
            --text-main: #1e293b;
            --card-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        }

        body {
            font-family: "Plus Jakarta Sans", sans-serif;
            background: var(--bg-body);
            color: var(--text-main);
            overflow-x: hidden;
        }

        /* Sidebar Modern Styling */
        .sidebar {
            width: 260px;
            height: 100vh;
            background: var(--sidebar-bg);
            position: fixed;
            padding: 2rem 1rem;
            color: white;
            z-index: 1000;
        }

        .sidebar-brand {
            font-weight: 800;
            font-size: 1.4rem;
            margin-bottom: 2.5rem;
            padding-left: 1rem;
            color: #fff;
            letter-spacing: -0.5px;
        }

        .sidebar nav a {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            color: #94a3b8;
            text-decoration: none;
            margin-bottom: 5px;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .sidebar nav a i { margin-right: 12px; font-size: 1.1rem; }

        .sidebar nav a:hover {
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
        }

        .sidebar nav a.active {
            background: var(--accent-color);
            color: white;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }

        .logout-section {
            margin-top: auto;
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 1rem;
        }

        /* Content Layout */
        .content { margin-left: 260px; padding: 40px 50px; }

        .header-section { margin-bottom: 2.5rem; }

        /* Course Grid & Cards */
        .course-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }

        .course-card {
            background: white;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .course-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05);
            border-color: var(--accent-color);
        }

        .card-top {
            padding: 24px;
            position: relative;
            flex-grow: 1;
        }

        .badge-seats {
            background: #eef2ff;
            color: #4f46e5;
            padding: 5px 12px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 700;
            display: inline-block;
            margin-bottom: 12px;
        }

        .course-code {
            color: #64748b;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .course-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: #0f172a;
            margin: 8px 0 20px 0;
            line-height: 1.4;
        }

        .info-group {
            display: grid;
            gap: 12px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
            color: #475569;
        }

        .info-item i { color: #94a3b8; font-size: 1rem; }

        /* Action Footer */
        .card-actions {
            background: #f8fafc;
            padding: 16px 24px;
            border-top: 1px solid #e2e8f0;
            border-radius: 0 0 16px 16px;
            display: flex;
            gap: 10px;
        }

        .btn-action {
            flex: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 12px;
            font-size: 0.85rem;
            font-weight: 600;
            border-radius: 10px;
            text-decoration: none;
            transition: 0.2s;
            border: 1px solid #e2e8f0;
            background: white;
            color: #475569;
        }

        .btn-action:hover {
            background: #f1f5f9;
            color: var(--text-main);
        }

        .btn-materials { border-color: #dbeafe; color: #2563eb; }
        .btn-materials:hover { background: #eff6ff; }

        .btn-edit { color: #d97706; }
        .btn-delete { color: #dc2626; }

        /* Global Button */
        .btn-primary-custom {
            background: var(--accent-color);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            transition: 0.3s;
        }

        .btn-primary-custom:hover {
            background: var(--accent-hover);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.4);
        }

        @media (max-width: 1024px) {
            .sidebar { width: 80px; }
            .sidebar span, .sidebar-brand span { display: none; }
            .content { margin-left: 80px; padding: 30px; }
            .sidebar-brand { padding-left: 0.5rem; }
        }
    </style>
</head>
<body>

<div class="sidebar d-flex flex-column">
    <div class="sidebar-brand">
        <i class="bi bi-layers-fill text-primary"></i> <span>Academic</span>
    </div>
    <nav class="d-flex flex-column h-100">
        <a href="admin_dashboard.php"><i class="bi bi-house"></i> <span>Dashboard</span></a>
        <a href="manage_students.php"><i class="bi bi-people"></i> <span>Students</span></a>
        <a href="manage_courses.php" class="active"><i class="bi bi-book"></i> <span>Courses</span></a>
        <a href="manage_assignments.php"><i class="bi bi-journal-check"></i> <span>Assignments</span></a>
        
        <div class="logout-section">
            <a href="../login.php" class="text-danger">
                <i class="bi bi-box-arrow-left"></i> <span>Sign Out</span>
            </a>
        </div>
    </nav>
</div>

<div class="content">
    <div class="header-section d-flex justify-content-between align-items-center">
        <div>
            <h2 class="fw-800 mb-1">Course Management</h2>
            <p class="text-muted mb-0">Manage your curriculum and academic schedule.</p>
        </div>
        <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addCourseModal">
            <i class="bi bi-plus-lg me-2"></i> Add Course
        </button>
    </div>

    <div class="course-grid">
        <?php while ($row = mysqli_fetch_assoc($courses)): ?>
        <div class="course-card">
            <div class="card-top">
                <div class="d-flex justify-content-between align-items-start">
                    <span class="badge-seats"><?= $row['total_seats']; ?> Total Seats</span>
                    <span class="course-code">ID: #<?= $row['id']; ?></span>
                </div>
                
                <div class="course-code mt-2"><?= htmlspecialchars($row['course_code']); ?></div>
                <div class="course-name"><?= htmlspecialchars($row['course_name']); ?></div>

                <div class="info-group">
                    <div class="info-item">
                        <i class="bi bi-person-circle"></i>
                        <span><strong>Instructor:</strong> <?= htmlspecialchars($row['instructor_name']); ?></span>
                    </div>
                    <div class="info-item">
                        <i class="bi bi-calendar-event"></i>
                        <span><strong>Schedule:</strong> <?= htmlspecialchars($row['schedule_day']); ?></span>
                    </div>
                    <div class="info-item">
                        <i class="bi bi-clock-history"></i>
                        <span><strong>Time:</strong> <?= htmlspecialchars($row['schedule_time']); ?></span>
                    </div>
                </div>
            </div>

            <div class="card-actions">
                <a href="course_materials.php?course_id=<?= $row['id']; ?>" class="btn-action btn-materials" title="Materials">
                    <i class="bi bi-folder2-open"></i> Materials
                </a>
                
                <button class="btn-action btn-edit" title="Edit"
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
                    <i class="bi bi-pencil"></i> Edit
                </button>

                <a href="course_delete.php?id=<?= $row['id']; ?>" 
                   onclick="return confirm('Are you sure you want to delete this course?');"
                   class="btn-action btn-delete" title="Delete">
                    <i class="bi bi-trash"></i> Delete
                </a>
            </div>
        </div>
        <?php endwhile; ?>
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
          <button type="submit" class="btn btn-warning fw-bold px-4 text-white" style="border-radius:10px;">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function editCourse(id, name, code, instructor, day, time, seats) {
    document.getElementById("edit_id").value = id;
    document.getElementById("edit_course_name").value = name;
    document.getElementById("edit_course_code").value = code;
    document.getElementById("edit_instructor_name").value = instructor;
    document.getElementById("edit_schedule_day").value = day;
    document.getElementById("edit_schedule_time").value = time;
    document.getElementById("edit_total_seats").value = seats;
}
</script>

</body>
</html>