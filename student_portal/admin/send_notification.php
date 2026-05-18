<?php
session_start();
require "../config.php";

if (!isset($_SESSION['admin_email'])) {
    header("Location: ../login.php");
    exit();
}

// Fetch all courses for teacher selection
$courses = $conn->query("SELECT id, course_name FROM courses ORDER BY course_name ASC");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['send_notification'])) {

    $title = trim($_POST['title']);
    $message = trim($_POST['message']);
    $course_id = intval($_POST['course_id']); // 0 for all students
    $link = NULL;

    // Handle attachment upload
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
        $filename = time() . "_" . basename($_FILES['attachment']['name']);
        $target = "../uploads/notifications/" . $filename;
        if (!is_dir("../uploads/notifications/")) mkdir("../uploads/notifications/", 0777, true);
        move_uploaded_file($_FILES['attachment']['tmp_name'], $target);
        $link = "uploads/notifications/" . $filename;
    }

    // Determine recipients
   if ($course_id == 0) {
    // All students
    $students_res = $conn->query("SELECT id FROM students");
} else {
    // Only valid students using JOIN
    $stmt_students = $conn->prepare("
        SELECT s.id 
        FROM students s
        INNER JOIN student_courses sc ON s.id = sc.student_id
        WHERE sc.course_id = ?
    ");
    $stmt_students->bind_param("i", $course_id);
    $stmt_students->execute();
    $students_res = $stmt_students->get_result();
    $stmt_students->close();
}

    // Insert notification for each student
    while ($stu = $students_res->fetch_assoc()) {
        $student_id = $stu['id'] ?? $stu['student_id'];
        $course_for_db = ($course_id == 0) ? NULL : $course_id; 

        $stmt = $conn->prepare("
            INSERT INTO notifications (student_id, title, message, link, course_id, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("isssi", $student_id, $title, $message, $link, $course_for_db);
        $stmt->execute();
        $stmt->close();
    }

    echo "<script>alert('Notification sent successfully');</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Communications</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Tailwind CSS (CDN for real-world modern styling) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Material Symbols (Modern Outlined Icons) -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet" />
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: { primary: '#0f172a', accent: '#3b82f6' }
                }
            }
        }
    </script>
    <style>
        /* Custom scrollbar for text area */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        /* Remove default focus outlines but keep accessible focus */
        input:focus, textarea:focus, select:focus { outline: none; box-shadow: none; }
    </style>
