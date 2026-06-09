<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Redirect to login if not logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$email = $_SESSION['email'];
$errors = [];
$successMessages = [];
$dbError = null;
$profilePhotoPath = null;
$skills = [];
$badges = [];
$workshops = [];
$events = [];
$projects = [];
$recentSkills = [];
$recentBadges = [];
$recentWorkshops = [];
$recentEvents = [];
$recentProjects = [];
$stats = [
    'skills' => 0,
    'badges' => 0,
    'workshops' => 0,
    'events' => 0,
    'projects' => 0,
];

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function safe_email_folder($email)
{
    return preg_replace('/[^a-zA-Z0-9._-]+/', '_', strtolower($email));
}

function ensure_directory($path)
{
    if (!is_dir($path)) {
        mkdir($path, 0777, true);
    }
}

function to_web_path($absolutePath)
{
    return str_replace('\\', '/', substr($absolutePath, strlen(__DIR__) + 1));
}

function save_uploaded_file($fieldName, $targetDirectory, array $allowedExtensions, &$uploadError, $prefix)
{
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        $uploadError = 'The uploaded file could not be saved.';
        return false;
    }

    $originalName = $_FILES[$fieldName]['name'];
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if (!in_array($extension, $allowedExtensions, true)) {
        $uploadError = 'Unsupported file type.';
        return false;
    }

    ensure_directory($targetDirectory);

    $fileName = $prefix . '_' . date('Ymd_His') . '_' . uniqid('', true) . '.' . $extension;
    $destination = $targetDirectory . DIRECTORY_SEPARATOR . $fileName;

    if (!move_uploaded_file($_FILES[$fieldName]['tmp_name'], $destination)) {
        $uploadError = 'Unable to move the uploaded file.';
        return false;
    }

    return to_web_path($destination);
}

