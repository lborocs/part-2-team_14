<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../actions/guard_project_access.php';

$database = new Database();
$db = $database->getConnection();
if (!$db) die("Database connection failed.");

$userId = $_SESSION['user_id'] ?? null;
$role   = $_SESSION['role'] ?? null;

$isLocal = in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'], true);
if (!$userId && $isLocal) {
    $userId = 1;
    $role   = 'manager';
}

if (!$userId) {
    http_response_code(401);
    exit("Not logged in.");
}

$projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
if ($projectId <= 0) {
    http_response_code(400);
    exit("Missing/invalid project_id in the URL.");
}

// 1) Must be on the project (member OR leader OR creator OR manager)
$access = guardProjectAccess($db, $projectId, (int)$userId);

$project = $access['project'];
$canManageProject = $access['canManageProject'];
$canCloseProject  = $access['canCloseProject'];

// =============================
// AJAX: MEMBER PROGRESS (SECURE)
// =============================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['ajax'] ?? '') === 'member_progress') {
    header('Content-Type: application/json; charset=utf-8');

    // must be manager/team_leader for this project
    if (!$canManageProject) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No permission']);
        exit;
    }

    try {
        // Only people who have tasks assigned in THIS project
        $stmt = $db->prepare("
            SELECT
                u.user_id,
                u.email,
                u.first_name,
                u.last_name,
                u.profile_picture,
                COUNT(DISTINCT ta.task_id) AS total_tasks,
                SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) AS completed_tasks
            FROM task_assignments ta
            JOIN tasks t
              ON t.task_id = ta.task_id
             AND t.project_id = :pid
            JOIN users u
              ON u.user_id = ta.user_id
            WHERE u.is_active = 1
            GROUP BY u.user_id, u.email, u.first_name, u.last_name, u.profile_picture
            ORDER BY u.first_name ASC, u.last_name ASC
        ");
        $stmt->execute([':pid' => $projectId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $people = array_map(function ($r) {
            $total = (int)$r['total_tasks'];
            $done  = (int)$r['completed_tasks'];
            $pct   = $total > 0 ? (int)round(($done / $total) * 100) : 0;

            $name = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
            if ($name === '') $name = strtolower(trim($r['email'] ?? ''));

            return [
                'user_id' => (int)$r['user_id'],
                'name' => $name,
                'email' => strtolower(trim($r['email'] ?? '')),
                'avatarUrl' => !empty($r['profile_picture']) ? $r['profile_picture'] : null,
                'total_tasks' => $total,
                'completed_tasks' => $done,
                'percent' => $pct
            ];
        }, $rows);

        echo json_encode(['success' => true, 'people' => $people]);
        exit;
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error']);
        exit;
    }
}


// =============================
// CLOSE PROJECT (AJAX) - manager-progress.php
// (same behaviour as projects.php)
// =============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['ajax'] ?? '') === 'close_project') {
    header('Content-Type: application/json; charset=utf-8');

    // Only manager can close
    if (!$canCloseProject) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No permission']);
        exit;
    }

    // Archive project
    $upd = $db->prepare("
        UPDATE projects
        SET status = 'archived'
        WHERE project_id = :pid
        LIMIT 1
    ");
    $upd->execute([':pid' => $projectId]);

    if ($upd->rowCount() < 1) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Project not found']);
        exit;
    }

    echo json_encode(['success' => true]);
    exit;
}


//  2) Must be manager/team leader for THIS project to view manager-progress.php
if (!$canManageProject) {
    http_response_code(403);
    exit("You don't have access to the manager progress view.");
}

// Fetch active users for avatars + names (same as projects.php)
$stmt = $db->prepare("
  SELECT user_id, email, first_name, last_name, role, profile_picture
  FROM users
  WHERE is_active = 1
");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$roleMap = [
    'team_member' => 'member',
    'technical_specialist' => 'specialist',
    'team_leader' => 'team_leader',
    'manager' => 'manager',
];