</head>
<body class="bg-[#f8fafc] text-slate-800 h-screen flex overflow-hidden">

    <!-- SIDEBAR (Visual Context for Real System) -->
    <aside class="w-64 bg-primary text-slate-300 flex flex-col transition-all duration-300 hidden md:flex">
        <div class="h-16 flex items-center px-6 border-b border-slate-800">
            <span class="material-symbols-outlined text-accent mr-3">school</span>
            <span class="text-white font-semibold tracking-wide">EduPortal</span>
        </div>
        <nav class="flex-1 px-4 py-6 space-y-2">
            <a href="#" class="flex items-center px-3 py-2.5 rounded-lg hover:bg-slate-800 transition-colors">
                <span class="material-symbols-outlined mr-3 text-sm">dashboard</span> Dashboard
            </a>
            <a href="#" class="flex items-center px-3 py-2.5 rounded-lg hover:bg-slate-800 transition-colors">
                <span class="material-symbols-outlined mr-3 text-sm">groups</span> Students
            </a>
            <a href="#" class="flex items-center px-3 py-2.5 rounded-lg bg-accent/10 text-accent font-medium transition-colors">
                <span class="material-symbols-outlined mr-3 text-sm">campaign</span> Announcements
            </a>
            <a href="#" class="flex items-center px-3 py-2.5 rounded-lg hover:bg-slate-800 transition-colors">
                <span class="material-symbols-outlined mr-3 text-sm">settings</span> Settings
            </a>
        </nav>
        <div class="p-4 border-t border-slate-800">
            <div class="flex items-center">
                <div class="w-8 h-8 rounded-full bg-slate-700 flex items-center justify-center text-white font-bold text-sm">A</div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-white">System Admin</p>
                    <p class="text-xs text-slate-500">admin@eduportal.com</p>
                </div>
            </div>
        </div>
    </aside>

    <!-- MAIN CONTENT AREA -->
    <main class="flex-1 flex flex-col h-full overflow-hidden">
        
        <!-- TOP HEADER -->
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-8 z-10">
            <h1 class="text-lg font-semibold text-slate-800">New Announcement</h1>
            <div class="flex items-center gap-4 text-slate-500">
                <button class="hover:text-slate-800"><span class="material-symbols-outlined">help</span></button>
                <button class="hover:text-slate-800"><span class="material-symbols-outlined">notifications</span></button>
            </div>
        </header>

        <!-- WORKSPACE / FORM AREA -->
        <div class="flex-1 overflow-y-auto p-4 md:p-8">
            <div class="max-w-4xl mx-auto">
                
                <!-- Notice Form Container -->
                <form method="POST" enctype="multipart/form-data" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden flex flex-col h-[calc(100vh-140px)] min-h-[500px]">
                    
                    <!-- Header Fields (To & Subject) -->
                    <div class="border-b border-slate-100 px-6 py-3 flex items-center gap-4 group">
                        <label class="text-slate-400 font-medium text-sm w-16 uppercase tracking-wider">To</label>
                        <div class="flex-1 flex items-center bg-slate-50 px-3 py-1.5 rounded-lg border border-transparent group-focus-within:border-accent group-focus-within:bg-white transition-colors">
                            <span class="material-symbols-outlined text-slate-400 text-[18px] mr-2">group</span>
                            <select name="course_id" class="w-full bg-transparent text-slate-700 font-medium text-sm cursor-pointer appearance-none">
                                <option value="0">Entire Campus (All Students)</option>
                                <?php while($c = $courses->fetch_assoc()): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['course_name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <div class="border-b border-slate-100 px-6 py-4 flex items-center gap-4">
                        <label class="text-slate-400 font-medium text-sm w-16 uppercase tracking-wider">Subject</label>
                        <input type="text" name="title" required placeholder="e.g., Important update regarding exams..." 
                               class="flex-1 text-slate-800 font-semibold text-lg placeholder-slate-300 bg-transparent">
                    </div>

                    <!-- WYSIWYG Toolbar (Visual Only for realism) -->
                    <div class="px-6 py-2 bg-slate-50 border-b border-slate-100 flex items-center gap-1 text-slate-500">
                        <button type="button" class="p-1.5 rounded hover:bg-slate-200 transition"><span class="material-symbols-outlined text-[18px]">format_bold</span></button>
                        <button type="button" class="p-1.5 rounded hover:bg-slate-200 transition"><span class="material-symbols-outlined text-[18px]">format_italic</span></button>
                        <button type="button" class="p-1.5 rounded hover:bg-slate-200 transition"><span class="material-symbols-outlined text-[18px]">format_underlined</span></button>
                        <div class="w-px h-4 bg-slate-300 mx-2"></div>
                        <button type="button" class="p-1.5 rounded hover:bg-slate-200 transition"><span class="material-symbols-outlined text-[18px]">format_list_bulleted</span></button>
                        <button type="button" class="p-1.5 rounded hover:bg-slate-200 transition"><span class="material-symbols-outlined text-[18px]">link</span></button>
                    </div>

                    <!-- Message Body -->
                    <div class="flex-1 p-6 flex flex-col relative">
                        <textarea name="message" required placeholder="Write your announcement here..." 
                                  class="w-full h-full resize-none text-slate-700 leading-relaxed bg-transparent"></textarea>
                    </div>

                    <!-- Footer / Actions -->
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                        
                        <!-- File Attachment -->
                        <div class="relative flex items-center">
                            <input type="file" name="attachment" id="file-upload" class="hidden" accept=".pdf,.doc,.docx,.jpg,.png">
                            <label for="file-upload" class="cursor-pointer flex items-center gap-2 text-slate-600 bg-white border border-slate-300 px-4 py-2 rounded-full text-sm font-medium hover:bg-slate-100 hover:text-slate-800 transition-all shadow-sm">
                                <span class="material-symbols-outlined text-[20px]">attach_file</span>
                                <span id="file-name">Attach File</span>
                            </label>
                        </div>

                        <!-- Send Button -->
                        <button type="submit" name="send_notification" class="bg-accent hover:bg-blue-600 text-white px-6 py-2.5 rounded-full font-medium text-sm flex items-center gap-2 shadow-md shadow-blue-500/30 transition-all active:scale-95">
                            Send Announcement
                            <span class="material-symbols-outlined text-[18px]">send</span>
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </main>

    <!-- Script to handle file name display -->
    <script>
        document.getElementById('file-upload').addEventListener('change', function(e) {
            const fileNameSpan = document.getElementById('file-name');
            if (e.target.files.length > 0) {
                const name = e.target.files[0].name;
                fileNameSpan.textContent = name.length > 25 ? name.substring(0, 25) + '...' : name;
                fileNameSpan.classList.add('text-accent');
            } else {
                fileNameSpan.textContent = 'Attach File';
                fileNameSpan.classList.remove('text-accent');
            }
        });
    </script>
</body>
</html>