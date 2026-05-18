<?php
session_start();
require "../config.php";

if (!isset($_SESSION['admin_email'])) {
    header("Location: ../login.php");
    exit();
}

// Fetch all students
$students = mysqli_query($conn, "SELECT * FROM students ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students | Admin Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <style>
        :root {
            --brand-primary: #6366f1;
            --sidebar-bg: #0f172a;
            --body-bg: #f1f5f9;
            --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.02);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--body-bg);
            color: #1e293b;
        }

        /* Sidebar remains for navigation consistency */
        .sidebar {
            width: 260px;
            height: 100vh;
            background: var(--sidebar-bg);
            position: fixed;
            padding: 24px;
            color: #f1f5f9;
            z-index: 1000;
        }

        .sidebar a {
            display: flex; align-items: center; padding: 12px 16px; color: #94a3b8;
            text-decoration: none; margin-bottom: 8px; border-radius: 12px;
            font-weight: 500; transition: 0.2s; gap: 12px;
        }

        .sidebar a.active { background: var(--brand-primary); color: white; }

        .content { margin-left: 260px; padding: 40px; }

        /* ADVANCED CARD UI */
        .student-card {
            background: #ffffff;
            border: none;
            border-radius: 20px;
            padding: 24px;
            height: 100%;
            transition: all 0.3s ease;
            box-shadow: var(--card-shadow);
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .student-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .student-id-pill {
            position: absolute;
            top: 20px;
            right: 20px;
            background: #f8fafc;
            color: #64748b;
            font-size: 0.7rem;
            font-weight: 800;
            padding: 4px 10px;
            border-radius: 100px;
        }

        .avatar-box {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: white;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 16px;
        }

        .student-name {
            font-weight: 700;
            font-size: 1.15rem;
            margin-bottom: 2px;
            color: #0f172a;
        }

        .info-group {
            background: #f8fafc;
            border-radius: 12px;
            padding: 12px;
            margin-top: 15px;
        }

        .info-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #94a3b8;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .info-value {
            font-size: 0.85rem;
            font-weight: 600;
            color: #334155;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .card-actions {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #f1f5f9;
            display: flex;
            gap: 10px;
        }

        .btn-action {
            flex: 1;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.85rem;
            padding: 8px;
        }

        /* Modal styling */
        .modal-content { border-radius: 24px; border: none; }
        .form-control { border-radius: 12px; background: #f8fafc; padding: 12px; }

        @media (max-width: 992px) {
            .sidebar { width: 80px; padding: 15px; }
            .sidebar span, .sidebar h3 span { display: none; }
            .content { margin-left: 80px; }
        }
    </style>
</head>

<body>

<div class="sidebar">
    <h3><i class="bi bi-shield-lock-fill"></i> <span>NexusAdmin</span></h3>
    <a href="admin_dashboard.php"><i class="bi bi-grid-1x2"></i> <span>Dashboard</span></a>
    <a href="manage_students.php" class="active"><i class="bi bi-people-fill"></i> <span>Manage Students</span></a>
    <a href="manage_courses.php"><i class="bi bi-book"></i> <span>Manage Courses</span></a>
    <a href="manage_assignments.php"><i class="bi bi-journal-check"></i> <span>Manage Assignments</span></a>
    
    <div style="margin-top: auto; padding-top: 20px;">
        <a href="../login.php" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
            <i class="bi bi-box-arrow-left"></i> <span>Logout</span>
        </a>
    </div>
</div>

<div class="content">
    <div class="d-flex justify-content-between align-items-end mb-5">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item"><a href="#" class="text-decoration-none text-muted">Portal</a></li>
                    <li class="breadcrumb-item active">Students</li>
                </ol>
            </nav>
            <h2 class="fw-bold mb-0">Student Directory</h2>
            <p class="text-muted">Currently managing <?= mysqli_num_rows($students) ?> enrolled students</p>
        </div>
        <button class="btn btn-primary px-4 py-2 rounded-pill fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#addStudentModal">
            <i class="bi bi-plus-lg me-2"></i> Register Student
        </button>
    </div>

    <div class="row g-4">
        <?php while ($row = mysqli_fetch_assoc($students)): ?>
        <div class="col-xl-4 col-md-6">
            <div class="student-card">
                <span class="student-id-pill">ID #<?= $row['id']; ?></span>
                
                <div class="avatar-box">
                    <?= strtoupper(substr($row['name'], 0, 1)); ?>
                </div>

                <div class="student-name"><?= htmlspecialchars($row['name']); ?></div>
                <div class="small text-muted mb-2"><i class="bi bi-envelope me-1"></i> <?= htmlspecialchars($row['email']); ?></div>
                
                <div class="d-flex gap-2 mb-3">
                    <span class="badge bg-indigo-subtle text-primary border border-primary-subtle rounded-pill px-3">
                        <?= htmlspecialchars($row['roll_no']); ?>
                    </span>
                    <span class="badge bg-light text-dark border rounded-pill px-3">
                        Batch 2026
                    </span>
                </div>

                <div class="info-group">
                    <div class="row">
                        <div class="col-6">
                            <div class="info-label">College</div>
                            <div class="info-value text-truncate" title="<?= htmlspecialchars($row['college']); ?>">
                                <?= htmlspecialchars($row['college']); ?>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="info-label">Enrolled</div>
                            <div class="info-value"><?= date('M Y', strtotime($row['created_at'])); ?></div>
                        </div>
                    </div>
                </div>

                <div class="info-group">
                    <div class="row">
                        <div class="col-6">
                            <div class="info-label">Student Phone</div>
                            <div class="info-value"><i class="bi bi-phone"></i> <?= htmlspecialchars($row['contact']); ?></div>
                        </div>
                        <div class="col-6">
                            <div class="info-label">Parent Phone</div>
                            <div class="info-value"><i class="bi bi-telephone-outbound"></i> <?= htmlspecialchars($row['parent_contact']); ?></div>
                        </div>
                    </div>
                </div>

                <div class="card-actions">
                    <button 
                        class="btn btn-outline-warning btn-action"
                        data-bs-toggle="modal"
                        data-bs-target="#editStudentModal"
                        onclick="editStudent(
                            '<?= $row['id']; ?>',
                            '<?= addslashes($row['name']); ?>',
                            '<?= addslashes($row['roll_no']); ?>',
                            '<?= addslashes($row['college']); ?>',
                            '<?= addslashes($row['email']); ?>',
                            '<?= addslashes($row['contact']); ?>',
                            '<?= addslashes($row['parent_contact']); ?>'
                        )"
                    >
                        <i class="bi bi-pencil-square me-1"></i> Edit
                    </button>

                    <a href="student_delete.php?id=<?= $row['id']; ?>"
                       onclick="return confirm('Delete this student permanently?');"
                       class="btn btn-outline-danger btn-action">
                        <i class="bi bi-trash3 me-1"></i> Delete
                    </a>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<div class="modal fade" id="addStudentModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content p-3">
      <form action="student_add.php" method="POST">
        <div class="modal-header border-0">
          <h5 class="modal-title fw-bold fs-4">New Student Entry</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-4">
            <div class="col-md-6">
                <label class="form-label fw-600">Full Name</label>
                <input type="text" name="name" required class="form-control" placeholder="Full legal name">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-600">Roll Number</label>
                <input type="text" name="roll_no" required class="form-control" placeholder="e.g. S101">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-600">College / Institute</label>
                <input type="text" name="college" required class="form-control" placeholder="University Name">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-600">Email Address</label>
                <input type="email" name="email" required class="form-control" placeholder="john@example.com">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-600">Primary Contact</label>
                <input type="text" name="contact" required class="form-control" placeholder="+1...">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-600">Parent Contact</label>
                <input type="text" name="parent_contact" required class="form-control" placeholder="Emergency No.">
            </div>
            <div class="col-md-12">
                <label class="form-label fw-600">Initial Password</label>
                <input type="password" name="password" required class="form-control" placeholder="••••••••">
            </div>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Confirm Registration</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="editStudentModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content p-3 border-warning border-top border-4">
      <form action="student_edit.php" method="POST">
        <div class="modal-header border-0">
          <h5 class="modal-title fw-bold">Modify Student Record</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="edit_id" name="id">
          <div class="row g-4">
            <div class="col-md-6"><label class="form-label">Name</label><input id="edit_name" type="text" name="name" required class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Roll No</label><input id="edit_roll_no" type="text" name="roll_no" required class="form-control"></div>
            <div class="col-md-6"><label class="form-label">College</label><input id="edit_college" type="text" name="college" required class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Email</label><input id="edit_email" type="email" name="email" required class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Contact</label><input id="edit_contact" type="text" name="contact" required class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Parent No</label><input id="edit_parent_contact" type="text" name="parent_contact" required class="form-control"></div>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Discard</button>
          <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold">Apply Updates</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function editStudent(id, name, roll, college, email, contact, parent_contact) {
    document.getElementById("edit_id").value = id;
    document.getElementById("edit_name").value = name;
    document.getElementById("edit_roll_no").value = roll;
    document.getElementById("edit_college").value = college;
    document.getElementById("edit_email").value = email;
    document.getElementById("edit_contact").value = contact;
    document.getElementById("edit_parent_contact").value = parent_contact;
}
</script>

</body>
</html>