$simUsers = [];
foreach ($users as $u) {
    $email = strtolower(trim($u['email']));
    $fullName = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
    $avatarClass = 'avatar-' . ((int)$u['user_id'] % 4 + 1);
    $dbRole = $u['role'] ?? 'team_member';
    $jsRole = $roleMap[$dbRole] ?? 'member';

    $simUsers[$email] = [
        'name' => $fullName !== '' ? $fullName : $email,
        'role' => $jsRole,
        'avatarClass' => $avatarClass,
        'avatarUrl' => !empty($u['profile_picture']) ? $u['profile_picture'] : null,
    ];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make-It-All - Manager Progress</title>

    <link rel="stylesheet" href="../dashboard.css">
    <link rel="stylesheet" href="progress.css">
    <link rel="stylesheet" href="manager-progress.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="/favicon.png">
    <script src="https://unpkg.com/feather-icons"></script>

    <script>
        window.__USERS__ = <?= json_encode($simUsers, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        window.__PROJECT__ = <?= json_encode($project) ?>;
        window.__ROLE__ = <?= json_encode($role) ?>;
        window.__CAN_MANAGE_PROJECT__ = <?= json_encode($canManageProject) ?>;
        window.__CAN_CLOSE_PROJECT__ = <?= json_encode($canCloseProject) ?>;
    </script>

</head>

<body id="manager-progress-page">
    <div class="dashboard-container">
        <nav class="sidebar">
            <div class="nav-top">
                <div class="logo-container">
                    <img src="../logo.png" alt="Make-It-All Logo" class="logo-icon">
                </div>
                <ul class="nav-main">
                    <?php if ($role === 'manager' || $role === 'team_leader'): ?>
                        <li><a href="../home/home.php"><i data-feather="home"></i>Home</a></li>
                    <?php endif; ?>
                    <li class="active-parent"><a href="projects-overview.php"><i data-feather="folder"></i>Projects</a></li>
                    <?php if ($role === 'manager'): ?>
                        <li><a href="../employees/employee-directory.php"><i data-feather="users"></i>Employees</a></li>
                    <?php endif; ?>
                    <li><a href="../knowledge-base/knowledge-base.html"><i data-feather="book-open"></i>Knowledge Base</a></li>
                </ul>
            </div>
            <div class="nav-footer">
                <ul>
                    <li><a href="../settings.php"><i data-feather="settings"></i>Settings</a></li>
                </ul>
            </div>
        </nav>

        <main class="main-content">
            <header class="project-header">
                <div class="project-header-top">
                    <div class="breadcrumbs-title">
                        <p class="breadcrumbs">Projects > <span id="project-name-breadcrumb"><?= htmlspecialchars($project['project_name'] ?? 'Project') ?></span></p>
                        <h1 id="project-name-header"><?= htmlspecialchars($project['project_name'] ?? 'Project') ?></h1>
                    </div>
                    <?php if ($canCloseProject): ?>
                        <button class="close-project-btn" id="close-project-btn">Close Project</button>
                    <?php endif; ?>


                </div>

                <!-- ✅ Let JS fill correct links (uses __ROLE__ + __CAN_MANAGE_PROJECT__) -->
                <nav class="project-nav" id="project-nav-links"></nav>
            </header>

            <div class="manager-progress-layout">
                <div class="manager-progress-left">
                    <section class="member-progress-card">
                        <div class="member-progress-header">
                            <h2>Team Progress</h2>

                            <div class="member-progress-search-wrap">
                                <i data-feather="search"></i>
                                <input
                                    type="text"
                                    id="member-progress-search"
                                    class="member-progress-search"
                                    placeholder="Search members..."
                                    autocomplete="off" />
                            </div>
                        </div>


                        <div class="member-progress-list" id="member-progress-list">
                            <!-- JS renders rows -->
                        </div>

                        <p class="member-progress-hint" id="member-progress-hint" style="display:none;">
                            No matches found.
                        </p>
                    </section>

                </div>

                <div class="manager-progress-right">
                    <section class="deadlines-card">
                        <h2>Upcoming Deadlines</h2>
                        <div class="deadlines-list" id="deadlines-list"></div>
                    </section>

                </div>
            </div>
        </main>
    </div>
    <!-- Close Project Confirm Modal (REQUIRED for app.js close button) -->
    <div class="modal-overlay" id="close-project-modal" style="display:none;">
        <div class="modal-content" style="max-width:520px;">
            <div class="modal-header">
                <h2>Close Project</h2>
                <button type="button" class="close-btn" id="close-project-x">
                    <i data-feather="x"></i>
                </button>
            </div>

            <div class="modal-body">
                <p style="margin:0 0 8px;">Are you sure you want to close this project?</p>
                <p style="margin:0 0 16px; color:#666;">
                    This project will be moved to archives.
                </p>

                <div style="display:flex; gap:10px; justify-content:flex-end;">
                    <button type="button" class="create-post-btn" id="close-project-cancel"
                        style="background:#eee; color:#111;">
                        Cancel
                    </button>

                    <button type="button" class="create-post-btn" id="close-project-ok">
                        OK
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="../app.js"></script>
    <script>
        feather.replace();
    </script>
</body>

</html>