function ensure_tables($connection)
{
    $queries = [
        "CREATE TABLE IF NOT EXISTS member_profiles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL UNIQUE,
            username VARCHAR(255) NOT NULL,
            photo_path VARCHAR(255) DEFAULT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS member_skills (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            skill_name VARCHAR(120) NOT NULL,
            skill_level VARCHAR(60) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS member_badges (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            badge_title VARCHAR(160) NOT NULL,
            badge_note TEXT DEFAULT NULL,
            badge_date VARCHAR(40) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS member_workshops (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            workshop_title VARCHAR(160) NOT NULL,
            organizer VARCHAR(160) DEFAULT NULL,
            completed_on VARCHAR(40) DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS member_events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            event_title VARCHAR(160) NOT NULL,
            role_name VARCHAR(120) DEFAULT NULL,
            joined_on VARCHAR(40) DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS member_projects (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            project_title VARCHAR(160) NOT NULL,
            project_description TEXT DEFAULT NULL,
            project_url VARCHAR(255) DEFAULT NULL,
            project_file VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )"
    ];

    foreach ($queries as $query) {
        if (!mysqli_query($connection, $query)) {
            return 'Schema setup failed: ' . mysqli_error($connection);
        }
    }

    return null;
}

function count_rows_for_email($connection, $table, $email)
{
    $statement = mysqli_prepare($connection, "SELECT COUNT(*) AS total FROM {$table} WHERE email = ?");
    if (!$statement) {
        return 0;
    }

    mysqli_stmt_bind_param($statement, 's', $email);
    mysqli_stmt_execute($statement);
    $result = mysqli_stmt_get_result($statement);
    $count = 0;

    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $count = (int) ($row['total'] ?? 0);
    }

    mysqli_stmt_close($statement);
    return $count;
}

function fetch_rows_for_email($connection, $table, $email, $orderBy = 'created_at DESC', $limit = 4)
{
    $rows = [];
    $query = "SELECT * FROM {$table} WHERE email = ? ORDER BY {$orderBy} LIMIT {$limit}";
    $statement = mysqli_prepare($connection, $query);

    if (!$statement) {
        return $rows;
    }

    mysqli_stmt_bind_param($statement, 's', $email);
    mysqli_stmt_execute($statement);
    $result = mysqli_stmt_get_result($statement);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
    }

    mysqli_stmt_close($statement);
    return $rows;
}

$connection = mysqli_connect('localhost', 'root', '', 'club_db');

if (!$connection) {
    $dbError = 'Connection Failed: ' . mysqli_connect_error();
} else {
    $dbError = ensure_tables($connection);

    if (!$dbError) {
        $profileDirectory = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'member-photos' . DIRECTORY_SEPARATOR . safe_email_folder($email);
        $projectDirectory = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'member-projects' . DIRECTORY_SEPARATOR . safe_email_folder($email);
        ensure_directory($profileDirectory);
        ensure_directory($projectDirectory);

        $profileSeed = mysqli_prepare($connection, 'INSERT INTO member_profiles (email, username) VALUES (?, ?) ON DUPLICATE KEY UPDATE username = VALUES(username)');
        if ($profileSeed) {
            mysqli_stmt_bind_param($profileSeed, 'ss', $email, $username);
            mysqli_stmt_execute($profileSeed);
            mysqli_stmt_close($profileSeed);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            $action = $_POST['action'];
            $actionError = null;

            if ($action === 'update_photo') {
                $photoPath = save_uploaded_file('profile_photo', $profileDirectory, ['jpg', 'jpeg', 'png', 'webp'], $actionError, 'profile');

                if ($photoPath === false) {
                    $errors[] = $actionError;
                } elseif ($photoPath === null) {
                    $errors[] = 'Please choose a profile photo before saving.';
                } else {
                    $statement = mysqli_prepare($connection, 'UPDATE member_profiles SET photo_path = ? WHERE email = ?');
                    if ($statement) {
                        mysqli_stmt_bind_param($statement, 'ss', $photoPath, $email);
                        mysqli_stmt_execute($statement);
                        mysqli_stmt_close($statement);
                        $_SESSION['dashboard_flash'] = ['type' => 'success', 'text' => 'Profile photo updated successfully.'];
                        header('Location: member_dashboard.php');
                        exit();
                    }
                    $errors[] = 'Unable to update profile photo.';
                }
            } elseif ($action === 'add_skill') {
                $skillName = trim($_POST['skill_name'] ?? '');
                $skillLevel = trim($_POST['skill_level'] ?? '');

                if ($skillName === '') {
                    $errors[] = 'Skill name cannot be empty.';
                } else {
                    $statement = mysqli_prepare($connection, 'INSERT INTO member_skills (email, skill_name, skill_level) VALUES (?, ?, ?)');
                    if ($statement) {
                        mysqli_stmt_bind_param($statement, 'sss', $email, $skillName, $skillLevel);
                        mysqli_stmt_execute($statement);
                        mysqli_stmt_close($statement);
                        $_SESSION['dashboard_flash'] = ['type' => 'success', 'text' => 'Skill added to your dashboard.'];
                        header('Location: member_dashboard.php#skills');
                        exit();
                    }
                    $errors[] = 'Unable to add skill.';
                }
            } elseif ($action === 'add_badge') {
                $badgeTitle = trim($_POST['badge_title'] ?? '');
                $badgeNote = trim($_POST['badge_note'] ?? '');
                $badgeDate = trim($_POST['badge_date'] ?? '');

                if ($badgeTitle === '') {
                    $errors[] = 'Badge title cannot be empty.';
                } else {
                    $statement = mysqli_prepare($connection, 'INSERT INTO member_badges (email, badge_title, badge_note, badge_date) VALUES (?, ?, ?, ?)');
                    if ($statement) {
                        mysqli_stmt_bind_param($statement, 'ssss', $email, $badgeTitle, $badgeNote, $badgeDate);
                        mysqli_stmt_execute($statement);
                        mysqli_stmt_close($statement);
                        $_SESSION['dashboard_flash'] = ['type' => 'success', 'text' => 'Badge saved successfully.'];
                        header('Location: member_dashboard.php#badges');
                        exit();
                    }
                    $errors[] = 'Unable to add badge.';
                }
            } elseif ($action === 'add_workshop') {
                $workshopTitle = trim($_POST['workshop_title'] ?? '');
                $organizer = trim($_POST['workshop_organizer'] ?? '');
                $completedOn = trim($_POST['workshop_date'] ?? '');
                $notes = trim($_POST['workshop_notes'] ?? '');

                if ($workshopTitle === '') {
                    $errors[] = 'Workshop title cannot be empty.';
                } else {
                    $statement = mysqli_prepare($connection, 'INSERT INTO member_workshops (email, workshop_title, organizer, completed_on, notes) VALUES (?, ?, ?, ?, ?)');
                    if ($statement) {
                        mysqli_stmt_bind_param($statement, 'sssss', $email, $workshopTitle, $organizer, $completedOn, $notes);
                        mysqli_stmt_execute($statement);
                        mysqli_stmt_close($statement);
                        $_SESSION['dashboard_flash'] = ['type' => 'success', 'text' => 'Workshop recorded successfully.'];
                        header('Location: member_dashboard.php#workshops');
                        exit();
                    }
                    $errors[] = 'Unable to add workshop.';
                }
            } elseif ($action === 'add_event') {
                $eventTitle = trim($_POST['event_title'] ?? '');
                $eventRole = trim($_POST['event_role'] ?? '');
                $joinedOn = trim($_POST['event_date'] ?? '');
                $eventNotes = trim($_POST['event_notes'] ?? '');

                if ($eventTitle === '') {
                    $errors[] = 'Event title cannot be empty.';
                } else {
                    $statement = mysqli_prepare($connection, 'INSERT INTO member_events (email, event_title, role_name, joined_on, notes) VALUES (?, ?, ?, ?, ?)');
                    if ($statement) {
                        mysqli_stmt_bind_param($statement, 'sssss', $email, $eventTitle, $eventRole, $joinedOn, $eventNotes);
                        mysqli_stmt_execute($statement);
                        mysqli_stmt_close($statement);
                        $_SESSION['dashboard_flash'] = ['type' => 'success', 'text' => 'Event added successfully.'];
                        header('Location: member_dashboard.php#events');
                        exit();
                    }
                    $errors[] = 'Unable to add event.';
                }
            } elseif ($action === 'add_project') {
                $projectTitle = trim($_POST['project_title'] ?? '');
                $projectDescription = trim($_POST['project_description'] ?? '');
                $projectUrl = trim($_POST['project_url'] ?? '');
                $projectFile = save_uploaded_file('project_file', $projectDirectory, ['zip', 'pdf', 'png', 'jpg', 'jpeg', 'webp'], $actionError, 'project');

                if ($projectFile === false) {
                    $errors[] = $actionError;
                } elseif ($projectTitle === '') {
                    $errors[] = 'Project title cannot be empty.';
                } else {
                    $statement = mysqli_prepare($connection, 'INSERT INTO member_projects (email, project_title, project_description, project_url, project_file) VALUES (?, ?, ?, ?, ?)');
                    if ($statement) {
                        mysqli_stmt_bind_param($statement, 'sssss', $email, $projectTitle, $projectDescription, $projectUrl, $projectFile);
                        mysqli_stmt_execute($statement);
                        mysqli_stmt_close($statement);
                        $_SESSION['dashboard_flash'] = ['type' => 'success', 'text' => 'Project uploaded successfully.'];
                        header('Location: member_dashboard.php#projects');
                        exit();
                    }
                    $errors[] = 'Unable to upload project.';
                }
            }
        }

        $profileStatement = mysqli_prepare($connection, 'SELECT photo_path FROM member_profiles WHERE email = ? LIMIT 1');
        if ($profileStatement) {
            mysqli_stmt_bind_param($profileStatement, 's', $email);
            mysqli_stmt_execute($profileStatement);
            $profileResult = mysqli_stmt_get_result($profileStatement);
            if ($profileResult && ($profileRow = mysqli_fetch_assoc($profileResult))) {
                $profilePhotoPath = $profileRow['photo_path'] ?? null;
            }
            mysqli_stmt_close($profileStatement);
        }

        $stats['skills'] = count_rows_for_email($connection, 'member_skills', $email);
        $stats['badges'] = count_rows_for_email($connection, 'member_badges', $email);
        $stats['workshops'] = count_rows_for_email($connection, 'member_workshops', $email);
        $stats['events'] = count_rows_for_email($connection, 'member_events', $email);
        $stats['projects'] = count_rows_for_email($connection, 'member_projects', $email);

        $skills = fetch_rows_for_email($connection, 'member_skills', $email, 'created_at DESC', 12);
        $badges = fetch_rows_for_email($connection, 'member_badges', $email, 'created_at DESC', 12);
        $workshops = fetch_rows_for_email($connection, 'member_workshops', $email, 'created_at DESC', 12);
        $events = fetch_rows_for_email($connection, 'member_events', $email, 'created_at DESC', 12);
        $projects = fetch_rows_for_email($connection, 'member_projects', $email, 'created_at DESC', 12);
        $recentSkills = array_slice($skills, 0, 3);
        $recentBadges = array_slice($badges, 0, 3);
        $recentWorkshops = array_slice($workshops, 0, 3);
        $recentEvents = array_slice($events, 0, 3);
        $recentProjects = array_slice($projects, 0, 3);
    } else {
        $errors[] = $dbError;
    }
}

$flash = $_SESSION['dashboard_flash'] ?? null;
unset($_SESSION['dashboard_flash']);

if ($flash) {
    if (($flash['type'] ?? '') === 'success') {
        $successMessages[] = $flash['text'] ?? 'Saved successfully.';
    } else {
        $errors[] = $flash['text'] ?? 'Something went wrong.';
    }
}

if (isset($_GET['login_success']) && $_GET['login_success'] === 'true') {
    $successMessages[] = 'Login successful. Welcome to your dashboard.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>K-MiNDS | Member Dashboard</title>
    <link rel="stylesheet" href="member_dashboard.css">
</head>
<body class="member-dashboard-page">
    <div class="dashboard-shell">
        <header class="dashboard-topbar">
            <div class="brand-lockup">
                <span class="brand-mark">K-MiNDS</span>
                <span class="brand-subtitle">Member dashboard</span>
            </div>
            <div class="topbar-actions">
                <a class="pill-link" href="Landing_page.php">Home</a>
                <a class="pill-link pill-cta" href="#uploadHub">Add content</a>
                <a class="pill-link pill-danger" href="logout.php">Logout</a>
            </div>
        </header>

        <main>
            <section class="hero-panel reveal-item" data-reveal>
                <div class="hero-copy">
                    <p class="eyebrow">Personal workspace</p>
                    <h1>Welcome back, <?php echo h($username); ?>.</h1>
                    <p class="hero-description">Your saved profile photo and achievements appear first, while uploads stay tucked behind one clean action button. Everything on this dashboard is tied to your login email.</p>
                    <div class="hero-actions">
                        <button class="hero-btn primary" type="button" id="toggleUploadHub">Add content</button>
                        <a class="hero-btn secondary" href="change_password.php">Change password</a>
                    </div>
                </div>

                <div class="hero-stats-grid">
                    <div class="stat-tile">
                        <strong><?php echo (int) $stats['skills']; ?></strong>
                        <span>Skills</span>
                    </div>
                    <div class="stat-tile">
                        <strong><?php echo (int) $stats['badges']; ?></strong>
                        <span>Badges</span>
                    </div>
                    <div class="stat-tile">
                        <strong><?php echo (int) $stats['workshops']; ?></strong>
                        <span>Workshops</span>
                    </div>
                    <div class="stat-tile">
                        <strong><?php echo (int) $stats['events']; ?></strong>
                        <span>Events</span>
                    </div>
                    <div class="stat-tile stat-tile-wide">
                        <strong><?php echo (int) $stats['projects']; ?></strong>
                        <span>Uploaded projects</span>
                    </div>
                </div>
            </section>

            <section class="dashboard-grid dashboard-overview-grid">
                <article class="dashboard-card profile-card reveal-item" data-reveal>
                    <div class="card-head">
                        <div>
                            <p class="card-kicker">Profile photo upload</p>
                            <h2>Your profile</h2>
                        </div>
                    </div>

                    <div class="profile-visual">
                        <?php if (!empty($profilePhotoPath)): ?>
                            <img id="profilePhotoPreview" src="<?php echo h($profilePhotoPath); ?>" alt="Profile photo for <?php echo h($username); ?>">
                        <?php else: ?>
                            <div id="profilePhotoPreview" class="profile-placeholder"><?php echo h(strtoupper(substr($username, 0, 1))); ?></div>
                        <?php endif; ?>
                    </div>

                    <p class="summary-note">This is the photo shown in your member workspace.</p>
                </article>

                <article class="dashboard-card reveal-item" data-reveal>
                    <div class="card-head">
                        <div>
                            <p class="card-kicker">Skills</p>
                            <h2>Recent skills</h2>
                        </div>
                    </div>
                    <div class="stack-list compact-list">
                        <?php if (!empty($recentSkills)): ?>
                            <?php foreach ($recentSkills as $skill): ?>
                                <div class="list-card">
                                    <strong><?php echo h($skill['skill_name']); ?></strong>
                                    <span><?php echo h($skill['skill_level'] ?: 'No level added'); ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="empty-state">No skills added yet.</p>
                        <?php endif; ?>
                    </div>
                </article>

                <article class="dashboard-card reveal-item" data-reveal>
                    <div class="card-head">
                        <div>
                            <p class="card-kicker">Badges</p>
                            <h2>Recent badges</h2>
                        </div>
                    </div>
                    <div class="stack-list compact-list">
                        <?php if (!empty($recentBadges)): ?>
                            <?php foreach ($recentBadges as $badge): ?>
                                <div class="list-card">
                                    <strong><?php echo h($badge['badge_title']); ?></strong>
                                    <span><?php echo h($badge['badge_date'] ?: 'No date added'); ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="empty-state">No badges added yet.</p>
                        <?php endif; ?>
                    </div>
                </article>

                <article class="dashboard-card reveal-item" data-reveal>
                    <div class="card-head">
                        <div>
                            <p class="card-kicker">Workshops</p>
                            <h2>Recent workshops</h2>
                        </div>
                    </div>
                    <div class="stack-list compact-list">
                        <?php if (!empty($recentWorkshops)): ?>
                            <?php foreach ($recentWorkshops as $workshop): ?>
                                <div class="list-card">
                                    <strong><?php echo h($workshop['workshop_title']); ?></strong>
                                    <span><?php echo h($workshop['completed_on'] ?: 'No date added'); ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="empty-state">No workshops added yet.</p>
                        <?php endif; ?>
                    </div>
                </article>

                <article class="dashboard-card reveal-item" data-reveal>
                    <div class="card-head">
                        <div>
                            <p class="card-kicker">Events</p>
                            <h2>Recent events</h2>
                        </div>
                    </div>
                    <div class="stack-list compact-list">
                        <?php if (!empty($recentEvents)): ?>
                            <?php foreach ($recentEvents as $event): ?>
                                <div class="list-card">
                                    <strong><?php echo h($event['event_title']); ?></strong>
                                    <span><?php echo h($event['joined_on'] ?: 'No date added'); ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="empty-state">No events added yet.</p>
                        <?php endif; ?>
                    </div>
                </article>

                <article class="dashboard-card reveal-item" data-reveal>
                    <div class="card-head">
                        <div>
                            <p class="card-kicker">Projects</p>
                            <h2>Recent uploads</h2>
                        </div>
                    </div>
                    <div class="stack-list compact-list">
                        <?php if (!empty($recentProjects)): ?>
                            <?php foreach ($recentProjects as $project): ?>
                                <div class="list-card">
                                    <strong><?php echo h($project['project_title']); ?></strong>
                                    <span><?php echo h(date('M d, Y', strtotime($project['created_at']))); ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="empty-state">No projects uploaded yet.</p>
                        <?php endif; ?>
                    </div>
                </article>
            </section>

            <?php if (!empty($successMessages)): ?>
                <section class="notice-box success reveal-item" data-reveal>
                    <?php foreach ($successMessages as $message): ?>
                        <p><?php echo h($message); ?></p>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <section class="notice-box error reveal-item" data-reveal>
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo h($error); ?></p>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>

            <section id="uploadHub" class="upload-hub reveal-item" data-reveal>
                <div class="upload-hub-header">
                    <div>
                        <p class="card-kicker">Upload center</p>
                        <h2>Add new content when you need it</h2>
                        <p class="upload-hub-note">Use the button below to open the upload tools for a specific item. Your saved content stays visible above this section.</p>
                    </div>
                    <button class="hero-btn primary" type="button" id="toggleUploadForms">Open upload tools</button>
                </div>

                <div class="upload-forms" id="uploadForms">
                    <div class="upload-grid">
                        <article class="dashboard-card upload-card">
                            <div class="card-head">
                                <div>
                                    <p class="card-kicker">Profile photo upload</p>
                                    <h2>Update photo</h2>
                                </div>
                            </div>
                            <form class="stack-form" action="member_dashboard.php" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="update_photo">
                                <label for="profile_photo">Choose a photo</label>
                                <input type="file" id="profile_photo" name="profile_photo" accept="image/png,image/jpeg,image/jpg,image/webp" required>
                                <button type="submit" class="form-btn">Save photo</button>
                            </form>
                        </article>

                        <article id="skills" class="dashboard-card upload-card">
                            <div class="card-head">
                                <div>
                                    <p class="card-kicker">Skills section</p>
                                    <h2>Add a skill</h2>
                                </div>
                            </div>
                            <form class="stack-form compact" action="member_dashboard.php" method="POST">
                                <input type="hidden" name="action" value="add_skill">
                                <input type="text" name="skill_name" placeholder="Skill name, e.g. Python" required>
                                <input type="text" name="skill_level" placeholder="Skill level, e.g. Intermediate">
                                <button type="submit" class="form-btn">Add skill</button>
                            </form>
                        </article>

                        <article id="badges" class="dashboard-card upload-card">
                            <div class="card-head">
                                <div>
                                    <p class="card-kicker">Badges</p>
                                    <h2>Add a badge</h2>
                                </div>
                            </div>
                            <form class="stack-form compact" action="member_dashboard.php" method="POST">
                                <input type="hidden" name="action" value="add_badge">
                                <input type="text" name="badge_title" placeholder="Badge title" required>
                                <input type="text" name="badge_date" placeholder="Date earned, e.g. 2026-05-25">
                                <textarea name="badge_note" rows="3" placeholder="Short badge note"></textarea>
                                <button type="submit" class="form-btn">Add badge</button>
                            </form>
                        </article>

                        <article id="workshops" class="dashboard-card upload-card">
                            <div class="card-head">
                                <div>
                                    <p class="card-kicker">Completed workshops</p>
                                    <h2>Add a workshop</h2>
                                </div>
                            </div>
                            <form class="stack-form compact" action="member_dashboard.php" method="POST">
                                <input type="hidden" name="action" value="add_workshop">
                                <input type="text" name="workshop_title" placeholder="Workshop title" required>
                                <input type="text" name="workshop_organizer" placeholder="Organizer">
                                <input type="text" name="workshop_date" placeholder="Date completed, e.g. 2026-05-25">
                                <textarea name="workshop_notes" rows="3" placeholder="What you learned"></textarea>
                                <button type="submit" class="form-btn">Add workshop</button>
                            </form>
                        </article>

                        <article id="events" class="dashboard-card upload-card">
                            <div class="card-head">
                                <div>
                                    <p class="card-kicker">Joined events</p>
                                    <h2>Add an event</h2>
                                </div>
                            </div>
                            <form class="stack-form compact" action="member_dashboard.php" method="POST">
                                <input type="hidden" name="action" value="add_event">
                                <input type="text" name="event_title" placeholder="Event title" required>
                                <input type="text" name="event_role" placeholder="Your role in the event">
                                <input type="text" name="event_date" placeholder="Joined on, e.g. 2026-05-25">
                                <textarea name="event_notes" rows="3" placeholder="Event notes"></textarea>
                                <button type="submit" class="form-btn">Add event</button>
                            </form>
                        </article>

                        <article id="projects" class="dashboard-card upload-card full-width">
                            <div class="card-head">
                                <div>
                                    <p class="card-kicker">Uploaded projects</p>
                                    <h2>Upload a project</h2>
                                </div>
                            </div>
                            <form class="stack-form compact project-form" action="member_dashboard.php" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="add_project">
                                <input type="text" name="project_title" placeholder="Project title" required>
                                <input type="url" name="project_url" placeholder="Project link or demo URL">
                                <textarea name="project_description" rows="4" placeholder="Project summary"></textarea>
                                <label for="project_file">Attach a file</label>
                                <input type="file" id="project_file" name="project_file" accept=".zip,.pdf,.png,.jpg,.jpeg,.webp">
                                <button type="submit" class="form-btn">Upload project</button>
                            </form>
                        </article>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script src="member_dashboard.js"></script>
</body>
</html>
