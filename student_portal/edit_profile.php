<?php
session_start();
require 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['student_id'])) { 
    header('Location: login.php'); 
    exit; 
}

$id = $_SESSION['student_id'];

// Fetch existing profile data
$stmt = $conn->prepare("
    SELECT name, roll_no, college, email, contact, parent_contact 
    FROM students 
    WHERE id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($name, $roll_no, $college, $email, $contact, $parent_contact);
$stmt->fetch();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Account Settings | <?= htmlspecialchars($name) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --brand-primary: #6366f1;
            --brand-bg: #f8fafc;
            --surface: #ffffff;
            --input-border: #e2e8f0;
        }

        body {
            background-color: var(--brand-bg);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #1e293b;
            padding: 40px 0;
        }

        .settings-card {
            background: var(--surface);
            border: none;
            border-radius: 24px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .profile-sidebar {
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            padding: 40px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .avatar-placeholder {
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            margin-bottom: 20px;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .form-section {
            padding: 40px;
        }

        .section-title {
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-label {
            font-weight: 600;
            font-size: 0.85rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .form-control {
            border: 2px solid var(--input-border);
            border-radius: 12px;
            padding: 12px 16px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        .input-group-text {
            background: none;
            border-right: none;
            color: #94a3b8;
            border-color: var(--input-border);
            border-radius: 12px 0 0 12px;
        }

        .input-group > .form-control {
            border-left: none;
            border-radius: 0 12px 12px 0;
        }

        .btn-primary {
            background: var(--brand-primary);
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 700;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: #4f46e5;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 600;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
            color: #1e293b;
        }

        .roll-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 5px 15px;
            border-radius: 100px;
            font-size: 0.8rem;
            margin-top: 10px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            
            <div class="settings-card shadow">
                <div class="row g-0">
                    <div class="col-md-4 profile-sidebar">
                        <div class="avatar-placeholder">
                            <i class="bi bi-person-circle"></i>
                        </div>
                        <h4 class="mb-0 fw-bold"><?php echo htmlspecialchars($name); ?></h4>
                        <p class="opacity-75 mb-0 small"><?php echo htmlspecialchars($college); ?></p>
                        <div class="roll-badge">Roll No: <?php echo htmlspecialchars($roll_no); ?></div>
                    </div>

                    <div class="col-md-8 form-section">
                        <h3 class="section-title">
                            <i class="bi bi-gear-fill text-primary"></i> 
                            Personal Information
                        </h3>

                        <form action="update_profile.php" method="post">
                            <input type="hidden" name="id" value="<?php echo $id; ?>">

                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label">Full Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                                        <input class="form-control" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label">Institute / College</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-building"></i></span>
                                        <input class="form-control" name="college" value="<?php echo htmlspecialchars($college); ?>" required>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Official Email</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Personal Contact</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-phone"></i></span>
                                        <input class="form-control" name="contact" value="<?php echo htmlspecialchars($contact); ?>" required>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Emergency / Parent Contact</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-telephone-outbound"></i></span>
                                        <input class="form-control" name="parent_contact" value="<?php echo htmlspecialchars($parent_contact); ?>">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Update Password <span class="text-lowercase fw-normal">(Optional)</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                        <input type="password" class="form-control" name="password" placeholder="••••••••">
                                    </div>
                                </div>

                                <div class="col-12 mt-4 pt-3 border-top">
                                    <div class="d-flex gap-3">
                                        <button class="btn btn-primary px-4">
                                            <i class="bi bi-check2-circle me-2"></i>Update Profile
                                        </button>
                                        <a href="dashboard.php" class="btn btn-secondary px-4">
                                            <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>