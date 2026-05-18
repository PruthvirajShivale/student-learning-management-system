<?php
session_start();
require "../config.php";

if (!isset($_SESSION['admin_email'])) {
    header("Location: ../login.php");
    exit();
}

$courses_q = mysqli_query($conn, "SELECT id, course_name FROM courses ORDER BY course_name");
$assignments = mysqli_query($conn, "SELECT * FROM assignments ORDER BY id DESC");
$students_q = mysqli_query($conn, "SELECT id, name, roll_no FROM students ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Assignments | Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <style>
        :root {
            --sidebar-bg: #0f172a;
            --brand-primary: #4f46e5;
            --brand-hover: #4338ca;
            --bg-main: #f8fafc;
            --card-radius: 16px;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg-main);
            color: #1e293b;
            margin: 0;
        }

        /* Sidebar Modernization */
        .sidebar {
            width: 280px;
            height: 100vh;
            position: fixed;
            background: var(--sidebar-bg);
            color: white;
            padding: 1.5rem;
            z-index: 1000;
        }

        .sidebar h3 {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 2rem;
            padding-left: 0.5rem;
            color: #818cf8;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            margin-bottom: 8px;
            border-radius: 12px;
            text-decoration: none;
            color: #94a3b8;
            font-weight: 500;
            transition: 0.3s;
        }

        .sidebar a:hover {
            background: rgba(255, 255, 255, 0.05);
            color: white;
        }

        .sidebar a.active {
            background: var(--brand-primary);
            color: white;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }

        /* Content Area */
        .content {
            margin-left: 280px;
            padding: 2.5rem;
        }

        /* Header Section */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-header h2 { font-weight: 800; color: #0f172a; }

        /* Modern Grid Cards (The "Advance UI") */
        .assignment-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }

        .assignment-card {
            background: white;
            border-radius: var(--card-radius);
            padding: 1.5rem;
            border: 1px solid #e2e8f0;
            transition: 0.3s;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .assignment-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px -10px rgba(0,0,0,0.1);
        }

        .status-badge {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status-active { background: #dcfce7; color: #166534; }
        .status-inactive { background: #fee2e2; color: #991b1b; }

        .course-tag {
            font-size: 0.8rem;
            color: var(--brand-primary);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .assignment-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin: 0.5rem 0 1rem 0;
            color: #0f172a;
        }

        .info-row {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
            color: #64748b;
            margin-bottom: 0.75rem;
        }

        .card-actions {
            margin-top: auto;
            padding-top: 1.5rem;
            display: flex;
            gap: 8px;
            border-top: 1px solid #f1f5f9;
        }

        /* Modal Customization */
        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
        }

        .modal-header { border-bottom: 1px solid #f1f5f9; padding: 1.5rem; }
        .modal-body { padding: 1.5rem; }
        .form-control, .form-select {
            border-radius: 10px;
            padding: 10px 14px;
            border: 1px solid #e2e8f0;
            margin-bottom: 1rem;
        }

        .form-control:focus {
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
            border-color: var(--brand-primary);
        }

        label { font-weight: 600; font-size: 0.85rem; margin-bottom: 6px; color: #475569; }

        .btn-modern {
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 600;
            transition: 0.2s;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <h3>NexusAdmin</h3>
    <a href="admin_dashboard.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
    <a href="manage_students.php"><i class="bi bi-people me-2"></i> Manage Students</a>
    <a href="manage_courses.php"><i class="bi bi-journal-bookmark me-2"></i> Manage Courses</a>
    <a href="manage_assignments.php" class="active"><i class="bi bi-file-earmark-text me-2"></i> Assignments</a>
    <div style="margin-top: auto; padding-top: 2rem;">
        <a href="../login.php" style="background:#ef4444; color: white;"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
    </div>
</div>

<div class="content">
    <div class="page-header">
        <div>
            <h2>Manage Assignments</h2>
            <p class="text-muted">Create, edit, and track student task progress.</p>
        </div>
        <button class="btn btn-primary btn-modern" data-bs-toggle="modal" data-bs-target="#addAssignmentModal">
            <i class="bi bi-plus-lg me-1"></i> Add Assignment
        </button>
    </div>

    <div class="assignment-grid">
        <?php while ($row = mysqli_fetch_assoc($assignments)): 
            $assign_to_label = 'All Students';
            if ($row['assign_to'] !== 'all' && strpos($row['assign_to'], 'student:') === 0) {
                $sid = intval(substr($row['assign_to'], 8));
                $sres = mysqli_query($conn, "SELECT name, roll_no FROM students WHERE id='$sid'");
                if ($sres && mysqli_num_rows($sres)==1) {
                    $s = mysqli_fetch_assoc($sres);
                    $assign_to_label = $s['name']." (".$s['roll_no'].")";
                }
            }

            $course_name = 'N/A';
            if (!empty($row['course_id'])) {
                $cres = mysqli_query($conn, "SELECT course_name FROM courses WHERE id='".$row['course_id']."'");
                if ($cres && mysqli_num_rows($cres)==1) {
                    $c = mysqli_fetch_assoc($cres);
                    $course_name = $c['course_name'];
                }
            }
        ?>
        <div class="assignment-card">
            <span class="status-badge <?= $row['status'] == 'active' ? 'status-active' : 'status-inactive' ?>">
                <?= $row['status'] ?>
            </span>
            
            <div class="course-tag"><?= htmlspecialchars($course_name) ?></div>
            <div class="assignment-title"><?= htmlspecialchars($row['title']) ?></div>
            
            <div class="info-row">
                <i class="bi bi-calendar-event text-primary"></i>
                <span>Due: <strong><?= $row['due_date'] ?></strong></span>
            </div>
            <div class="info-row">
                <i class="bi bi-person-check text-primary"></i>
                <span>Assigned: <?= $assign_to_label ?></span>
            </div>

            <div class="card-actions">
                <button class="btn btn-light btn-sm flex-grow-1"
                    data-bs-toggle="modal" data-bs-target="#editAssignmentModal"
                    data-course_id="<?= $row['course_id'] ?>" data-id="<?= $row['id'] ?>"
                    data-title="<?= htmlspecialchars($row['title'], ENT_QUOTES) ?>"
                    data-description="<?= htmlspecialchars($row['description'], ENT_QUOTES) ?>"
                    data-due="<?= $row['due_date'] ?>" data-status="<?= $row['status'] ?>"
                    data-assign="<?= $row['assign_to'] ?>" onclick="openEdit(this)">
                    <i class="bi bi-pencil"></i> Edit
                </button>
                
                <?php if (!empty($row['file_path'])): ?>
                    <a class="btn btn-outline-info btn-sm" href="<?= '../uploads/assignments/' . htmlspecialchars($row['file_path']); ?>" target="_blank">
                        <i class="bi bi-file-pdf"></i> PDF
                    </a>
                <?php endif; ?>

                <a class="btn btn-outline-success btn-sm" href="view_submissions.php?id=<?= $row['id'] ?>">
                    <i class="bi bi-eye"></i> View
                </a>
                
                <a class="btn btn-outline-danger btn-sm" href="assignment_delete.php?id=<?= $row['id'] ?>" onclick="return confirm('Delete this assignment?')">
                    <i class="bi bi-trash"></i>
                </a>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<div class="modal fade" id="addAssignmentModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form action="assignment_add.php" method="POST" enctype="multipart/form-data">
        <div class="modal-header bg-primary text-white">
            <h5 class="mb-0 fw-bold">Create New Assignment</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-6">
                    <label>Assignment Title</label>
                    <input name="title" class="form-control" required placeholder="e.g. Final Project Phase 1">
                </div>
                <div class="col-md-6">
                    <label>Course</label>
                    <select name="course_id" class="form-select" required>
                        <option value="">-- Select Course --</option>
                        <?php mysqli_data_seek($courses_q,0); while($c = mysqli_fetch_assoc($courses_q)): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['course_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            
            <label>Description</label>
            <textarea name="description" class="form-control" rows="3" required placeholder="Outline the requirements..."></textarea>
            
            <div class="row">
                <div class="col-md-4">
                    <label>Due Date</label>
                    <input type="date" name="due_date" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label>Status</label>
                    <select name="status" class="form-select">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label>Attachment (PDF)</label>
                    <input type="file" name="file" class="form-control">
                </div>
            </div>

            <div class="p-3 bg-light rounded-3 mt-2">
                <label class="d-block mb-2">Target Audience</label>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="assign_mode" value="all" checked id="mode_all">
                    <label class="form-check-label" for="mode_all">All Students</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="assign_mode" value="specific" id="mode_spec">
                    <label class="form-check-label" for="mode_spec">Specific Student</label>
                </div>
                <select name="specific_student" id="specific_student" class="form-select mt-2" style="display:none;">
                    <option value="">-- Select Student --</option>
                    <?php mysqli_data_seek($students_q,0); while($s = mysqli_fetch_assoc($students_q)): ?>
                        <option value="<?= $s['id'] ?>"><?= $s['name'].' ('.$s['roll_no'].')' ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-light btn-modern" data-bs-dismiss="modal">Cancel</button>
            <button class="btn btn-primary btn-modern shadow-sm">Save Assignment</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="editAssignmentModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form action="assignment_edit.php" method="POST" enctype="multipart/form-data">
        <div class="modal-header bg-warning">
            <h5 class="mb-0 fw-bold">Edit Assignment Details</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="edit_id" name="id">
            <div class="row">
                <div class="col-md-6">
                    <label>Title</label>
                    <input id="edit_title" name="title" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label>Course</label>
                    <select id="edit_course_id" name="course_id" class="form-select" required>
                        <?php mysqli_data_seek($courses_q,0); while($c = mysqli_fetch_assoc($courses_q)): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['course_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <label>Description</label>
            <textarea id="edit_description" name="description" class="form-control" rows="3" required></textarea>

            <div class="row">
                <div class="col-md-4">
                    <label>Due Date</label>
                    <input id="edit_due_date" type="date" name="due_date" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label>Status</label>
                    <select id="edit_status" name="status" class="form-select">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label>Replace File</label>
                    <input type="file" name="file" class="form-control">
                </div>
            </div>

            <div class="p-3 bg-light rounded-3 mt-2">
                <label class="d-block mb-2">Assign To</label>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="edit_assign_mode" id="edit_assign_all" value="all">
                    <label class="form-check-label" for="edit_assign_all">All Students</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="edit_assign_mode" id="edit_assign_specific" value="specific">
                    <label class="form-check-label" for="edit_assign_specific">Specific Student</label>
                </div>
                <select name="edit_specific_student" id="edit_specific_student" class="form-select mt-2" style="display:none;">
                    <?php mysqli_data_seek($students_q,0); while($s = mysqli_fetch_assoc($students_q)): ?>
                        <option value="<?= $s['id'] ?>"><?= $s['name'].' ('.$s['roll_no'].')' ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
        <div class="modal-footer border-0">
            <button type="button" class="btn btn-light btn-modern" data-bs-dismiss="modal">Close</button>
            <button class="btn btn-warning btn-modern">Update Assignment</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Toggle visibility for student dropdown in ADD modal
document.querySelectorAll('input[name="assign_mode"]').forEach(r => {
    r.addEventListener('change', () => {
        document.getElementById('specific_student').style.display = (r.value === 'specific') ? 'block' : 'none';
    });
});

// Toggle visibility for student dropdown in EDIT modal
document.querySelectorAll('input[name="edit_assign_mode"]').forEach(r => {
    r.addEventListener('change', () => {
        document.getElementById('edit_specific_student').style.display = (r.value === 'specific') ? 'block' : 'none';
    });
});

function openEdit(btn) {
    document.getElementById('edit_id').value = btn.dataset.id;
    document.getElementById('edit_title').value = btn.dataset.title;
    document.getElementById('edit_description').value = btn.dataset.description;
    document.getElementById('edit_due_date').value = btn.dataset.due;
    document.getElementById('edit_status').value = btn.dataset.status;
    document.getElementById('edit_course_id').value = btn.dataset.course_id;

    if (btn.dataset.assign === 'all') {
        document.getElementById('edit_assign_all').checked = true;
        document.getElementById('edit_specific_student').style.display = 'none';
        document.getElementById('edit_specific_student').value = '';
    } else {
        document.getElementById('edit_assign_specific').checked = true;
        document.getElementById('edit_specific_student').style.display = 'block';
        document.getElementById('edit_specific_student').value = btn.dataset.assign.split(':')[1];
    }
}
</script>
</body>
</html>