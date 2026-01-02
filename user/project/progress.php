<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) die("Database connection failed.");

$userId = $_SESSION['user_id'] ?? null;
$role   = $_SESSION['role'] ?? null;

if (!$userId) {
    http_response_code(401);
    exit("Not logged in (login not merged yet).");
}

$projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
if ($projectId <= 0) exit("Missing/invalid project_id in the URL.");

//memeber validation
$stmt = $db->prepare("
  SELECT 
    p.project_id,
    p.project_name,
    p.description,
    p.status,
    p.deadline,
    p.team_leader_id,
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

// This is the key:
$canManageProject = $isManager || $isTeamLeaderOfThisProject;

// =============================
// GET TASKS FOR THIS PROJECT (role-based visibility + assignee pfps)
// =============================
$roleLower = strtolower((string)$role);

// If you can manage THIS project, you must see ALL tasks in it
if ($canManageProject) {
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
} else {
    // Otherwise you're a normal member: only tasks assigned to YOU
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
        AND EXISTS (
          SELECT 1
          FROM task_assignments ta
          WHERE ta.task_id = t.task_id
            AND ta.user_id = :uid
        )
      ORDER BY t.created_date DESC
    ");
    $stmt->execute([':pid' => $projectId, ':uid' => $userId]);
}

$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);


$taskIds = array_column($tasks, 'task_id');

// Build assignees per task WITH profile pictures
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

        $email = strtolower(trim($r['email']));
        $name  = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));

        $assigneesByTask[$tid][] = [
            'email' => $email,
            'name'  => $name !== '' ? $name : $email,
            'avatarUrl' => !empty($r['profile_picture']) ? $r['profile_picture'] : null,
        ];
    }
}

// ✅ Attach assignees to each task so JS can calculate progress correctly
foreach ($tasks as &$t) {
    $tid = (int)$t['task_id'];
    $assignees = $assigneesByTask[$tid] ?? [];

    // what your JS expects:
    $t['assignedTo'] = array_map(fn($a) => $a['email'], $assignees);

    // optional: if you want names/avatars in JS too
    $t['assignees'] = $assignees;
}
unset($t);


// Fetch users from DB
$stmt = $db->prepare("
  SELECT user_id, email, first_name, last_name, role, profile_picture
  FROM users
  WHERE is_active = 1
");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Map DB roles -> your JS roles
$roleMap = [
    'team_member' => 'member',
    'technical_specialist' => 'specialist',
    'team_leader' => 'team_leader',
    'manager' => 'manager',
];

// Build simUsers object keyed by email
$simUsers = [];
foreach ($users as $u) {
    $email = strtolower(trim($u['email']));
    $fullName = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));

    // stable avatar class 1..4 based on user_id (so it doesn't shuffle randomly)
    $avatarClass = 'avatar-' . ((int)$u['user_id'] % 4 + 1);

    $dbRole = $u['role'] ?? 'team_member';
    $jsRole = $roleMap[$dbRole] ?? 'member';

    $simUsers[$email] = [
        'name' => $fullName !== '' ? $fullName : $email,
        'role' => $jsRole,
        'avatarClass' => $avatarClass, // keep as fallback
        'avatarUrl' => !empty($u['profile_picture']) ? $u['profile_picture'] : null,
    ];
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make-It-All - Progress</title>
    <link rel="stylesheet" href="../dashboard.css">
    <link rel="stylesheet" href="progress.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>

    <script>
        window.__USERS__ = <?= json_encode($simUsers, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        window.__TASKS__ = <?= json_encode($tasks) ?>;
        window.__PROJECT__ = <?= json_encode($project) ?>;
        window.__ROLE__ = <?= json_encode($role) ?>;
        window.__IS_TEAM_LEADER_PROJECT__ = <?= json_encode($isTeamLeaderOfThisProject) ?>;
        window.__CAN_MANAGE_PROJECT__ = <?= json_encode($canManageProject) ?>;
        window.__CURRENT_USER_EMAIL__ = <?= json_encode(strtolower(trim($_SESSION['email'] ?? ''))) ?>;
        window.__CURRENT_USER_ROLE__ = <?= json_encode(strtolower(trim($_SESSION['role'] ?? 'team_member'))) ?>;
    </script>

</head>

<body id="progress-page">
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
                <div>
                    <p class="breadcrumbs">Projects > <span id="project-name-breadcrumb">Project</span></p>
                    <h1 id="project-name-header">Project</h1>
                </div>
                <nav class="project-nav" id="project-nav-links">
                    <!-- JS will populate these links -->
                    <a href="projects.html">Tasks</a>
                    <a href="manager-progress.html" class="active">Progress</a>
                    <a href="#">Resources</a>
                </nav>
                <button class="close-project-btn" id="close-project-btn" style="display: none;">Close Project</button>
            </header>

            <div class="progress-layout">
                <!-- Left Column -->
                <div class="progress-left">
                    <!-- Task Progress Card -->
                    <section class="progress-card">
                        <h2>Task Progress</h2>

                        <div class="progress-body">
                            <div class="progress-bar-container">
                                <div class="progress-bar">
                                    <div class="progress-fill" id="task-progress-fill" style="width: 0%">
                                        <span class="progress-icon">🏆</span>
                                    </div>
                                </div>
                            </div>

                            <p class="progress-text" id="progress-text">
                                You have completed x% of your assigned tasks.
                            </p>
                        </div>
                    </section>

                </div>

                <!-- Right Column -->
                <div class="progress-right">
                    <!-- Upcoming Deadlines Card -->
                    <section class="deadlines-card">
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