<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

$database = new Database();
$db = $database->getConnection();
if (!$db) die("Database connection failed.");

$userId = $_SESSION['user_id'] ?? null;
$role   = $_SESSION['role'] ?? null;
$email  = strtolower(trim($_SESSION['email'] ?? ''));

if (!$userId) {
    http_response_code(401);
    exit("Not logged in.");
}

$projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
if ($projectId <= 0) exit("Missing/invalid project_id in the URL.");

// -----------------------------
// Project access check
// Manager OR Team leader of this project OR member (optional)
// But since this is manager-progress, typically require manager/team_leader
// -----------------------------
$stmt = $db->prepare("
  SELECT 
    p.project_id,
    p.project_name,
    p.description,
    p.status,
    p.deadline,
    p.team_leader_id,
    p.completion_percentage,
    CASE WHEN p.team_leader_id = :uid THEN 1 ELSE 0 END AS is_team_leader,
    CASE WHEN pm.user_id IS NOT NULL THEN 1 ELSE 0 END AS is_member
  FROM projects p
  LEFT JOIN project_members pm
    ON pm.project_id = p.project_id
   AND pm.user_id = :uid
   AND pm.left_at IS NULL
  WHERE p.project_id = :pid
    AND (
      p.created_by = :uid
      OR p.team_leader_id = :uid
      OR pm.user_id IS NOT NULL
    )
  LIMIT 1
");
$stmt->execute([':pid' => $projectId, ':uid' => $userId]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    http_response_code(403);
    exit("You don't have access to this project.");
}

$roleLower = strtolower((string)$role);
$isManager = ($roleLower === 'manager');
$isTeamLeaderOfThisProject = ((int)$project['is_team_leader'] === 1);

// Only manager/team leader should view manager-progress
if (!$isManager && !$isTeamLeaderOfThisProject) {
    http_response_code(403);
    exit("Only managers or the team leader can access this page.");
}

$canManageProject = true; // manager-progress implies they can manage

// -----------------------------
// Fetch ALL tasks in this project
// -----------------------------
$stmt = $db->prepare("
  SELECT 
    t.task_id,
    t.project_id,
    t.task_name,
    t.description,
    t.status,
    t.priority,
    t.deadline,
    t.created_date,
    t.created_by
  FROM tasks t
  WHERE t.project_id = :pid
  ORDER BY t.created_date DESC
");
$stmt->execute([':pid' => $projectId]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

$taskIds = array_column($tasks, 'task_id');

// -----------------------------
// Build assignees per task (emails + names + profile pictures)
// -----------------------------
$assigneesByTask = [];

if (!empty($taskIds)) {
    $placeholders = implode(',', array_fill(0, count($taskIds), '?'));

    $stmt2 = $db->prepare("
    SELECT
      ta.task_id,
      u.user_id,
      u.email,
      u.first_name,
      u.last_name,
      u.profile_picture
    FROM task_assignments ta
    JOIN users u ON u.user_id = ta.user_id
    WHERE ta.task_id IN ($placeholders)
    ORDER BY ta.task_id, u.first_name, u.last_name
  ");
    $stmt2->execute($taskIds);
    $rows = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $r) {
        $tid = (int)$r['task_id'];
        if (!isset($assigneesByTask[$tid])) $assigneesByTask[$tid] = [];

        $e = strtolower(trim($r['email']));
        $n = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));

        $assigneesByTask[$tid][] = [
            'email' => $e,
            'name'  => $n !== '' ? $n : $e,
            'avatarUrl' => !empty($r['profile_picture']) ? $r['profile_picture'] : null,
        ];
    }
}

// Attach to each task in the exact shape your JS expects
foreach ($tasks as &$t) {
    $tid = (int)$t['task_id'];
    $assignees = $assigneesByTask[$tid] ?? [];

    $t['assignedTo'] = array_map(fn($a) => $a['email'], $assignees);
    $t['assignees']  = $assignees; // optional
}
unset($t);

// -----------------------------
// Fetch all active users for __USERS__ (same as progress.php)
// -----------------------------
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
    $em = strtolower(trim($u['email']));
    $fullName = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));

    $avatarClass = 'avatar-' . ((int)$u['user_id'] % 4 + 1);
    $dbRole = $u['role'] ?? 'team_member';
    $jsRole = $roleMap[$dbRole] ?? 'member';

    $simUsers[$em] = [
        'name' => $fullName !== '' ? $fullName : $em,
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
    <!-- Link to the original progress.css for some base styles -->
    <link rel="stylesheet" href="progress.css">
    <!-- Link to the new manager-progress.css for specific styles -->
    <link rel="stylesheet" href="manager-progress.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>

    <script>
        window.__USERS__ = <?= json_encode($simUsers, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        window.__TASKS__ = <?= json_encode($tasks) ?>;
        window.__PROJECT__ = <?= json_encode($project) ?>;
        window.__ROLE__ = <?= json_encode($role) ?>;
        window.__CAN_MANAGE_PROJECT__ = <?= json_encode(true) ?>;
        window.__CURRENT_USER_EMAIL__ = <?= json_encode($email) ?>;
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
                    <li><a href="../home/home.html"><i data-feather="home"></i>Home</a></li>
                    <li class="active-parent"><a href="projects-overview.php"><i data-feather="folder"></i>Projects</a></li>
                    <li><a href="../knowledge-base/knowledge-base.html"><i data-feather="book-open"></i>Knowledge Base</a></li>
                </ul>
            </div>
            <div class="nav-footer">
                <ul>
                    <li><a href="../settings.html"><i data-feather="settings"></i>Settings</a></li>
                </ul>
            </div>
        </nav>

        <main class="main-content">
            <header class="project-header">
                <div class="project-header-top">
                    <div class="breadcrumbs-title">
                        <p class="breadcrumbs">Projects > <span id="project-name-breadcrumb">Project</span></p>
                        <h1 id="project-name-header">Project</h1>
                    </div>
                    <button class="close-project-btn" id="close-project-btn" style="display: none;">Close Project</button>
                </div>

                <nav class="project-nav" id="project-nav-links">
                    <a href="#" class="active">Tasks</a>
                    <a href="manager-progress.html">Progress</a>
                    <a href="#">Resources</a>
                </nav>
            </header>


            <div class="manager-progress-layout">
                <!-- Left Column -->
                <div class="manager-progress-left">
                    <!-- Task Progress Card -->
                    <section class="progress-card">
                        <h2>Task Progress</h2>
                        <div class="progress-bar-container">
                            <div class="progress-bar">
                                <div class="progress-fill" id="task-progress-fill" style="width: 0%"></div>
                            </div>
                        </div>
                        <p class="progress-text" id="progress-text">Your team has completed 0% of tasks assigned.</p>
                    </section>
                </div>

                <!-- Right Column -->
                <div class="manager-progress-right">
                    <!-- Upcoming Deadlines Card -->
                    <section id="deadlines-card" class="deadlines-card">
                        <h2>Upcoming Deadlines</h2>
                        <div class="deadlines-list" id="deadlines-list">
                            <!-- Deadlines will be rendered here -->
                        </div>
                    </section>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../app.js"></script>
</body>

</html>