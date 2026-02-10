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

// Must be on the project (member OR leader OR creator OR manager)
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
            ORDER BY 
    CASE 
        WHEN COUNT(DISTINCT ta.task_id) = 0 THEN 0
        ELSE ROUND((SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) * 100.0) / COUNT(DISTINCT ta.task_id), 0)
    END ASC,
    u.first_name ASC, 
    u.last_name ASC
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
// AJAX: DEADLINES LIST (SECURE)
// =============================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['ajax'] ?? '') === 'deadlines') {
    header('Content-Type: application/json; charset=utf-8');

    if (!$canManageProject) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No permission']);
        exit;
    }

    try {
        // Get upcoming/overdue tasks for THIS project (exclude completed)
        // Get first assignee only (using MIN to avoid GROUP BY issues)
        $stmt = $db->prepare("
            SELECT
                t.task_id,
                t.task_name,
                t.deadline,
                t.updated_at,
                t.status,
                t.priority,
                t.description,

                ta_first.user_id AS owner_id,
                u.first_name,
                u.last_name,
                u.profile_picture

            FROM tasks t
            LEFT JOIN (
                SELECT task_id, MIN(user_id) AS user_id
                FROM task_assignments
                GROUP BY task_id
            ) ta_first ON ta_first.task_id = t.task_id
            LEFT JOIN users u ON u.user_id = ta_first.user_id

            WHERE t.project_id = :pid
              AND t.status != 'completed'
              AND t.deadline IS NOT NULL

            ORDER BY t.deadline ASC
            LIMIT 30
        ");
        $stmt->execute([':pid' => $projectId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $items = array_map(function ($r) {
            $ownerName = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
            if ($ownerName === '') $ownerName = 'Unassigned';

            return [
                'task_id' => (int)$r['task_id'],
                'task_name' => $r['task_name'],
                'deadline' => $r['deadline'],
                'updated_at' => $r['updated_at'],
                'status' => $r['status'] ?? 'in_progress',
                'priority' => $r['priority'] ?? 'medium',
                'description' => $r['description'] ?? '',
                'owner' => [
                    'user_id' => $r['owner_id'] ? (int)$r['owner_id'] : null,
                    'name' => $ownerName,
                    'avatar' => !empty($r['profile_picture']) ? $r['profile_picture'] : null,
                ]
            ];
        }, $rows);

        echo json_encode(['success' => true, 'items' => $items]);
        exit;
    } catch (Throwable $e) {
        error_log("Deadlines query error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error']);
        exit;
    }
}


// =============================
// CLOSE PROJECT (AJAX) 
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
// =============================
// AJAX: UPDATE TASK
// =============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['ajax'] ?? '') === 'update_task') {
    header('Content-Type: application/json; charset=utf-8');

    if (!$canManageProject) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No permission']);
        exit;
    }

    $taskId = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;
    $taskName = trim($_POST['task_name'] ?? '');
    $deadline = trim($_POST['deadline'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($taskId <= 0 || $taskName === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    if ($deadline === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Deadline is required']);
        exit;
    }

    try {
        // Verify task belongs to this project
        $checkStmt = $db->prepare("
            SELECT task_id FROM tasks 
            WHERE task_id = :tid AND project_id = :pid
        ");
        $checkStmt->execute([':tid' => $taskId, ':pid' => $projectId]);
        
        if (!$checkStmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Task not found in this project']);
            exit;
        }

        // Convert datetime-local format to MySQL timestamp
        $deadlineTimestamp = date('Y-m-d H:i:s', strtotime($deadline));

        // Update task
        $updateStmt = $db->prepare("
            UPDATE tasks 
            SET task_name = :name,
                deadline = :deadline,
                description = :description
            WHERE task_id = :tid
        ");
        
        $result = $updateStmt->execute([
            ':name' => $taskName,
            ':deadline' => $deadlineTimestamp,
            ':description' => $description,
            ':tid' => $taskId
        ]);

        if (!$result) {
            throw new Exception('Failed to update task');
        }

        echo json_encode(['success' => true, 'message' => 'Task updated successfully']);
        exit;

    } catch (Throwable $e) {
        error_log("Update task error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

// =============================
// AJAX: GANTT CHART DATA
// =============================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['ajax'] ?? '') === 'gantt_data') {
    header('Content-Type: application/json; charset=utf-8');


    if (!$canManageProject) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No permission']);
        exit;
    }

    try {
        // Get all tasks for this project with assignees
        $stmt = $db->prepare("
            SELECT
                t.task_id,
                t.task_name,
                t.status,
                t.priority,
                t.created_date,
                t.deadline,
                t.started_date,
                t.completed_date,
                
                u.user_id,
                u.first_name,
                u.last_name,
                u.email,
                u.profile_picture
                
            FROM tasks t
            INNER JOIN task_assignments ta ON ta.task_id = t.task_id
            INNER JOIN users u ON u.user_id = ta.user_id
            
            WHERE t.project_id = :pid
              AND u.is_active = 1
              
            ORDER BY u.first_name ASC, u.last_name ASC, t.deadline ASC
        ");
        $stmt->execute([':pid' => $projectId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Group tasks by user
        $userTasks = [];
        foreach ($rows as $row) {
            $userId = (int)$row['user_id'];
            $userEmail = strtolower(trim($row['email']));
            $userName = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
            if ($userName === '') $userName = $userEmail;

            if (!isset($userTasks[$userId])) {
                $userTasks[$userId] = [
                    'user_id' => $userId,
                    'name' => $userName,
                    'email' => $userEmail,
                    'avatar' => !empty($row['profile_picture']) ? $row['profile_picture'] : null,
                    'tasks' => []
                ];
            }

            // Use created_date as start and deadline as end
            $startDate = $row['created_date'];
            $endDate = $row['deadline'];

            $userTasks[$userId]['tasks'][] = [
                'task_id' => (int)$row['task_id'],
                'task_name' => $row['task_name'],
                'status' => $row['status'],
                'priority' => $row['priority'] ?? 'medium',
                'start_date' => $startDate,
                'end_date' => $endDate,
                'deadline' => $row['deadline'],
                'created_date' => $row['created_date']
            ];
        }

        echo json_encode([
            'success' => true,
            'users' => array_values($userTasks)
        ]);
        exit;
        
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error']);
        exit;
    }
}

// =============================
// AJAX: GET PROJECT MEMBERS
// =============================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['ajax'] ?? '') === 'project_members') {
    header('Content-Type: application/json; charset=utf-8');

    if (!$canManageProject) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No permission']);
        exit;
    }
    // Get all active users who are members of this project (for assignee dropdowns)
    try {
        $stmt = $db->prepare("
            SELECT DISTINCT
                u.user_id,
                u.email,
                u.first_name,
                u.last_name,
                u.profile_picture
            FROM users u
            INNER JOIN project_members pm ON pm.user_id = u.user_id
            WHERE pm.project_id = :pid
              AND u.is_active = 1
            ORDER BY u.first_name ASC, u.last_name ASC
        ");
        $stmt->execute([':pid' => $projectId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $members = array_map(function ($r) {
            $name = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
            if ($name === '') $name = strtolower(trim($r['email'] ?? ''));

            return [
                'user_id' => (int)$r['user_id'],
                'name' => $name,
                'email' => strtolower(trim($r['email'] ?? '')),
                'avatarUrl' => !empty($r['profile_picture']) ? $r['profile_picture'] : null,
            ];
        }, $rows);

        echo json_encode(['success' => true, 'members' => $members]);
        exit;
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error']);
        exit;
    }
}

// =============================
// AJAX: UPDATE TASK ASSIGNEE
// =============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['ajax'] ?? '') === 'update_task_assignee') {
    header('Content-Type: application/json; charset=utf-8');

    if (!$canManageProject) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No permission']);
        exit;
    }

    $taskId = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;
    $newUserId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

    if ($taskId <= 0 || $newUserId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    try {
        $checkStmt = $db->prepare("
            SELECT task_id FROM tasks 
            WHERE task_id = :tid AND project_id = :pid
        ");
        $checkStmt->execute([':tid' => $taskId, ':pid' => $projectId]);
        
        if (!$checkStmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Task not found']);
            exit;
        }

        $deleteStmt = $db->prepare("DELETE FROM task_assignments WHERE task_id = :tid");
        $deleteStmt->execute([':tid' => $taskId]);

        $insertStmt = $db->prepare("
            INSERT INTO task_assignments (task_id, user_id, assigned_by) 
            VALUES (:tid, :uid, :assigned_by)
        ");
        $insertStmt->execute([
            ':tid' => $taskId, 
            ':uid' => $newUserId,
            ':assigned_by' => $userId
        ]);

        echo json_encode(['success' => true, 'message' => 'Assignee updated']);
        exit;

    } catch (Throwable $e) {
        error_log("Update assignee error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error']);
        exit;
    }
}

if (!$canManageProject) {
    http_response_code(403);
    exit("You don't have access to the manager progress view.");
}

// Fetch active users for avatars + names 
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
                        <p class="breadcrumbs"><a href="projects-overview.php">Projects</a> > <span id="project-name-breadcrumb"><?= htmlspecialchars($project['project_name'] ?? 'Project') ?></span></p>
                        <h1 id="project-name-header"><?= htmlspecialchars($project['project_name'] ?? 'Project') ?></h1>
                    </div>
                    <?php if ($canCloseProject): ?>
                        <button class="close-project-btn" id="close-project-btn"><i data-feather="archive"></i> Close Project</button>
                    <?php endif; ?>


                </div>

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

                      <div class="deadlines-scroll">
  <div class="deadlines-columns">
    <div>Task</div>
    <div>Due</div>
    <div>Activity</div>
  </div>

  <div class="deadlines-list" id="deadlines-list"></div>
</div>

                    </section>
                </div>
              <!-- Gantt Chart Section -->
<section class="gantt-card">
    <div class="gantt-header">
    <h2>Project Timeline</h2>
    
    
    <div class="gantt-controls">

     <!-- Search Bar -->
    <div class="gantt-search-wrap">
        <i data-feather="search"></i>
        <input
            type="text"
            id="gantt-search"
            class="gantt-search"
            placeholder="Search team members..."
            autocomplete="off"
        />
    </div>
            <!-- Date Navigation -->
            <div class="gantt-date-nav">
                <button class="gantt-nav-btn" id="gantt-prev-period">
                    <i data-feather="chevron-left"></i>
                </button>
                <div class="gantt-current-period" id="gantt-current-period">2026</div>
                <button class="gantt-nav-btn" id="gantt-next-period">
                    <i data-feather="chevron-right"></i>
                </button>
            </div>
            
            <!-- Period Toggle -->
            <div class="gantt-period-toggle">
                <button class="gantt-period-btn active" data-period="6">6 Months</button>
                <button class="gantt-period-btn" data-period="12">Full Year</button>
            </div>

            <!-- Legend -->
            <div class="gantt-legend">
                <div class="legend-item">
                    <div class="legend-color status-red"></div>
                    <span>Overdue</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color status-amber"></div>
                    <span>At Risk</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color status-green"></div>
                    <span>On Track</span>
                </div>
            </div>
        </div>
    </div>

    <div class="gantt-container" id="gantt-container">
    </div>
</section>
            </div>
        </main>
    </div>
    <!-- Close Project Confirm Modal  -->
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
<script>
(function () {
  const list = document.getElementById('deadlines-list');
  if (!list) return;

  const projectId = new URLSearchParams(location.search).get('project_id');
  
  // Store full task data when we load deadlines
  let taskDataMap = {};

  let projectMembersCache = null;

async function fetchProjectMembers() {
  if (projectMembersCache) return projectMembersCache;
  
  const res = await fetch(
    `manager-progress.php?project_id=${encodeURIComponent(projectId)}&ajax=project_members`,
    { credentials: 'same-origin' }
  );
  const data = await res.json();
  
  if (data.success) {
    projectMembersCache = data.members;
    return projectMembersCache;
  }
  return [];
}

  function parseDate(s){
    return new Date((s || '').replace(' ', 'T'));
  }

  function dueLabel(deadlineStr){
    const d = parseDate(deadlineStr);
    if (isNaN(d)) return '-';

    const now = new Date();
    const diffMs = d - now;
    const abs = Math.abs(diffMs);

    const mins = Math.round(abs / 60000);
    const hours = Math.round(abs / 3600000);
    const days = Math.round(abs / 86400000);

    if (abs < 3600000) return diffMs < 0 ? `${mins}m overdue` : `in ${mins}m`;
    if (abs < 86400000) return diffMs < 0 ? `${hours}h overdue` : `in ${hours}h`;
    return diffMs < 0 ? `${days}d overdue` : `in ${days}d`;
  }

  function activityLabel(updatedStr){
    const d = parseDate(updatedStr);
    if (isNaN(d)) return '-';

    const now = new Date();
    const diff = now - d;
    const mins = Math.round(diff / 60000);
    const hours = Math.round(diff / 3600000);
    const days = Math.round(diff / 86400000);

    if (mins < 60) return `${mins}m ago`;
    if (hours < 24) return `${hours}h ago`;
    return `${days}d ago`;
  }

  function renderRow(item){
    const row = document.createElement('div');
    row.className = 'deadline-row';
    row.style.cursor = 'pointer';
    row.dataset.taskId = item.task_id;

    const due = dueLabel(item.deadline);
    const activity = activityLabel(item.updated_at);

    const isOverdue = new Date(item.deadline) < new Date();
const dueClass = isOverdue ? 'overdue' : '';

row.innerHTML = `
  <div class="col-task">${item.task_name || '-'}</div>
  <div class="col-due right ${dueClass}">${due}</div>
  <div class="col-activity right">${activity}</div>
`;
    
    // Store full task data
    taskDataMap[item.task_id] = item;
    
    // Add click handler directly to the row
    row.addEventListener('click', () => {
      openTaskModal(item.task_id);
    });
    
    return row;
  }

  async function loadDeadlines(){
    list.innerHTML = '';
    taskDataMap = {};

    const url = `manager-progress.php?project_id=${encodeURIComponent(projectId)}&ajax=deadlines`;
    const res = await fetch(url, { credentials: 'same-origin' });
    const data = await res.json();

    if (!data.success) {
  list.innerHTML = `<div class="deadlines-empty">Unable to load deadlines</div>`;
  return;
}

if (data.items.length === 0) {
  list.innerHTML = `<div class="deadlines-empty">No upcoming deadlines</div>`;
  return;
}

(data.items || []).forEach(item => list.appendChild(renderRow(item)));
  }
  
  function openTaskModal(taskId) {
    const task = taskDataMap[taskId];
    if (!task) {
      console.error('Task not found:', taskId);
      return;
    }
    
    // Create a modal matching progress.css styles
    let modal = document.getElementById('deadline-task-modal');
    
    if (!modal) {
      modal = document.createElement('div');
      modal.id = 'deadline-task-modal';
      modal.className = 'modal-overlay';
      
      modal.innerHTML = `
        <div class="task-detail-modal">
          <div class="task-detail-header">
            <h3 id="modal-task-name"></h3>
            <div class="task-detail-actions">
              <button class="task-menu-btn" id="task-menu-btn">
                <i data-feather="more-vertical"></i>
              </button>
              <button class="modal-close-btn" id="modal-close-btn">
                <i data-feather="x"></i>
              </button>
            </div>
          </div>

          <!-- Dropdown menu -->
          <div class="task-actions-menu" id="task-actions-menu" style="display:none;">
            <div class="menu-section">
              <div class="menu-section-title">Status</div>
              <div id="status-options"></div>
            </div>
            <div class="menu-section">
              <div class="menu-section-title">Priority</div>
              <div id="priority-options"></div>
            </div>
            <div class="menu-section">
  <button class="menu-item" id="edit-task-btn">
    <i data-feather="edit-2"></i>
    Edit Task
  </button>
</div>
<div class="menu-section">
  <button class="menu-item menu-item-danger" id="delete-task-btn">
    <i data-feather="trash-2"></i>
    Delete Task
  </button>
</div>
          </div>

          <div class="task-detail-body">
  <div class="task-detail-row">
    <div class="task-detail-label">Task Name</div>
    <div class="task-detail-value" id="modal-task-name-value"></div>
    <input type="text" class="task-edit-input" id="modal-task-name-input" style="display:none;">
  </div>

  <div class="task-detail-row">
            <div class="task-detail-label">Assigned To</div>
            <div class="task-detail-value" id="modal-task-owner"></div>
            <select class="task-edit-select" id="modal-task-owner-select" style="display:none;">
            </select>
          </div>

            <div class="task-detail-row">
              <div class="task-detail-label">Status</div>
              <div class="task-detail-value">
                <span class="status-badge" id="modal-task-status"></span>
              </div>
            </div>

            <div class="task-detail-row">
              <div class="task-detail-label">Priority</div>
              <div class="task-detail-value">
                <span class="priority-badge" id="modal-task-priority"></span>
              </div>
            </div>

            <div class="task-detail-row">
  <div class="task-detail-label">Deadline</div>
  <div class="task-detail-value" id="modal-task-deadline"></div>
  <input type="datetime-local" class="task-edit-input" id="modal-task-deadline-input" style="display:none;">
</div>

       <div class="task-detail-row task-detail-row-notes">
  <div class="task-detail-label">Manager Notes</div>
  <div class="task-detail-value" id="modal-task-notes"></div>
  <textarea class="task-edit-textarea" id="modal-task-notes-input" style="display:none;" rows="4"></textarea>
</div>



            <div class="task-detail-row">
              <div class="task-detail-label">Last Updated</div>
              <div class="task-detail-value" id="modal-task-updated"></div>
            </div>
          </div>
          <div class="task-edit-actions" id="task-edit-actions" style="display:none;">
  <button class="task-edit-cancel" id="task-edit-cancel">Cancel</button>
  <button class="task-edit-save" id="task-edit-save">Save Changes</button>
</div>
        </div>

      `;
      
      document.body.appendChild(modal);
      
      // Close handlers
      const closeBtn = modal.querySelector('#modal-close-btn');
      const menuBtn = modal.querySelector('#task-menu-btn');
      const menu = modal.querySelector('#task-actions-menu');
      
      closeBtn.addEventListener('click', () => {
        modal.style.display = 'none';
        document.body.style.overflow = '';
        menu.style.display = 'none';
      });
      
      modal.addEventListener('click', (e) => {
        if (e.target === modal) {
          modal.style.display = 'none';
          document.body.style.overflow = '';
          menu.style.display = 'none';
        }
      });
      
      // Menu toggle
      menuBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
      });
      
      // Close menu when clicking outside
      document.addEventListener('click', (e) => {
        if (!menu.contains(e.target) && !menuBtn.contains(e.target)) {
          menu.style.display = 'none';
        }
      });
    }
    
    // Populate modal with task data
    const statusMap = {
      'to_do': { label: 'To Do', class: 'status-todo' },
      'in_progress': { label: 'In Progress', class: 'status-progress' },
      'review': { label: 'Review', class: 'status-review' },
      'completed': { label: 'Completed', class: 'status-completed' }
    };
    
    const priorityMap = {
      'low': { label: 'Low', class: 'priority-low' },
      'medium': { label: 'Medium', class: 'priority-medium' },
      'high': { label: 'High', class: 'priority-high' }
    };
    
    // Normalize status and priority
    const currentStatus = (task.status || 'in_progress').toLowerCase();
    const currentPriority = (task.priority || 'medium').toLowerCase();
    
    // Set task name
document.getElementById('modal-task-name').textContent = task.task_name || 'Untitled Task';
document.getElementById('modal-task-name-value').textContent = task.task_name || 'Untitled Task';
document.getElementById('modal-task-name-input').value = task.task_name || '';

    // Set owner and populate select
    const ownerEl = document.getElementById('modal-task-owner');
    const ownerSelectEl = document.getElementById('modal-task-owner-select');

    ownerEl.textContent = task.owner?.name || 'Unassigned';

    // Fetch and populate project members
    fetchProjectMembers().then(members => {
      ownerSelectEl.innerHTML = members.map(m => 
        `<option value="${m.user_id}" ${m.user_id === (task.owner?.user_id || 0) ? 'selected' : ''}>
          ${m.name}
        </option>`
      ).join('');
    });

    
    // Set status badge
    const statusInfo = statusMap[currentStatus] || statusMap['in_progress'];
    const statusEl = document.getElementById('modal-task-status');
    statusEl.textContent = statusInfo.label;
    statusEl.className = 'status-badge ' + statusInfo.class;
    
    // Set priority badge
    const priorityInfo = priorityMap[currentPriority] || priorityMap['medium'];
    const priorityEl = document.getElementById('modal-task-priority');
    priorityEl.textContent = priorityInfo.label;
    priorityEl.className = 'priority-badge ' + priorityInfo.class;
    
    // Set deadline
const deadline = parseDate(task.deadline);
document.getElementById('modal-task-deadline').textContent = !isNaN(deadline) 
  ? deadline.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })
  : 'No deadline';

if (!isNaN(deadline)) {
  const year = deadline.getFullYear();
  const month = String(deadline.getMonth() + 1).padStart(2, '0');
  const day = String(deadline.getDate()).padStart(2, '0');
  const hours = String(deadline.getHours()).padStart(2, '0');
  const minutes = String(deadline.getMinutes()).padStart(2, '0');
  document.getElementById('modal-task-deadline-input').value = `${year}-${month}-${day}T${hours}:${minutes}`;
}
    
    // Set last updated
    const updated = parseDate(task.updated_at);
    document.getElementById('modal-task-updated').textContent = !isNaN(updated)
      ? updated.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) + ' at ' + updated.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' })
      : 'Unknown';

      const notesEl = document.getElementById('modal-task-notes');
const notesText = (task.description && task.description.trim()) ? task.description.trim() : '—';
notesEl.textContent = notesText;
document.getElementById('modal-task-notes-input').value = task.description || '';

    
    // Populate status options (exclude current status)
    const statusContainer = document.getElementById('status-options');
    statusContainer.innerHTML = '';
    
    Object.entries(statusMap).forEach(([key, info]) => {
      if (key === currentStatus) return;
      
      const btn = document.createElement('button');
      btn.className = 'menu-item';
      btn.innerHTML = `<i data-feather="arrow-right"></i> ${info.label}`;
      btn.onclick = () => updateTaskStatus(task.task_id, key);
      statusContainer.appendChild(btn);
    });
    
    // Populate priority options (exclude current priority)
    const priorityContainer = document.getElementById('priority-options');
    priorityContainer.innerHTML = '';
    
    Object.entries(priorityMap).forEach(([key, info]) => {
      if (key === currentPriority) return;
      
      const btn = document.createElement('button');
      btn.className = 'menu-item';
      btn.innerHTML = `<i data-feather="flag"></i> ${info.label}`;
      btn.onclick = () => updateTaskPriority(task.task_id, key);
      priorityContainer.appendChild(btn);
    });
    

    // Edit button
const editBtn = document.getElementById('edit-task-btn');
const newEditBtn = editBtn.cloneNode(true);
editBtn.parentNode.replaceChild(newEditBtn, editBtn);
newEditBtn.onclick = () => enableEditMode(task.task_id);

// Edit mode handlers
document.getElementById('task-edit-cancel').onclick = () => disableEditMode();
document.getElementById('task-edit-save').onclick = () => saveTaskEdits(task.task_id);

    // Delete button
    const deleteBtn = document.getElementById('delete-task-btn');
    const newDeleteBtn = deleteBtn.cloneNode(true);
    deleteBtn.parentNode.replaceChild(newDeleteBtn, deleteBtn);
    newDeleteBtn.onclick = () => deleteTask(task.task_id);
    
    // Show modal
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    if (window.feather) feather.replace();
  }

  async function updateTaskStatus(taskId, newStatus) {
    try {
        const res = await fetch(`manager-progress.php?project_id=${encodeURIComponent(projectId)}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          ajax: 'update_task_status',
          task_id: taskId,
          new_status: newStatus
        })
      });
      
      const data = await res.json();
      
      if (!data.success) {
        alert(data.message || 'Failed to update status');
        return;
      }
      
      showNotification('Task status updated!');
      
      document.getElementById('deadline-task-modal').style.display = 'none';
      document.body.style.overflow = '';
      document.getElementById('task-actions-menu').style.display = 'none';
      
      await loadDeadlines();
      
      if (typeof initManagerMemberProgressList === 'function') {
        initManagerMemberProgressList();
      }
      
    } catch (err) {
      console.error('Status update error:', err);
      alert('Failed to update status. Please try again.');
    }
  }

  async function updateTaskPriority(taskId, newPriority) {
    try {
      const res = await fetch(`projects.php?project_id=${encodeURIComponent(projectId)}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          ajax: 'update_task_priority',
          task_id: taskId,
          priority: newPriority
        })
      });
      
      const data = await res.json();
      
      if (!data.success) {
        alert(data.message || 'Failed to update priority');
        return;
      }
      
      showNotification('Task priority updated!');
      
      document.getElementById('deadline-task-modal').style.display = 'none';
      document.body.style.overflow = '';
      document.getElementById('task-actions-menu').style.display = 'none';
      
      await loadDeadlines();
      
    } catch (err) {
      console.error('Priority update error:', err);
      alert('Failed to update priority. Please try again.');
    }
  }

  async function deleteTask(taskId) {
    if (!confirm('Are you sure you want to delete this task? This cannot be undone.')) return;
    
    try {
      const res = await fetch(`projects.php?project_id=${encodeURIComponent(projectId)}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          ajax: 'delete_task',
          task_id: taskId
        })
      });
      
      const data = await res.json();
      
      if (!data.success) {
        alert(data.message || 'Failed to delete task');
        return;
      }
      
      showNotification('Task deleted successfully!');
      
      document.getElementById('deadline-task-modal').style.display = 'none';
      document.body.style.overflow = '';
      
      await loadDeadlines();
      
      if (typeof initManagerMemberProgressList === 'function') {
        initManagerMemberProgressList();
      }
      
    } catch (err) {
      console.error('Delete error:', err);
      alert('Failed to delete task. Please try again.');
    }
  }

 function enableEditMode(taskId) {
  // Hide view mode, show edit mode
  document.getElementById('modal-task-name-value').style.display = 'none';
  document.getElementById('modal-task-name-input').style.display = 'block';
  
  document.getElementById('modal-task-owner').style.display = 'none';
  document.getElementById('modal-task-owner-select').style.display = 'block';
  
  document.getElementById('modal-task-deadline').style.display = 'none';
  document.getElementById('modal-task-deadline-input').style.display = 'block';
  
  document.getElementById('modal-task-notes').style.display = 'none';
  document.getElementById('modal-task-notes-input').style.display = 'block';
  
  document.getElementById('task-edit-actions').style.display = 'flex';
  
  // Hide the three-dot menu
  document.getElementById('task-actions-menu').style.display = 'none';
}

function disableEditMode() {
  // Show view mode, hide edit mode
  document.getElementById('modal-task-name-value').style.display = 'block';
  document.getElementById('modal-task-name-input').style.display = 'none';
  
  document.getElementById('modal-task-owner').style.display = 'block';
  document.getElementById('modal-task-owner-select').style.display = 'none';
  
  document.getElementById('modal-task-deadline').style.display = 'block';
  document.getElementById('modal-task-deadline-input').style.display = 'none';
  
  document.getElementById('modal-task-notes').style.display = 'block';
  document.getElementById('modal-task-notes-input').style.display = 'none';
  
  document.getElementById('task-edit-actions').style.display = 'none';
}

async function saveTaskEdits(taskId) {
  const taskName = document.getElementById('modal-task-name-input').value.trim();
  const deadlineInput = document.getElementById('modal-task-deadline-input').value;
  const description = document.getElementById('modal-task-notes-input').value.trim();
  const newAssigneeId = document.getElementById('modal-task-owner-select').value;
  
  if (!taskName) {
    alert('Task name cannot be empty');
    return;
  }
  
  if (!deadlineInput) {
    alert('Deadline is required');
    return;
  }
  
  try {
    // Update task details
    const taskRes = await fetch(`manager-progress.php?project_id=${encodeURIComponent(projectId)}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        ajax: 'update_task',
        task_id: taskId,
        task_name: taskName,
        deadline: deadlineInput,
        description: description
      })
    });
    
    const taskData = await taskRes.json();
    
    if (!taskData.success) {
      alert(taskData.message || 'Failed to update task');
      return;
    }

    // Update assignee if changed
    const currentAssigneeId = taskDataMap[taskId].owner?.user_id || 0;
    if (parseInt(newAssigneeId) !== currentAssigneeId) {
      const assigneeRes = await fetch(`manager-progress.php?project_id=${encodeURIComponent(projectId)}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          ajax: 'update_task_assignee',
          task_id: taskId,
          user_id: newAssigneeId
        })
      });
      
      const assigneeData = await assigneeRes.json();
      
      if (!assigneeData.success) {
        alert(assigneeData.message || 'Task updated but failed to change assignee');
      }
    }
    
    showNotification('Task updated successfully!');
    
    document.getElementById('deadline-task-modal').style.display = 'none';
    document.body.style.overflow = '';
    
    await loadDeadlines();
    
    if (typeof initManagerMemberProgressList === 'function') {
      initManagerMemberProgressList();
    }
    
  } catch (err) {
    console.error('Update error:', err);
    alert('Failed to update task. Please try again.');
  }
}


  function showNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'success-notification';
    notification.innerHTML = `
      <i data-feather="check-circle"></i>
      <span>${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    if (window.feather) feather.replace();
    
    setTimeout(() => {
      notification.classList.add('show');
    }, 10);
    
    setTimeout(() => {
      notification.classList.remove('show');
      setTimeout(() => notification.remove(), 300);
    }, 3000);
  }

  loadDeadlines();
})();
</script>

<script>
// Gantt Chart JavaScript - With Red/Amber/Green Status Colors
(function() {
    const container = document.getElementById('gantt-container');
    if (!container) return;

    const projectId = new URLSearchParams(location.search).get('project_id');
    let ganttData = [];
    let filteredGanttData = []; 
    let currentPeriod = 6;
    let currentYear = new Date().getFullYear();
    let currentMonth = new Date().getMonth();
    let tooltip = null;

    // Constants for task spacing
    const TASK_HEIGHT = 10;
    const TASK_SPACING = 4;
    const ROW_PADDING = 16;

    // Function to determine task status color 
    function getTaskStatusColor(task) {
        const now = new Date();
        const deadline = new Date(task.deadline);
        const createdDate = new Date(task.created_date);
        
        // If task is completed, use green
        if (task.status === 'completed') {
            return 'status-green';
        }
        
        // Calculate total duration and time elapsed
        const totalDuration = deadline - createdDate;
        const timeElapsed = now - createdDate;
        const percentComplete = (timeElapsed / totalDuration) * 100;
        
        if (now > deadline) {
            return 'status-red';
        }
        
        if (percentComplete >= 80) {
            return 'status-amber';
        }
        
        return 'status-green';
    }

    // Function to get status label for display
    function getTaskStatusLabel(task) {
        const now = new Date();
        const deadline = new Date(task.deadline);
        const createdDate = new Date(task.created_date);
        
        if (task.status === 'completed') {
            return 'Completed';
        }
        
        const totalDuration = deadline - createdDate;
        const timeElapsed = now - createdDate;
        const percentComplete = (timeElapsed / totalDuration) * 100;
        
        if (now > deadline) {
            return 'Overdue';
        }
        
        if (percentComplete >= 80) {
            return 'At Risk';
        }
        
        return 'On Track';
    }

    // Period toggle
    document.querySelectorAll('.gantt-period-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.gantt-period-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentPeriod = parseInt(btn.dataset.period);
            
            if (currentPeriod === 12) {
                currentMonth = 0;
            }
            
            renderGantt();
            updatePeriodLabel();
        });
    });

    // Date navigation
    document.getElementById('gantt-prev-period')?.addEventListener('click', () => {
        if (currentPeriod === 6) {
            currentMonth -= 6;
            if (currentMonth < 0) {
                currentMonth = 12 + currentMonth;
                currentYear--;
            }
        } else {
            currentYear--;
            currentMonth = 0;
        }
        renderGantt();
        updatePeriodLabel();
    });

    document.getElementById('gantt-next-period')?.addEventListener('click', () => {
        if (currentPeriod === 6) {
            currentMonth += 6;
            if (currentMonth >= 12) {
                currentMonth = currentMonth - 12;
                currentYear++;
            }
        } else {
            currentYear++;
            currentMonth = 0;
        }
        renderGantt();
        updatePeriodLabel();
    });

    function updatePeriodLabel() {
        const label = document.getElementById('gantt-current-period');
        if (!label) return;

        if (currentPeriod === 6) {
            const startMonth = new Date(currentYear, currentMonth, 1).toLocaleString('default', { month: 'short' });
            const endMonth = new Date(currentYear, currentMonth + 5, 1).toLocaleString('default', { month: 'short' });
            label.textContent = `${startMonth} - ${endMonth} ${currentYear}`;
        } else {
            label.textContent = `${currentYear}`;
        }
    }

    async function loadGanttData() {
        try {
            const res = await fetch(
                `manager-progress.php?project_id=${encodeURIComponent(projectId)}&ajax=gantt_data`,
                { credentials: 'same-origin' }
            );
            const data = await res.json();

            if (!data.success) {
                container.innerHTML = '<div class="gantt-empty"><p>Unable to load timeline data</p></div>';
                return;
            }

            ganttData = data.users || [];
            filteredGanttData = ganttData;
            renderGantt();
            updatePeriodLabel();
        } catch (err) {
            console.error('Gantt load error:', err);
            container.innerHTML = '<div class="gantt-empty"><p>Error loading timeline</p></div>';
        }
    }

    function setupSearch() {
        const searchInput = document.getElementById('gantt-search');
        if (!searchInput) return;

        searchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase().trim();
            
            if (searchTerm === '') {
                filteredGanttData = ganttData;
            } else {
                filteredGanttData = ganttData.filter(user => 
                    user.name.toLowerCase().includes(searchTerm) ||
                    user.email.toLowerCase().includes(searchTerm)
                );
            }
            
            renderGantt();
        });
    }

    function renderGantt() {
    const dataToRender = filteredGanttData.length > 0 || document.getElementById('gantt-search')?.value 
        ? filteredGanttData 
        : ganttData;
    
    if (!dataToRender.length && ganttData.length > 0) {
        container.innerHTML = `
            <div class="gantt-empty">
                <i data-feather="search"></i>
                <p>No team members match your search</p>
            </div>
        `;
        if (window.feather) feather.replace();
        return;
    }
    
    if (!dataToRender.length) {
            container.innerHTML = `
                <div class="gantt-empty">
                    <i data-feather="calendar"></i>
                    <p>No tasks scheduled yet</p>
                </div>
            `;
            if (window.feather) feather.replace();
            return;
        }

        const periodStart = new Date(currentYear, currentMonth, 1, 0, 0, 0, 0);
        const periodEnd = new Date(currentYear, currentMonth + currentPeriod, 0, 23, 59, 59, 999);

        const months = generateMonths(periodStart, periodEnd);

        let html = '<div class="gantt-grid">';
        
        html += '<div class="gantt-left-column">';
        html += '<div class="gantt-left-header">Team Members</div>';
        
        dataToRender.forEach(user => {
            // Calculate row height based on number of tasks
            const visibleTasks = user.tasks.filter(task => {
                const taskPosition = calculateTaskPosition(task, periodStart, periodEnd);
                return taskPosition !== null;
            });
            
            const rowHeight = calculateRowHeight(visibleTasks.length);
            
            const avatarHtml = user.avatar
                ? `<img src="${user.avatar}" alt="${user.name}">`
                : getInitials(user.name);

            html += `
                <div class="gantt-employee-row" style="height: ${rowHeight}px;">
                    <div class="gantt-employee-avatar ${!user.avatar ? 'avatar-' + (user.user_id % 4 + 1) : ''}">
                        ${avatarHtml}
                    </div>
                    <div class="gantt-employee-info">
                        <div class="gantt-employee-name">${user.name}</div>
                        <div class="gantt-employee-task-count">${user.tasks.length} task${user.tasks.length !== 1 ? 's' : ''}</div>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';

        // Right column - Timeline
        html += '<div class="gantt-right-column">';
        
        // Timeline header
        html += '<div class="gantt-timeline-header">';
        months.forEach(month => {
            html += `<div class="gantt-month">${month.name}</div>`;
        });
        html += '</div>';

        // Timeline rows
        html += '<div class="gantt-timeline-rows">';
        
        dataToRender.forEach(user => {
            const taskPositions = [];
            user.tasks.forEach(task => {
                const position = calculateTaskPosition(task, periodStart, periodEnd);
                if (position) {
                    taskPositions.push({ task, position });
                }
            });

            // Calculate stacking for overlapping tasks
            const stackedTasks = stackTasks(taskPositions);
            const rowHeight = calculateRowHeight(stackedTasks.length);
            
            html += `<div class="gantt-timeline-row" style="height: ${rowHeight}px;">`;
            html += '<div style="position: relative; display: flex; width: 100%; height: 100%;">';
            
            // Month columns
            months.forEach(() => {
                html += `<div class="gantt-month-column"></div>`;
            });
            
            // Render stacked tasks
            stackedTasks.forEach(({ task, position, stackLevel }) => {
                const topPosition = ROW_PADDING + (stackLevel * (TASK_HEIGHT + TASK_SPACING));
                const escapedTask = escapeHtml(JSON.stringify(task));
                const statusColor = getTaskStatusColor(task);
                
                html += `
                    <div class="gantt-task-bar ${statusColor}"
                         style="position: absolute; 
                                left: ${position.left}%; 
                                width: ${position.width}%; 
                                top: ${topPosition}px;"
                         data-task='${escapedTask}'
                         title="${task.task_name}">
                    </div>
                `;
            });
            
            html += '</div>';
            html += '</div>';
        });
        
        html += '</div>';
        html += '</div>';
        html += '</div>';

        container.innerHTML = html;

        if (!tooltip) {
            tooltip = document.createElement('div');
            tooltip.className = 'gantt-tooltip';
            document.body.appendChild(tooltip);
        }

        addTaskBarListeners();
        if (window.feather) feather.replace();
    }

    function calculateRowHeight(taskCount) {
        if (taskCount === 0) return 72;
        const tasksHeight = taskCount * TASK_HEIGHT + (taskCount - 1) * TASK_SPACING;
        return Math.max(72, tasksHeight + (ROW_PADDING * 2));
    }

    function stackTasks(taskPositions) {
        if (taskPositions.length === 0) return [];

        // Sort tasks by start position
        taskPositions.sort((a, b) => a.position.left - b.position.left);

        const stacked = [];
        const levels = [];

        taskPositions.forEach(({ task, position }) => {
            let stackLevel = 0;
            let placed = false;

            while (!placed) {
                if (!levels[stackLevel]) {
                    levels[stackLevel] = [];
                }

                const overlaps = levels[stackLevel].some(existing => {
                    return !(position.left >= existing.right || 
                             position.left + position.width <= existing.left);
                });

                if (!overlaps) {
                    levels[stackLevel].push({
                        left: position.left,
                        right: position.left + position.width
                    });
                    stacked.push({ task, position, stackLevel });
                    placed = true;
                } else {
                    stackLevel++;
                }
            }
        });

        return stacked;
    }

    function getInitials(name) {
        if (!name) return '??';
        return name.split(' ')
            .filter(n => n.length > 0)
            .map(n => n[0])
            .join('')
            .substring(0, 2)
            .toUpperCase();
    }

    function generateMonths(periodStart, periodEnd) {
        const months = [];
        const current = new Date(periodStart);
        
        current.setDate(1);
        current.setHours(0, 0, 0, 0);
        
        const endMonth = periodEnd.getMonth();
        const endYear = periodEnd.getFullYear();

        while (current.getFullYear() < endYear || 
               (current.getFullYear() === endYear && current.getMonth() <= endMonth)) {
            
            const monthName = current.toLocaleString('default', { 
                month: 'short', 
                year: currentPeriod === 12 ? undefined : 'numeric' 
            });
            
            const monthStart = new Date(current.getFullYear(), current.getMonth(), 1, 0, 0, 0, 0);
            const monthEnd = new Date(current.getFullYear(), current.getMonth() + 1, 0, 23, 59, 59, 999);
            
            months.push({
                name: monthName,
                start: monthStart,
                end: monthEnd
            });

            current.setMonth(current.getMonth() + 1);
        }

        return months;
    }

    function calculateTaskPosition(task, periodStart, periodEnd) {
        // Use created_date as start, deadline as end
        const taskStart = new Date(task.created_date);
        const taskEnd = new Date(task.deadline);

        if (isNaN(taskStart.getTime()) || isNaN(taskEnd.getTime())) {
            return null;
        }

        if (taskEnd < periodStart || taskStart > periodEnd) {
            return null;
        }

        const clampedStart = taskStart < periodStart ? periodStart : taskStart;
        const clampedEnd = taskEnd > periodEnd ? periodEnd : taskEnd;

        const periodDuration = periodEnd.getTime() - periodStart.getTime();
        const left = ((clampedStart.getTime() - periodStart.getTime()) / periodDuration) * 100;
        const width = ((clampedEnd.getTime() - clampedStart.getTime()) / periodDuration) * 100;

        const finalWidth = Math.max(0.5, width);
        const finalLeft = Math.max(0, Math.min(100 - finalWidth, left));

        return { 
            left: finalLeft, 
            width: finalWidth
        };
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function addTaskBarListeners() {
        document.querySelectorAll('.gantt-task-bar').forEach(bar => {
            bar.addEventListener('mouseenter', (e) => {
                try {
                    const task = JSON.parse(bar.dataset.task);
                    showTooltip(task, e);
                } catch (err) {
                    console.error('Error parsing task data:', err);
                }
            });

            bar.addEventListener('mousemove', (e) => {
                positionTooltip(e);
            });

            bar.addEventListener('mouseleave', () => {
                hideTooltip();
            });
        });
    }

    function showTooltip(task, e) {
        const statusLabels = {
            'to_do': 'To Do',
            'in_progress': 'In Progress',
            'review': 'Review',
            'completed': 'Completed'
        };

        const priorityLabels = {
            'low': 'Low',
            'medium': 'Medium',
            'high': 'High'
        };

        const formatDate = (dateStr) => {
            const d = new Date(dateStr);
            if (isNaN(d.getTime())) return 'N/A';
            return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
        };

        // Get the task timeline status
        const timelineStatus = getTaskStatusLabel(task);
        const statusColor = getTaskStatusColor(task);
        
        let statusColorText = '';
        if (statusColor === 'status-red') {
            statusColorText = '<span style="color: #ef5350; font-weight: 600;">🔴 RED - Overdue</span>';
        } else if (statusColor === 'status-amber') {
            statusColorText = '<span style="color: #ffa726; font-weight: 600;">🟠 AMBER - At Risk</span>';
        } else {
            statusColorText = '<span style="color: #66bb6a; font-weight: 600;">🟢 GREEN - On Track</span>';
        }

        tooltip.innerHTML = `
            <div class="gantt-tooltip-title">${task.task_name}</div>
            <div class="gantt-tooltip-row">
                <span class="gantt-tooltip-label">Timeline Status:</span>
                <span class="gantt-tooltip-value">${statusColorText}</span>
            </div>
            <div class="gantt-tooltip-row">
                <span class="gantt-tooltip-label">Task Status:</span>
                <span class="gantt-tooltip-value">${statusLabels[task.status] || 'Unknown'}</span>
            </div>
            <div class="gantt-tooltip-row">
                <span class="gantt-tooltip-label">Priority:</span>
                <span class="gantt-tooltip-value">${priorityLabels[task.priority] || 'Medium'}</span>
            </div>
            <div class="gantt-tooltip-row">
                <span class="gantt-tooltip-label">Created:</span>
                <span class="gantt-tooltip-value">${formatDate(task.created_date)}</span>
            </div>
            <div class="gantt-tooltip-row">
                <span class="gantt-tooltip-label">Deadline:</span>
                <span class="gantt-tooltip-value">${formatDate(task.deadline)}</span>
            </div>
        `;

        tooltip.classList.add('show');
        positionTooltip(e);
    }

    function positionTooltip(e) {
        if (!tooltip.classList.contains('show')) return;

        const offset = 15;
        let left = e.clientX + offset;
        let top = e.clientY + offset;

        const tooltipRect = tooltip.getBoundingClientRect();
        if (left + tooltipRect.width > window.innerWidth) {
            left = e.clientX - tooltipRect.width - offset;
        }
        if (top + tooltipRect.height > window.innerHeight) {
            top = e.clientY - tooltipRect.height - offset;
        }

        tooltip.style.left = left + 'px';
        tooltip.style.top = top + 'px';
    }

    function hideTooltip() {
        tooltip.classList.remove('show');
    }

    loadGanttData();
     setupSearch();
})();
</script>
</body>

</html>