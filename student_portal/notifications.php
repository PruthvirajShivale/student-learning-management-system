<?php
session_start();
require "config.php";

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit;
}

$student_id = intval($_SESSION['student_id']);

$student_courses = [];
$res = mysqli_query($conn, "
    SELECT course_id 
    FROM student_courses 
    WHERE student_id='$student_id'
");

while ($row = mysqli_fetch_assoc($res)) {
    $student_courses[] = $row['course_id'];
}

$course_ids = !empty($student_courses)
    ? implode(',', array_map('intval', $student_courses))
    : '0';

$query = "
SELECT 
    n.id,
    n.title,
    n.message,
    n.created_at,
    n.course_id,
    n.assignment_id,
    n.link,
    c.course_name,
    a.title AS assignment_title,
    a.description AS assignment_description,
    a.due_date
FROM notifications n
LEFT JOIN courses c ON n.course_id = c.id
LEFT JOIN assignments a ON n.assignment_id = a.id
WHERE 
    n.student_id = '$student_id'
    AND (
        n.course_id IS NULL
        OR n.course_id IN ($course_ids)
    )
ORDER BY n.created_at DESC
";

$res = mysqli_query($conn, $query);
$notifications = [];
while ($n = mysqli_fetch_assoc($res)) {
    $notifications[] = $n;
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Premium Notifications</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">

<style>
body{
    margin:0;
    font-family:'Inter',sans-serif;
    background:#f5f7fb;
    overflow-x:hidden;
}

/* Floating particles */
canvas{
    position:fixed;
    top:0;left:0;
    z-index:-1;
}

/* Header */
.header{
    max-width:1100px;
    margin:auto;
    padding:25px 20px;
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.header h1{
    font-size:22px;
    display:flex;
    align-items:center;
    gap:10px;
}

.pulse{
    animation:pulse 2s infinite;
    color:#6366f1;
}
@keyframes pulse{
    0%{transform:scale(1);}
    50%{transform:scale(1.15);}
    100%{transform:scale(1);}
}

.badge{
    background:#6366f1;
    color:white;
    padding:6px 14px;
    border-radius:30px;
    font-weight:600;
    transition:.3s;
}

/* Filter Tabs */
.tabs{
    max-width:1100px;
    margin:auto;
    padding:0 20px 20px;
    display:flex;
    gap:15px;
    flex-wrap:wrap;
}
.tab{
    padding:8px 16px;
    border-radius:20px;
    background:white;
    border:1px solid #e5e7eb;
    cursor:pointer;
    transition:.3s;
}
.tab.active{
    background:#6366f1;
    color:white;
}

/* Cards */
.wrapper{
    max-width:1100px;
    margin:auto;
    padding:0 20px 40px;
}

.notification-card{
    background:rgba(255,255,255,0.7);
    backdrop-filter:blur(15px);
    border-radius:20px;
    padding:20px;
    margin-bottom:20px;
    box-shadow:0 10px 30px rgba(0,0,0,0.05);
    transition:.3s;
    position:relative;
}

.notification-card.unread{
    border-left:4px solid #6366f1;
}

.notification-card:hover{
    transform:translateY(-5px);
}

.title{
    font-weight:600;
    margin-bottom:6px;
}

.message{
    font-size:14px;
    color:#6b7280;
}

.meta{
    font-size:12px;
    margin-top:10px;
    color:#9ca3af;
}

.ai-badge{
    position:absolute;
    top:15px;
    right:15px;
    font-size:10px;
    padding:4px 10px;
    border-radius:20px;
    background:#eef2ff;
    color:#6366f1;
    font-weight:600;
}

/* Toggle button */
.toggle-read{
    position:absolute;
    bottom:15px;
    right:15px;
    cursor:pointer;
    font-size:12px;
    color:#6366f1;
}

@media(max-width:600px){
    .notification-card{
        padding:15px;
    }
}
</style>
</head>
<body>

<canvas id="particles"></canvas>

<div class="header">
    <h1>
        <span class="material-icons-round pulse">notifications</span>
        Notifications
    </h1>
    <div class="badge" id="liveCount"><?= count($notifications) ?></div>
</div>

<div class="tabs">
    <div class="tab active" data-filter="all">All</div>
    <div class="tab" data-filter="assignment">Assignment</div>
    <div class="tab" data-filter="course">Course</div>
    <div class="tab" data-filter="general">General</div>
</div>

<div class="wrapper">
<?php foreach ($notifications as $n): 
$type="general";
if(!empty($n['assignment_id'])) $type="assignment";
elseif(!empty($n['course_id'])) $type="course";
?>
<div class="notification-card unread" data-type="<?= $type ?>">
    <div class="ai-badge"><?= strtoupper($type) ?></div>

    <div class="title"><?= htmlspecialchars($n['title']) ?></div>
    <div class="message"><?= nl2br(htmlspecialchars($n['message'])) ?></div>

    <?php if (!empty($n['assignment_title'])): ?>
        <div style="margin-top:10px;font-size:13px;color:#ef4444;">
            Due: <?= htmlspecialchars($n['due_date']) ?>
        </div>
    <?php endif; ?>

    <div class="meta">
        <?= date("d M Y, h:i A", strtotime($n['created_at'])) ?>
        <?php if (!empty($n['course_name'])): ?>
            • <?= htmlspecialchars($n['course_name']) ?>
        <?php endif; ?>
    </div>

    <div class="toggle-read">Mark as Read</div>
</div>
<?php endforeach; ?>
</div>

<script>
// Filter system
document.querySelectorAll('.tab').forEach(tab=>{
    tab.onclick=()=>{
        document.querySelectorAll('.tab').forEach(t=>t.classList.remove('active'));
        tab.classList.add('active');
        let filter=tab.dataset.filter;
        document.querySelectorAll('.notification-card').forEach(card=>{
            card.style.display=(filter==="all"||card.dataset.type===filter)?"block":"none";
        });
    }
});

// Read/Unread toggle + animated counter
let counter=document.getElementById("liveCount");
function updateCounter(){
    let unread=document.querySelectorAll('.notification-card.unread').length;
    counter.textContent=unread;
}
updateCounter();

document.querySelectorAll('.toggle-read').forEach(btn=>{
    btn.onclick=()=>{
        let card=btn.closest('.notification-card');
        card.classList.toggle('unread');
        btn.textContent=card.classList.contains('unread')?"Mark as Read":"Mark as Unread";
        updateCounter();
    }
});

// Floating particles
const canvas=document.getElementById("particles");
const ctx=canvas.getContext("2d");
canvas.width=window.innerWidth;
canvas.height=window.innerHeight;
let particles=[];
for(let i=0;i<60;i++){
    particles.push({
        x:Math.random()*canvas.width,
        y:Math.random()*canvas.height,
        r:Math.random()*2,
        d:Math.random()*1
    });
}
function draw(){
    ctx.clearRect(0,0,canvas.width,canvas.height);
    ctx.fillStyle="rgba(99,102,241,0.2)";
    particles.forEach(p=>{
        ctx.beginPath();
        ctx.arc(p.x,p.y,p.r,0,Math.PI*2);
        ctx.fill();
    });
    update();
}
function update(){
    particles.forEach(p=>{
        p.y+=p.d;
        if(p.y>canvas.height) p.y=0;
    });
}
setInterval(draw,30);
</script>

</body>
</html>
