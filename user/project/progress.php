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
    $role   = 'team_member';
}

if (!$userId) {
    http_response_code(401);
    exit("Not logged in.");
}

$projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
if ($projectId <= 0) exit("Missing/invalid project_id in the URL.");

// SINGLE SOURCE OF TRUTH (access + roles for THIS project)
$access = guardProjectAccess($db, $projectId, (int)$userId);

$project = $access['project'];
$canManageProject = $access['canManageProject'];


// If they can manage, send them to manager-progress.php (no HTML loop anymore)
if ($canManageProject) {
    header("Location: manager-progress.php?project_id=" . urlencode($projectId));
    exit;
}

// Users for name lookup (assignees)
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
    <title>Make-It-All - Progress</title>

    <link rel="stylesheet" href="../dashboard.css">
    <link rel="stylesheet" href="progress.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>

    <script>
        window.__USERS__ = <?= json_encode($simUsers, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        window.__PROJECT__ = <?= json_encode($project) ?>;
        window.__ROLE__ = <?= json_encode($role) ?>;
        window.__CAN_MANAGE_PROJECT__ = <?= json_encode($canManageProject) ?>;
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
                <div class="project-header-top">
                    <div class="breadcrumbs-title">
                        <p class="breadcrumbs">
                            Projects >
                            <span id="project-name-breadcrumb">
                                <?= htmlspecialchars($project['project_name'] ?? 'Project') ?>
                            </span>
                        </p>

                        <h1 id="project-name-header">
                            <?= htmlspecialchars($project['project_name'] ?? 'Project') ?>
                        </h1>

                    </div>
                </div>

                <nav class="project-nav" id="project-nav-links"></nav>
            </header>

            <div style="padding:20px;">
                <div class="progress-layout">
                    <!-- Left Column -->
                    <div class="progress-left">
                        <!-- Task Progress Card -->
                        <section class="progress-card">
                            <h2>Task Progress</h2>
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

            </div>

        </main>
    </div>

    <script src="../app.js"></script>
    <script>
        feather.replace();
    </script>
</body>

</html>