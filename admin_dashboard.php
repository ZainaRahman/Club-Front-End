<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");


if (!isset($_SESSION['username']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$adminName = $_SESSION['username'];
$errors    = [];
$successes = [];

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$conn = mysqli_connect('localhost', 'root', '', 'club_db');
if (!$conn) { die('DB connection failed: ' . mysqli_connect_error()); }

// Auto-create club_events table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS club_events (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(200) NOT NULL,
    event_date  DATE NOT NULL,
    month_label VARCHAR(10)  NOT NULL,
    day_label   VARCHAR(5)   NOT NULL,
    description TEXT         DEFAULT NULL,
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
)");

//Auto Create club_competition_wins table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS club_competition_wins (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    competition_name VARCHAR(200) NOT NULL,
    result           VARCHAR(100) NOT NULL,
    win_date         DATE NOT NULL,
    description      TEXT DEFAULT NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Auto-create club_publications table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS club_publications (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    title        VARCHAR(300) NOT NULL,
    pub_type     VARCHAR(50)  NOT NULL DEFAULT 'Journal',
    venue        VARCHAR(300) DEFAULT NULL,
    venue_url    VARCHAR(500) DEFAULT NULL,
    pub_year     YEAR        NOT NULL,
    topic        TEXT         DEFAULT NULL,
    bullets      TEXT         DEFAULT NULL,
    created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
)");

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_event') {
        $title = trim($_POST['event_title'] ?? '');
        $date  = trim($_POST['event_date']  ?? '');
        $desc  = trim($_POST['event_desc']  ?? '');
        if ($title === '' || $date === '') {
            $errors[] = 'Event title and date are required.';
        } else {
            $ts = strtotime($date);
            $ml = date('M', $ts); $dl = date('j', $ts);
            $s  = mysqli_prepare($conn, 'INSERT INTO club_events (title,event_date,month_label,day_label,description) VALUES (?,?,?,?,?)');
            mysqli_stmt_bind_param($s, 'sssss', $title, $date, $ml, $dl, $desc);
            if (mysqli_stmt_execute($s)) { $_SESSION['admin_flash'] = ['type'=>'success','text'=>'Event added.']; }
            else { $errors[] = 'Failed to add event.'; }
            mysqli_stmt_close($s);
        }
    }

    if ($action === 'delete_event') {
        $id = (int)($_POST['event_id'] ?? 0);
        if ($id > 0) {
            $s = mysqli_prepare($conn, 'DELETE FROM club_events WHERE id=?');
            mysqli_stmt_bind_param($s, 'i', $id);
            mysqli_stmt_execute($s); mysqli_stmt_close($s);
            $_SESSION['admin_flash'] = ['type'=>'success','text'=>'Event deleted.'];
        }
        header('Location: admin_dashboard.php#events'); exit();
    }

    if ($action === 'edit_event') {
    $id = (int)($_POST['event_id'] ?? 0);
    $title = trim($_POST['event_title'] ?? '');
    $date = trim($_POST['event_date'] ?? '');
    $desc = trim($_POST['event_desc'] ?? '');

    if ($id > 0 && $title !== '' && $date !== '') {

        $ts = strtotime($date);
        $month = date('M', $ts);
        $day = date('j', $ts);

        $s = mysqli_prepare($conn,
            "UPDATE club_events 
             SET title=?, event_date=?, month_label=?, day_label=?, description=? 
             WHERE id=?"
        );

        mysqli_stmt_bind_param(
            $s,
            "sssssi",
            $title,
            $date,
            $month,
            $day,
            $desc,
            $id
        );

        mysqli_stmt_execute($s);
        mysqli_stmt_close($s);

        $_SESSION['admin_flash'] = [
            'type' => 'success',
            'text' => 'Event updated.'
        ];
    }
}

    if ($action === 'delete_member') {
        $email = trim($_POST['member_email'] ?? '');
        if ($email !== '') {
            foreach (['member_profiles','member_skills','member_badges','member_workshops','member_events','member_projects'] as $tbl) {
                $chk = mysqli_query($conn, "SHOW TABLES LIKE '$tbl'");
                if (mysqli_num_rows($chk) > 0) {
                    $s = mysqli_prepare($conn, "DELETE FROM $tbl WHERE email=?");
                    mysqli_stmt_bind_param($s,'s',$email);
                    mysqli_stmt_execute($s); mysqli_stmt_close($s);
                }
            }
            $s = mysqli_prepare($conn, "DELETE FROM club_table WHERE email=? AND role!='admin'");
            mysqli_stmt_bind_param($s,'s',$email);
            mysqli_stmt_execute($s); mysqli_stmt_close($s);
            $_SESSION['admin_flash'] = ['type'=>'success','text'=>'Member deleted.'];
        }
        header('Location: admin_dashboard.php#members'); exit();
    }

    if ($action === 'toggle_role') {
        $email=trim($_POST['member_email']??''); $nr=trim($_POST['new_role']??'member');
        if ($email!=='' && in_array($nr,['member','admin'],true)) {
            $s=mysqli_prepare($conn,"UPDATE club_table SET role=? WHERE email=?");
            mysqli_stmt_bind_param($s,'ss',$nr,$email);
            mysqli_stmt_execute($s); mysqli_stmt_close($s);
            $_SESSION['admin_flash']=['type'=>'success','text'=>'Role updated.'];
        }
        header('Location: admin_dashboard.php#members'); exit();
    }

    if($action === 'add_competition_win') {
        $compName = trim($_POST['comp_name'] ?? '');
        $result   = trim($_POST['comp_result'] ?? '');
        $winDate  = trim($_POST['comp_date'] ?? '');
        $desc     = trim($_POST['comp_desc'] ?? '');
        if ($compName === '' || $result === '' || $winDate === '') {
            $errors[] = 'Competition name, result, and date are required.';
        } else {
            $s = mysqli_prepare($conn, 'INSERT INTO club_competition_wins (competition_name,result,win_date,description) VALUES (?,?,?,?)');
            mysqli_stmt_bind_param($s, 'ssss', $compName, $result, $winDate, $desc);
            if (mysqli_stmt_execute($s)) { $_SESSION['admin_flash'] = ['type'=>'success','text'=>'Competition win added.']; }
            else { $errors[] = 'Failed to add competition win.'; }
            mysqli_stmt_close($s);
       }
    }
    if ($action === 'delete_competition_win') {
        $id = (int)($_POST['win_id'] ?? 0);
        if ($id > 0) {
            $s = mysqli_prepare($conn, 'DELETE FROM club_competition_wins WHERE id=?');
            mysqli_stmt_bind_param($s, 'i', $id);
            mysqli_stmt_execute($s); mysqli_stmt_close($s);
            $_SESSION['admin_flash'] = ['type'=>'success','text'=>'Competition win deleted.'];
        }
        header('Location: admin_dashboard.php#competition-wins'); exit();
    }

    if ($action === 'edit_competition_win') {
    $id = $_POST['win_id'] ?? 0;
    $name = trim($_POST['comp_name'] ?? '');
    $date = trim($_POST['comp_date'] ?? '');
    $result = trim($_POST['comp_result'] ?? '');
    $desc = trim($_POST['comp_desc'] ?? '');

    if ($id > 0 && $name !== '' && $date !== '' && $result !== '') {

        $s = mysqli_prepare($conn,
            'UPDATE club_competition_wins 
             SET competition_name=?, win_date=?, result=?, description=? 
             WHERE id=?'
        );

        mysqli_stmt_bind_param($s, 'ssssi', $name, $date, $result, $desc, $id);
        mysqli_stmt_execute($s);
        mysqli_stmt_close($s);

        $_SESSION['admin_flash'] = ['type'=>'success','text'=>'Competition win updated.'];
    }
}

    // ── Publications ──────────────────────────────────────────────
    if ($action === 'add_publication') {
        $ptitle   = trim($_POST['pub_title']     ?? '');
        $ptype    = trim($_POST['pub_type']      ?? 'Journal');
        $pvenue   = trim($_POST['pub_venue']     ?? '');
        $pvurl    = trim($_POST['pub_venue_url'] ?? '');
        $pyear    = (int)($_POST['pub_year']     ?? date('Y'));
        $ptopic   = trim($_POST['pub_topic']     ?? '');
        $pbullets = trim($_POST['pub_bullets']   ?? '');
        if ($ptitle === '' || $pyear < 1900) {
            $errors[] = 'Publication title and year are required.';
        } else {
            $s = mysqli_prepare($conn, 'INSERT INTO club_publications (title,pub_type,venue,venue_url,pub_year,topic,bullets) VALUES (?,?,?,?,?,?,?)');
            mysqli_stmt_bind_param($s, 'ssssiss', $ptitle, $ptype, $pvenue, $pvurl, $pyear, $ptopic, $pbullets);
            if (mysqli_stmt_execute($s)) { $_SESSION['admin_flash'] = ['type'=>'success','text'=>'Publication added.']; }
            else { $errors[] = 'Failed to add publication: ' . mysqli_stmt_error($s); }
            mysqli_stmt_close($s);
        }
        header('Location: admin_dashboard.php#publications'); exit();
    }

    if ($action === 'delete_publication') {
        $id = (int)($_POST['pub_id'] ?? 0);
        if ($id > 0) {
            $s = mysqli_prepare($conn, 'DELETE FROM club_publications WHERE id=?');
            mysqli_stmt_bind_param($s, 'i', $id);
            mysqli_stmt_execute($s); mysqli_stmt_close($s);
            $_SESSION['admin_flash'] = ['type'=>'success','text'=>'Publication deleted.'];
        }
        header('Location: admin_dashboard.php#publications'); exit();
    }

    if ($action === 'edit_publication') {
        $id       = (int)($_POST['pub_id']       ?? 0);
        $ptitle   = trim($_POST['pub_title']     ?? '');
        $ptype    = trim($_POST['pub_type']      ?? 'Journal');
        $pvenue   = trim($_POST['pub_venue']     ?? '');
        $pvurl    = trim($_POST['pub_venue_url'] ?? '');
        $pyear    = (int)($_POST['pub_year']     ?? date('Y'));
        $ptopic   = trim($_POST['pub_topic']     ?? '');
        $pbullets = trim($_POST['pub_bullets']   ?? '');
        if ($id > 0 && $ptitle !== '' && $pyear >= 1900) {
            $s = mysqli_prepare($conn, 'UPDATE club_publications SET title=?,pub_type=?,venue=?,venue_url=?,pub_year=?,topic=?,bullets=? WHERE id=?');
            mysqli_stmt_bind_param($s, 'ssssissi', $ptitle, $ptype, $pvenue, $pvurl, $pyear, $ptopic, $pbullets, $id);
            mysqli_stmt_execute($s); mysqli_stmt_close($s);
            $_SESSION['admin_flash'] = ['type'=>'success','text'=>'Publication updated.'];
        }
        header('Location: admin_dashboard.php#publications'); exit();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
        if (!in_array($action, ['delete_event','delete_member','toggle_role','delete_competition_win','delete_publication'])) {
            header('Location: admin_dashboard.php#competitions');
            exit();
        }
    }
}

