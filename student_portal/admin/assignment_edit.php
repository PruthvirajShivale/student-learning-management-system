<?php
session_start();
require "../config.php";

$upload_dir = "../uploads/assignments/";
if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

/* =======================
   1. FETCH DATA FOR EDIT
   ======================= */
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $result = mysqli_query($conn, "SELECT * FROM assignments WHERE id='$id'");
    if (mysqli_num_rows($result) == 0) {
        die("Assignment not found");
    }
    $row = mysqli_fetch_assoc($result);
}

/* =======================
   2. FETCH COURSES
   ======================= */
$courses_q = mysqli_query($conn, "SELECT id, course_name FROM courses ORDER BY course_name");

/* =======================
   3. UPDATE ASSIGNMENT
   ======================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = intval($_POST['id']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $due_date = $_POST['due_date'];
    $status = $_POST['status'];

    // COURSE ID
    $course_id = !empty($_POST['course_id']) ? intval($_POST['course_id']) : null;

    // ASSIGN TO (all or specific student)
    $assign_mode = $_POST['edit_assign_mode'] ?? 'all';
    if ($assign_mode === 'specific' && !empty($_POST['edit_specific_student'])) {
        $assign_to = 'student:' . intval($_POST['edit_specific_student']);
    } else {
        $assign_to = 'all';
    }

    // old file
    $oldQ = mysqli_query($conn, "SELECT file_path FROM assignments WHERE id='$id'");
    $old = mysqli_fetch_assoc($oldQ);
    $file_name = $old['file_path'];

    // replace file
    if (!empty($_FILES['file']['name'])) {
        $newname = time() . "_" . basename($_FILES['file']['name']);
        if (move_uploaded_file($_FILES['file']['tmp_name'], $upload_dir . $newname)) {
            if (!empty($file_name) && file_exists($upload_dir . $file_name)) {
                unlink($upload_dir . $file_name); // delete old file
            }
            $file_name = $newname;
        } else {
            die("File upload failed");
        }
    }

    $sql = "UPDATE assignments SET 
                title='$title',
                description='$description',
                file_path='$file_name',
                due_date='$due_date',
                status='$status',
                assign_to='$assign_to',
                course_id='$course_id'
            WHERE id='$id'";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Assignment Updated Successfully'); window.location='manage_assignments.php';</script>";
        exit;
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Assignment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">

<div class="container">
    <h2>Edit Assignment</h2>
    <form method="POST" enctype="multipart/form-data">

        <input type="hidden" name="id" value="<?= $row['id'] ?>">

        <div class="mb-3">
            <label>Title</label>
            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($row['title']) ?>" required>
        </div>

        <div class="mb-3">
            <label>Description</label>
            <textarea name="description" class="form-control" required><?= htmlspecialchars($row['description']) ?></textarea>
        </div>

        <div class="mb-3">
            <label>Due Date</label>
            <input type="date" name="due_date" class="form-control" value="<?= $row['due_date'] ?>" required>
        </div>

        <div class="mb-3">
            <label>Status</label>
            <select name="status" class="form-control">
                <option value="active" <?= $row['status']=='active'?'selected':'' ?>>Active</option>
                <option value="inactive" <?= $row['status']=='inactive'?'selected':'' ?>>Inactive</option>
            </select>
        </div>

        <div class="mb-3">
            <label>Course</label>
            <select name="course_id" class="form-control" required>
                <option value="">-- Select Course --</option>
                <?php mysqli_data_seek($courses_q,0); while($c = mysqli_fetch_assoc($courses_q)): ?>
                    <option value="<?= $c['id'] ?>" <?= $c['id']==$row['course_id']?'selected':'' ?>>
                        <?= htmlspecialchars($c['course_name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="mb-3">
            <label>Assign To</label>
            <select name="edit_assign_mode" class="form-control mb-2">
                <option value="all" <?= $row['assign_to']=='all'?'selected':'' ?>>All Students</option>
                <option value="specific" <?= strpos($row['assign_to'],'student:')===0?'selected':'' ?>>Specific Student</option>
            </select>

            <?php
            $specific_id = (strpos($row['assign_to'],'student:')===0) ? intval(substr($row['assign_to'],8)) : '';
            if ($specific_id):
                $sres = mysqli_query($conn, "SELECT id, name, roll_no FROM students ORDER BY name");
            ?>
                <select name="edit_specific_student" class="form-control mt-2">
                    <option value="">-- Select Student --</option>
                    <?php while($s = mysqli_fetch_assoc($sres)): ?>
                        <option value="<?= $s['id'] ?>" <?= $s['id']==$specific_id?'selected':'' ?>>
                            <?= $s['name'].' ('.$s['roll_no'].')' ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            <?php endif; ?>
        </div>

        <div class="mb-3">
            <label>Existing File</label><br>
            <?php if (!empty($row['file_path']) && file_exists($upload_dir.$row['file_path'])): ?>
                <?php $ext = pathinfo($row['file_path'], PATHINFO_EXTENSION); ?>
                <?php if (in_array(strtolower($ext), ['jpg','jpeg','png','gif'])): ?>
                    <img src="<?= $upload_dir.$row['file_path'] ?>" width="150" class="mb-2"><br>
                <?php else: ?>
                    <a href="<?= $upload_dir.$row['file_path'] ?>" target="_blank">View Uploaded File</a><br>
                <?php endif; ?>
            <?php else: ?>
                No file uploaded
            <?php endif; ?>
        </div>

        <div class="mb-3">
            <label>Replace File (PDF / Image)</label>
            <input type="file" name="file" class="form-control">
            <small class="text-muted">Uploading a new file will replace the old one</small>
        </div>

        <button type="submit" class="btn btn-warning">Update Assignment</button>
        <a href="manage_assignments.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

</body>
</html>