$flash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);
if ($flash && ($flash['type']??'') === 'success') $successes[] = $flash['text'];

$members=[];
$mRes=mysqli_query($conn,"SELECT username,email,role FROM club_table ORDER BY username ASC");
while($row=mysqli_fetch_assoc($mRes)) $members[]=$row;

$events=[];
$eRes=mysqli_query($conn,"SELECT * FROM club_events ORDER BY event_date ASC");
while($row=mysqli_fetch_assoc($eRes)) $events[]=$row;

$competition_wins=[];
$cRes=mysqli_query($conn,"SELECT * FROM club_competition_wins ORDER BY win_date DESC");
while($row=mysqli_fetch_assoc($cRes)) $competition_wins[]=$row;

$publications=[];
$pRes=mysqli_query($conn,"SELECT * FROM club_publications ORDER BY pub_year DESC, id DESC");
while($row=mysqli_fetch_assoc($pRes)) $publications[]=$row;

$totalMembers=count(array_filter($members,fn($m)=>$m['role']!=='admin'));
$totalAdmins =count(array_filter($members,fn($m)=>$m['role']==='admin'));
$totalEvents =count($events);
$totalWins   =count($competition_wins);
$totalPubs   =count($publications);

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>K-MiNDS | Admin Panel</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;500;600;700&display=swap');
:root {
    --bg: #f3f8fd;
    --surface: rgba(255,255,255,0.96);
    --surface2: rgba(240,248,255,0.9);
    --border: rgba(9,42,75,0.08);
    --accent: #3498db;
    --accent-dark: #092a4b;
    --accent2: #2ecc71;
    --danger: #e74c3c;
    --warn: #f39c12;
    --text: #16324f;
    --muted: #6d8096;
    --shadow: 0 18px 38px rgba(9,42,75,0.10);
}
* { margin:0; padding:0; box-sizing:border-box }
html { scroll-behavior:smooth }
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background:
        radial-gradient(circle at 8% 10%, rgba(52,152,219,0.10), transparent 28%),
        radial-gradient(circle at 90% 6%, rgba(9,42,75,0.06), transparent 24%),
        linear-gradient(180deg, #fbfdff 0%, #f3f8fd 100%);
    background-color: var(--bg);
    color: var(--text);
    min-height: 100vh;
    overflow-x: hidden;
}
body::before {
    content:'';
    position:fixed; inset:0; pointer-events:none; z-index:0;
    background-image:
        linear-gradient(rgba(9,42,75,0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(9,42,75,0.03) 1px, transparent 1px);
    background-size: 64px 64px;
    mask-image: linear-gradient(180deg, rgba(0,0,0,0.4), transparent 90%);
}
/* ── Topbar ── */
.topbar {
    position:sticky; top:0; z-index:100;
    background: rgba(9,42,75,0.96);
    backdrop-filter: blur(16px);
    border-bottom: 1px solid rgba(255,255,255,0.08);
    padding: 0 28px; height: 64px;
    display:flex; align-items:center; justify-content:space-between;
    box-shadow: 0 10px 28px rgba(9,42,75,0.14);
}
.topbar-brand { display:flex; align-items:center; gap:12px }
.logo { font-size:1.3rem; font-weight:800; color:#8fd3ff; letter-spacing:0.08em; text-transform:uppercase }
.admin-badge {
    background: linear-gradient(135deg, #3498db, #092a4b);
    color:#fff; font-size:0.7rem; font-weight:700;
    padding:3px 12px; border-radius:999px; letter-spacing:0.1em; text-transform:uppercase;
}
.topbar-right { display:flex; align-items:center; gap:12px }
.topbar-user { color:rgba(255,255,255,0.65); font-size:0.9rem }
.topbar-user strong { color:#fff }
/* ── Buttons ── */
.btn {
    display:inline-flex; align-items:center; justify-content:center;
    padding:0 18px; height:38px; border-radius:10px;
    font-family:inherit; font-weight:600; font-size:0.88rem;
    border:none; cursor:pointer;
    transition:transform 150ms ease, box-shadow 150ms ease;
    text-decoration:none;
}
.btn:hover { transform:translateY(-2px) }
.btn-ghost {
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.18);
    color: #fff;
}
.btn-ghost:hover { background: rgba(255,255,255,0.18) }
.btn-primary { background:linear-gradient(135deg,#3498db,#1a6ab1); color:#fff; box-shadow:0 8px 20px rgba(52,152,219,0.22) }
.btn-danger  { background:linear-gradient(135deg,#e74c3c,#c0392b); color:#fff; box-shadow:0 8px 20px rgba(231,76,60,0.2) }
.btn-warn    { background:linear-gradient(135deg,#f39c12,#d68910); color:#fff; box-shadow:0 8px 20px rgba(243,156,18,0.2) }
.btn-sm { height:32px; padding:0 12px; font-size:0.82rem; border-radius:8px }
/* shell */
.shell { position:relative; z-index:1; max-width:1400px; margin:0 auto; padding:28px 28px 80px }
/* Alerts */
.alert { padding:14px 18px; border-radius:12px; margin-bottom:20px; font-size:0.94rem; font-weight:500 }
.alert-success { background:rgba(46,204,113,0.1); border:1px solid rgba(46,204,113,0.25); color:#1e8449 }
.alert-error   { background:rgba(231,76,60,0.08); border:1px solid rgba(231,76,60,0.22); color:#c0392b }
/* Stat cards */
.stats-row { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:16px; margin-bottom:28px }
.stat-card {
    background: var(--surface); border:1px solid var(--border);
    border-radius:18px; padding:22px 24px;
    position:relative; overflow:hidden;
    box-shadow: var(--shadow);
}
.stat-card::before {
    content:''; position:absolute; top:-30px; right:-20px;
    width:110px; height:110px; border-radius:50%;
    background: var(--glow, rgba(52,152,219,0.10));
}
.stat-card .label { font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.12em; color:var(--muted); margin-bottom:10px }
.stat-card .value { font-size:2.6rem; font-weight:800; line-height:1; color:var(--accent) }
.stat-card.green .value { color:#27ae60 } .stat-card.green { --glow:rgba(46,204,113,0.12) }
.stat-card.yellow .value { color:#e67e22 } .stat-card.yellow { --glow:rgba(243,156,18,0.12) }
.stat-card.purple .value { color:#8e44ad } .stat-card.purple { --glow:rgba(142,68,173,0.12) }
/* Sections */
.section { background:var(--surface); border:1px solid var(--border); border-radius:22px; overflow:hidden; margin-bottom:24px; box-shadow:var(--shadow) }
.section-head { display:flex; align-items:center; justify-content:space-between; padding:20px 24px; border-bottom:1px solid var(--border); background:var(--surface2) }
.section-head h2 { font-size:1.15rem; font-weight:700; display:flex; align-items:center; gap:10px; color:var(--accent-dark) }
.section-icon { width:32px; height:32px; border-radius:9px; display:grid; place-items:center; font-size:1rem; background:rgba(52,152,219,0.10) }
.section-body { padding:24px }
/* Table */
.data-table { width:100%; border-collapse:collapse }
.data-table th { text-align:left; padding:10px 14px; font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.1em; color:var(--muted); border-bottom:1px solid var(--border) }
.data-table td { padding:12px 14px; border-bottom:1px solid rgba(9,42,75,0.05); font-size:0.92rem; vertical-align:middle; color:var(--text) }
.data-table tr:last-child td { border-bottom:none }
.data-table tr:hover td { background:rgba(52,152,219,0.03) }
/* Badges */
.role-badge { display:inline-flex; align-items:center; padding:3px 10px; border-radius:999px; font-size:0.75rem; font-weight:700; letter-spacing:0.06em; text-transform:uppercase }
.role-admin  { background:rgba(52,152,219,0.12); color:#1a6ab1; border:1px solid rgba(52,152,219,0.22) }
.role-member { background:rgba(46,204,113,0.1); color:#1e8449; border:1px solid rgba(46,204,113,0.2) }
.actions-cell { display:flex; gap:8px; flex-wrap:wrap }
/* Form */
.form-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px }
.form-group { display:flex; flex-direction:column; gap:6px }
.form-group.span2 { grid-column:1/-1 }
.form-group label { font-size:0.82rem; font-weight:600; color:var(--muted); text-transform:uppercase; letter-spacing:0.08em }
.form-group input,
.form-group textarea,
.form-group select {
    padding:11px 14px; background:rgba(248,252,255,0.9);
    border:2px solid rgba(9,42,75,0.1); border-radius:10px;
    color:var(--text); font-family:inherit; font-size:0.94rem;
    transition:border-color 150ms, box-shadow 150ms;
}
.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    outline:none; border-color:var(--accent);
    box-shadow:0 0 0 3px rgba(52,152,219,0.15);
}
.form-group textarea { min-height:90px; resize:vertical }
.form-actions { grid-column:1/-1; display:flex; gap:10px; justify-content:flex-end; margin-top:4px }
.add-form-wrap { display:none }
.add-form-wrap.open { display:block }
.divider { border:none; border-top:1px solid var(--border); margin:20px 0 }
.empty { text-align:center; padding:40px 20px; color:var(--muted); font-size:0.95rem }
/* Modal */
.modal-overlay { display:none; position:fixed; inset:0; z-index:200; background:rgba(9,42,75,0.5); backdrop-filter:blur(6px); align-items:center; justify-content:center }
.modal-overlay.open { display:flex }
.modal { background:var(--surface); border:1px solid var(--border); border-radius:20px; padding:28px; width:100%; max-width:520px; box-shadow:var(--shadow); animation:slideUp 200ms ease }
@keyframes slideUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
.modal h3 { font-size:1.2rem; font-weight:700; margin-bottom:20px; color:var(--accent-dark) }
.modal-actions { display:flex; gap:10px; justify-content:flex-end; margin-top:20px }
/* Responsive */
@media(max-width:900px){ .stats-row{grid-template-columns:1fr 1fr} .form-grid{grid-template-columns:1fr} .form-group.span2{grid-column:auto} }
@media(max-width:600px){ .shell{padding:16px 14px 60px} .topbar{padding:0 16px} .stats-row{grid-template-columns:1fr} .data-table th:nth-child(2),.data-table td:nth-child(2){display:none} }
</style>
</head>
<body>

<header class="topbar">
    <div class="topbar-brand">
        <span class="logo">K-MiNDS</span>
        <span class="admin-badge">Admin Panel</span>
    </div>
    <div class="topbar-right">
        <span class="topbar-user">Logged in as <strong><?= h($adminName) ?></strong></span>
        <a class="btn btn-ghost" href="Landing_page.php">← Landing Page</a>
        <a class="btn btn-danger" href="logout.php">Logout</a>
    </div>
</header>

<div class="shell">

<?php foreach($successes as $s): ?>
    <div class="alert alert-success">✓ <?= h($s) ?></div>
<?php endforeach; ?>
<?php foreach($errors as $e): ?>
    <div class="alert alert-error">✗ <?= h($e) ?></div>
<?php endforeach; ?>

<div class="stats-row">
    <div class="stat-card">
        <div class="label">Total Members</div>
        <div class="value"><?= $totalMembers ?></div>
    </div>
    <div class="stat-card green">
        <div class="label">Upcoming Events</div>
        <div class="value"><?= $totalEvents ?></div>
    </div>
    <div class="stat-card yellow">
        <div class="label">Admins</div>
        <div class="value"><?= $totalAdmins ?></div>
    </div>
    <div class="stat-card blue">
        <div class="label">Competition Wins</div>
        <div class="value"><?= $totalWins ?></div>
    </div>
    <div class="stat-card purple">
        <div class="label">Publications</div>
        <div class="value"><?= $totalPubs ?></div>
    </div>
</div>

<!-- EVENTS SECTION -->
<section class="section" id="events">
    <div class="section-head">
        <h2><span class="section-icon">📅</span> Upcoming Events</h2>
        <button class="btn btn-primary" onclick="toggleForm('eventForm')">+ Add Event</button>
    </div>
    <div class="section-body">
        <div class="add-form-wrap" id="eventForm">
            <form method="POST">
                <input type="hidden" name="action" value="add_event">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Event Title *</label>
                        <input type="text" name="event_title" placeholder="e.g. AI Workshop" required>
                    </div>
                    <div class="form-group">
                        <label>Event Date *</label>
                        <input type="date" name="event_date" required>
                    </div>
                    <div class="form-group span2">
                        <label>Description</label>
                        <textarea name="event_desc" placeholder="Short description..."></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-ghost" onclick="toggleForm('eventForm')">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Event</button>
                    </div>
                </div>
            </form>
            <hr class="divider">
        </div>

        <?php if(empty($events)): ?>
            <div class="empty">No events yet. Add your first event above.</div>
        <?php else: ?>
        <table class="data-table">
            <thead><tr><th>Title</th><th>Date</th><th>Description</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach($events as $ev): ?>
            <tr>
                <td><strong><?= h($ev['title']) ?></strong></td>
                <td><?= h(date('d M Y',strtotime($ev['event_date']))) ?></td>
                <td style="color:var(--muted);max-width:260px"><?= h(mb_strimwidth($ev['description']??'',0,80,'…')) ?></td>
                <td>
                    <div class="actions-cell">
                        <button class="btn btn-warn btn-sm"
                            onclick='openEditEvent(
                              <?= $ev["id"] ?>,
                              <?= json_encode($ev["title"]) ?>,
                              <?= json_encode($ev["event_date"]) ?>,
                              <?= json_encode($ev["description"] ?? "") ?>
                            )'>
                            Edit
                        </button>
                        <form method="POST" onsubmit="return confirm('Delete this event?')">
                            <input type="hidden" name="action" value="delete_event">
                            <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</section>

<section class="section" id="competitions">
    <div class="section-head">
        <h2><span class="section-icon">🏆</span> Competition Wins</h2>
        <!-- Future: Add button and form for managing competition wins -->
         <button class="btn btn-primary" onclick="toggleForm('competitionWinForm')">+ Add Competition Win</button>
    </div>
    <div class="section-body">
        <div class="add-form-wrap" id="competitionWinForm">
            <form method="POST">
                <input type="hidden" name="action" value="add_competition_win">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Competition Name *</label>
                        <input type="text" name="comp_name" placeholder="e.g. AI Workshop" required>
                    </div>
                    <div class="form-group">
                        <label>Competition Date *</label>
                        <input type="date" name="comp_date" required>
                    </div>
                    <div class="form-group">
                        <label>Competition Result *</label>
                        <input type="text" name="comp_result" placeholder="e.g. 1st Place" required>
                    </div>
                    <div class="form-group span2">
                        <label>Description</label>
                        <textarea name="comp_desc" placeholder="Short description..."></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-ghost" onclick="toggleForm('competitionWinForm')">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Competition Win</button>
                    </div>
                </div>
            </form>
            <hr class="divider">
        </div>

        <?php if(empty($competition_wins)): ?>
            <div class="empty">No competition wins yet. Add your first competition win above.</div>
        <?php else: ?>
        <table class="data-table">
            <thead><tr><th>Competition</th><th>Date</th><th>Result</th><th>Description</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach($competition_wins as $win): ?>
            <tr>
                <td><strong><?= h($win['competition_name']) ?></strong></td>
                <td><?= h(date('d M Y',strtotime($win['win_date']))) ?></td>
                <td><strong><?= h($win['result']) ?></strong></td>
                <td style="color:var(--muted);max-width:260px"><?= h(mb_strimwidth($win['description']??'',0,80,'…')) ?></td>
                <td>
                    <div class="actions-cell">
                        <button class="btn btn-warn btn-sm"
                            onclick='openEditCompetitionWin(
                                <?= $win["id"] ?>,
                                <?= json_encode($win["competition_name"]) ?>,
                                <?= json_encode($win["win_date"]) ?>,
                                <?= json_encode($win["result"]) ?>,
                                <?= json_encode($win["description"] ?? "") ?>
                            )'>
                            Edit
                        </button>
                        <form method="POST" onsubmit="return confirm('Delete this competition win?')">
                            <input type="hidden" name="action" value="delete_competition_win">
                            <input type="hidden" name="win_id" value="<?= $win['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</section>

<!-- PUBLICATIONS SECTION -->
<section class="section" id="publications">
    <div class="section-head">
        <h2><span class="section-icon">📄</span> Publications</h2>
        <button class="btn btn-primary" onclick="toggleForm('publicationForm')">+ Add Publication</button>
    </div>
    <div class="section-body">
        <div class="add-form-wrap" id="publicationForm">
            <form method="POST">
                <input type="hidden" name="action" value="add_publication">
                <div class="form-grid">
                    <div class="form-group span2">
                        <label>Paper Title *</label>
                        <input type="text" name="pub_title" placeholder="e.g. Deep Learning for Time Series Forecasting" required>
                    </div>
                    <div class="form-group">
                        <label>Type *</label>
                        <select name="pub_type">
                            <option value="Journal">Journal</option>
                            <option value="Conference">Conference</option>
                            <option value="Preprint">Preprint</option>
                            <option value="Workshop">Workshop</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Year *</label>
                        <input type="number" name="pub_year" min="1990" max="2099" value="<?= date('Y') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Venue / Journal / Conference</label>
                        <input type="text" name="pub_venue" placeholder="e.g. arXiv ML, ICML 2025">
                    </div>
                    <div class="form-group">
                        <label>Venue URL (optional)</label>
                        <input type="url" name="pub_venue_url" placeholder="https://arxiv.org/...">
                    </div>
                    <div class="form-group span2">
                        <label>Topic / Abstract (one paragraph)</label>
                        <textarea name="pub_topic" placeholder="Brief description of the paper's contribution..."></textarea>
                    </div>
                    <div class="form-group span2">
                        <label>Bullet points <small style="font-weight:400;color:var(--muted)">(one per line — shown as a list on the page)</small></label>
                        <textarea name="pub_bullets" rows="4" placeholder="Evaluated 6+ deep learning models&#10;Benchmark datasets from multiple domains&#10;Comparative performance analysis"></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-ghost" onclick="toggleForm('publicationForm')">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Publication</button>
                    </div>
                </div>
            </form>
            <hr class="divider">
        </div>

        <?php if (empty($publications)): ?>
            <div class="empty">No publications yet. Add your first one above.</div>
        <?php else: ?>
        <table class="data-table">
            <thead><tr><th>Title</th><th>Type</th><th>Year</th><th>Venue</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($publications as $pub): ?>
            <tr>
                <td><strong><?= h($pub['title']) ?></strong></td>
                <td><span class="role-badge role-member"><?= h($pub['pub_type']) ?></span></td>
                <td><?= h($pub['pub_year']) ?></td>
                <td style="color:var(--muted)"><?= h(mb_strimwidth($pub['venue']??'',0,50,'…')) ?></td>
                <td>
                    <div class="actions-cell">
                        <button class="btn btn-warn btn-sm"
                            onclick='openEditPublication(
                                <?= $pub["id"] ?>,
                                <?= json_encode($pub["title"]) ?>,
                                <?= json_encode($pub["pub_type"]) ?>,
                                <?= json_encode($pub["venue"] ?? "") ?>,
                                <?= json_encode($pub["venue_url"] ?? "") ?>,
                                <?= (int)$pub["pub_year"] ?>,
                                <?= json_encode($pub["topic"] ?? "") ?>,
                                <?= json_encode($pub["bullets"] ?? "") ?>
                            )'>
                            Edit
                        </button>
                        <form method="POST" onsubmit="return confirm('Delete this publication?')">
                            <input type="hidden" name="action" value="delete_publication">
                            <input type="hidden" name="pub_id" value="<?= $pub['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</section>

<!-- MEMBERS SECTION -->
<section class="section" id="members">
    <div class="section-head">
        <h2><span class="section-icon">👥</span> Members</h2>
    </div>
    <div class="section-body">
        <?php if(empty($members)): ?>
            <div class="empty">No members registered yet.</div>
        <?php else: ?>
        <table class="data-table">
            <thead><tr><th>Username</th><th>Email</th><th>Role</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach($members as $m): ?>
            <tr>
                <td><strong><?= h($m['username']) ?></strong></td>
                <td style="color:var(--muted)"><?= h($m['email']) ?></td>
                <td>
                    <span class="role-badge <?= $m['role']==='admin'?'role-admin':'role-member' ?>">
                        <?= h($m['role']) ?>
                    </span>
                </td>
                <td>
                    <div class="actions-cell">
                    <?php if($m['email'] !== $_SESSION['email']): ?>
                        <form method="POST">
                            <input type="hidden" name="action" value="toggle_role">
                            <input type="hidden" name="member_email" value="<?= h($m['email']) ?>">
                            <input type="hidden" name="new_role" value="<?= $m['role']==='admin'?'member':'admin' ?>">
                            <button type="submit" class="btn btn-warn btn-sm"
                                onclick="return confirm('Change role to <?= $m['role']==='admin'?'member':'admin' ?>?')">
                                <?= $m['role']==='admin'?'Demote':'Make Admin' ?>
                            </button>
                        </form>
                        <?php if($m['role']!=='admin'): ?>
                        <form method="POST">
                            <input type="hidden" name="action" value="delete_member">
                            <input type="hidden" name="member_email" value="<?= h($m['email']) ?>">
                            <button type="submit" class="btn btn-danger btn-sm"
                                onclick="return confirm('Delete <?= h(addslashes($m['username'])) ?> permanently?')">
                                Delete
                            </button>
                        </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <span style="color:var(--muted);font-size:0.82rem">You</span>
                    <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</section>

</div>

<!-- EDIT EVENT MODAL -->
<div class="modal-overlay" id="editEventModal">
    <div class="modal">
        <h3>✏️ Edit Event</h3>
        <form method="POST">
            <input type="hidden" name="action" value="edit_event">
            <input type="hidden" name="event_id" id="editEventId">
            <div class="form-grid">
                <div class="form-group">
                    <label>Event Title *</label>
                    <input type="text" name="event_title" id="editEventTitle" required>
                </div>
                <div class="form-group">
                    <label>Event Date *</label>
                    <input type="date" name="event_date" id="editEventDate" required>
                </div>
                
                <div class="form-group span2">
                    <label>Description</label>
                    <textarea name="event_desc" id="editEventDesc"></textarea>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Event</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="editCompetitionWinModal">
    <div class="modal">
        <h3>✏️ Edit Competition Win</h3>
        <form method="POST">
            <input type="hidden" name="action" value="edit_competition_win">
            <input type="hidden" name="win_id" id="editCompetitionWinId">
            <div class="form-grid">
                <div class="form-group">
                    <label>Competition Name *</label>
                    <input type="text" name="comp_name" id="editCompetitionWinName" required>
                </div>
                <div class="form-group">
                    <label>Competition Date *</label>
                    <input type="date" name="comp_date" id="editCompetitionWinDate" required>
                </div>
                <div class="form-group">
                    <label>Result</label>
                    <input type="text" name="comp_result" id="editCompetitionWinResult" required>
                </div>
                <div class="form-group span2">
                    <label>Description</label>
                    <textarea name="comp_desc" id="editCompetitionWinDesc"></textarea>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Competition Win</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="editPublicationModal">
    <div class="modal">
        <h3>✏️ Edit Publication</h3>
        <form method="POST">
            <input type="hidden" name="action" value="edit_publication">
            <input type="hidden" name="pub_id" id="editPubId">
            <div class="form-grid">
                <div class="form-group span2">
                    <label>Paper Title *</label>
                    <input type="text" name="pub_title" id="editPubTitle" required>
                </div>
                <div class="form-group">
                    <label>Type</label>
                    <select name="pub_type" id="editPubType">
                        <option value="Journal">Journal</option>
                        <option value="Conference">Conference</option>
                        <option value="Preprint">Preprint</option>
                        <option value="Workshop">Workshop</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Year *</label>
                    <input type="number" name="pub_year" id="editPubYear" min="1990" max="2099" required>
                </div>
                <div class="form-group">
                    <label>Venue / Journal / Conference</label>
                    <input type="text" name="pub_venue" id="editPubVenue">
                </div>
                <div class="form-group">
                    <label>Venue URL</label>
                    <input type="url" name="pub_venue_url" id="editPubVenueUrl">
                </div>
                <div class="form-group span2">
                    <label>Topic / Abstract</label>
                    <textarea name="pub_topic" id="editPubTopic"></textarea>
                </div>
                <div class="form-group span2">
                    <label>Bullet points <small style="font-weight:400;color:var(--muted)">(one per line)</small></label>
                    <textarea name="pub_bullets" id="editPubBullets" rows="4"></textarea>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Publication</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleForm(id){document.getElementById(id).classList.toggle('open')}
function openEditEvent(id, title, date, desc){
    document.getElementById('editEventId').value = id || '';
    document.getElementById('editEventTitle').value = title || '';
    document.getElementById('editEventDate').value = date || '';
    document.getElementById('editEventDesc').value = desc || '';
    document.getElementById('editEventModal').classList.add('open');
}
document.getElementById('editEventModal').addEventListener('click',function(e){if(e.target===this)closeModal()});
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeModal()});

function openEditCompetitionWin(id, name, date, result, desc){
    document.getElementById('editCompetitionWinId').value = id || '';
    document.getElementById('editCompetitionWinName').value = name || '';
    document.getElementById('editCompetitionWinDate').value = date || '';
    document.getElementById('editCompetitionWinResult').value = result || '';
    document.getElementById('editCompetitionWinDesc').value = desc || '';
    document.getElementById('editCompetitionWinModal').classList.add('open');
}

function openEditPublication(id, title, type, venue, venueUrl, year, topic, bullets){
    document.getElementById('editPubId').value        = id || '';
    document.getElementById('editPubTitle').value     = title || '';
    document.getElementById('editPubType').value      = type || 'Journal';
    document.getElementById('editPubVenue').value     = venue || '';
    document.getElementById('editPubVenueUrl').value  = venueUrl || '';
    document.getElementById('editPubYear').value      = year || '';
    document.getElementById('editPubTopic').value     = topic || '';
    document.getElementById('editPubBullets').value   = bullets || '';
    document.getElementById('editPublicationModal').classList.add('open');
}

function closeModal(){document.querySelectorAll('.modal-overlay').forEach(m=>m.classList.remove('open'))}
document.getElementById('editCompetitionWinModal').addEventListener('click',function(e){if(e.target===this)closeModal()});
document.getElementById('editPublicationModal').addEventListener('click',function(e){if(e.target===this)closeModal()});
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeModal()});
</script>
</body>
</html>