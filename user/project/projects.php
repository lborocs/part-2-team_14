<?php
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../actions/guard_project_access.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("Database connection failed.");
}

$userId = $_SESSION['user_id'] ?? null;
$role   = $_SESSION['role'] ?? null;

$isLocal = in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'], true);

if (!$userId && $isLocal) {
    // DEV BYPASS (remove when login is merged)
    $userId = 1;              // pick a real user_id from your sample data
    $role   = 'manager';       // or 'team_member' to test member view
}

if (!$userId) {
    http_response_code(401);
    exit("Not logged in (login not merged yet).");
}

// =============================
// AJAX: GET PROJECT DATA FOR PROGRESS PAGES (SECURE)
// =============================
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_project') {
    header('Content-Type: application/json; charset=utf-8');

    $pid = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
    if ($pid <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing/invalid project_id']);
        exit;
    }

    // ✅ ACCESS CHECK FIRST (prevents leaking project data)
    $access = guardProjectAccess($db, $pid, (int)$userId);
    $baseProject = $access['project']; // already verified safe

    // attach leader display info (still safe because access passed)
    $stmt = $db->prepare("
        SELECT 
            u.first_name AS team_leader_first_name,
            u.last_name  AS team_leader_last_name,
            u.profile_picture AS team_leader_avatar
        FROM users u
        WHERE u.user_id = :leader_id
        LIMIT 1
    ");
    $stmt->execute([':leader_id' => $baseProject['team_leader_id']]);
    $leader = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    echo json_encode([
        'success' => true,
        'project' => array_merge($baseProject, $leader),
        'canManageProject' => $access['canManageProject'],
        'canCloseProject'  => $access['canCloseProject'],
    ]);
    exit;
}

$projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
if ($projectId <= 0) {
    http_response_code(400);
    exit("Missing/invalid project_id in the URL.");
}

// SINGLE SOURCE OF TRUTH
$access = guardProjectAccess($db, $projectId, (int)$userId);

$project = $access['project'];
$isManager = $access['isManager'];
$isTeamLeaderOfThisProject = $access['isTeamLeaderOfThisProject'];
$canManageProject = $access['canManageProject'];
$canCloseProject  = $access['canCloseProject'];


$allowedTaskStatuses = ['to_do', 'in_progress', 'review', 'completed'];


// =============================
// UPDATE TASKS FOR THIS PROJECT
// =============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['ajax'] ?? '') === 'update_task_status') {
    header('Content-Type: application/json; charset=utf-8');



    $roleLower = strtolower((string)$role);

    $taskId    = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;
    $newStatus = strtolower(trim($_POST['new_status'] ?? ''));

    if ($taskId <= 0 || $newStatus === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid task_id or status']);
        exit;
    }

    // managers/leaders: allow any status change
    if ($canManageProject) {
        // allowed
    } else {
        // members: ONLY allowed to move to review, and only if assigned
        if ($newStatus !== 'review') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Members can only submit tasks for review']);
            exit;
        }

        $check = $db->prepare("
            SELECT 1
            FROM task_assignments ta
            JOIN tasks t ON t.task_id = ta.task_id
            WHERE ta.task_id = :tid
            AND ta.user_id = :uid
            AND t.project_id = :pid
            LIMIT 1
        ");
        $check->execute([':tid' => $taskId, ':uid' => $userId, ':pid' => $projectId]);

        if (!$check->fetchColumn()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Not assigned to you']);
            exit;
        }
    }

    $dbStatus = $newStatus;

    $oldStatusStmt = $db->prepare("
        SELECT status FROM tasks
        WHERE task_id = :tid AND project_id = :pid
        LIMIT 1
    ");
    $oldStatusStmt->execute([
        ':tid' => $taskId,
        ':pid' => $projectId
    ]);

    $oldStatus = $oldStatusStmt->fetchColumn();
    if (!$oldStatus) {
        echo json_encode(['success' => false, 'error' => 'Task not found']);
        exit;
    }


    // Update only if task is in this project
    $upd = $db->prepare("
        UPDATE tasks
        SET
            status = :new_status,

            started_date = CASE
            WHEN :new_status = 'in_progress' AND started_date IS NULL
                THEN NOW()
            ELSE started_date
            END,

            completed_date = CASE
            WHEN :new_status = 'completed'
                THEN NOW()
            WHEN :new_status <> 'completed'
                AND :old_status = 'completed'
                THEN NULL
            ELSE completed_date
            END,

            reopened_count = CASE
            WHEN :old_status = 'completed'
                AND :new_status <> 'completed'
                THEN reopened_count + 1
            ELSE reopened_count
            END,

            last_reopened_date = CASE
            WHEN :old_status = 'completed'
                AND :new_status <> 'completed'
                THEN NOW()
            ELSE last_reopened_date
            END

        WHERE task_id = :tid AND project_id = :pid
        LIMIT 1
    ");

    $upd->execute([
        ':new_status' => $dbStatus,
        ':old_status' => $oldStatus,
        ':tid'        => $taskId,
        ':pid'        => $projectId,
    ]);


    if ($upd->rowCount() < 1) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Task not found in this project']);
        exit;
    }

    echo json_encode(['success' => true]);
    exit;
}

// =============================
// CREATE TASK (AJAX)
// =============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['ajax'] ?? '') === 'create_task') {
    header('Content-Type: application/json; charset=utf-8');

    // Only manager/team_leader can create tasks
    $roleLower = strtolower((string)$role);
    if (!$canManageProject) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No permission']);
        exit;
    }


    // Read inputs
    $taskName    = trim($_POST['task_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority    = strtolower(trim($_POST['priority'] ?? 'medium'));
    $status      = strtolower(trim($_POST['status'] ?? 'to_do')); // expects DB status
    $deadline    = trim($_POST['deadline'] ?? ''); // expects "YYYY-MM-DD" from <input type="date">
    $assignees   = $_POST['assignees'] ?? [];      // array of emails

    // Validate
    if ($taskName === '' || $deadline === '' || empty($assignees)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    $allowedPriority = ['low', 'medium', 'high'];
    if (!in_array($priority, $allowedPriority, true)) $priority = 'medium';

    $allowedStatus = ['to_do', 'in_progress', 'review', 'completed'];
    if (!in_array($status, $allowedStatus, true)) $status = 'to_do';

    // Convert deadline date -> timestamp (end of day)
    $deadlineTs = $deadline . " 17:00:00";

    try {
        $db->beginTransaction();

        // 1) Insert task
        $ins = $db->prepare("
            INSERT INTO tasks (task_name, description, project_id, created_by, deadline, status, priority)
            VALUES (:name, :desc, :pid, :uid, :deadline, :status, :priority)
        ");
        $ins->execute([
            ':name'     => $taskName,
            ':desc'     => $description,
            ':pid'      => $projectId,
            ':uid'      => $userId,
            ':deadline' => $deadlineTs,
            ':status'   => $status,
            ':priority' => $priority,
        ]);

        $newTaskId = (int)$db->lastInsertId();

        // 2) Resolve assignee emails -> user_ids
        // Build placeholders for IN (...)
        $assignees = array_values(array_unique(array_map('strtolower', array_map('trim', $assignees))));
        $placeholders = implode(',', array_fill(0, count($assignees), '?'));

        $uStmt = $db->prepare("
            SELECT user_id, email
            FROM users
            WHERE is_active = 1 AND LOWER(email) IN ($placeholders)
        ");
        $uStmt->execute($assignees);
        $foundUsers = $uStmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($foundUsers) === 0) {
            $db->rollBack();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No valid assignees found']);
            exit;
        }

        // 3) Insert task_assignments
        $aIns = $db->prepare("
            INSERT INTO task_assignments (task_id, user_id, assigned_by)
            VALUES (:tid, :uid, :by)
        ");

        // --- NEW: project_members upsert logic (no schema change) ---
        $pmActiveCheck = $db->prepare("
            SELECT 1
            FROM project_members
            WHERE project_id = :pid
            AND user_id = :uid
            AND left_at IS NULL
            LIMIT 1
        ");

        $pmReactivate = $db->prepare("
            UPDATE project_members
            SET left_at = NULL
            WHERE project_id = :pid
            AND user_id = :uid
            AND left_at IS NOT NULL
        ");

        $pmInsert = $db->prepare("
            INSERT INTO project_members (project_id, user_id, project_role, joined_at, left_at)
            VALUES (:pid, :uid, 'member', NOW(), NULL)
        ");

        $assignedEmails = [];

        foreach ($foundUsers as $fu) {
            $assigneeId = (int)$fu['user_id'];
            $assigneeEmail = strtolower(trim($fu['email']));

            // assign the task
            $aIns->execute([
                ':tid' => $newTaskId,
                ':uid' => $assigneeId,
                ':by'  => $userId,
            ]);

            // ensure they are an ACTIVE project member
            $pmActiveCheck->execute([
                ':pid' => $projectId,
                ':uid' => $assigneeId,
            ]);

            if (!$pmActiveCheck->fetchColumn()) {
                // try re-activate first (if they left before)
                $pmReactivate->execute([
                    ':pid' => $projectId,
                    ':uid' => $assigneeId,
                ]);

                // if nothing reactivated, insert new membership
                if ($pmReactivate->rowCount() === 0) {
                    $pmInsert->execute([
                        ':pid' => $projectId,
                        ':uid' => $assigneeId,
                    ]);
                }
            }

            $assignedEmails[] = $assigneeEmail;
        }


        $db->commit();

        echo json_encode([
            'success' => true,
            'task' => [
                'task_id'      => $newTaskId,
                'task_name'    => $taskName,
                'description'  => $description,
                'status'       => $status,
                'priority'     => $priority,
                'deadline'     => $deadlineTs,
                'created_date' => date('Y-m-d H:i:s'),
                'created_by'   => $userId,
                'assignedTo'   => $assignedEmails
            ]
        ]);
        exit;
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error creating task']);
        exit;
    }
}
error_log('CREATE TASK HIT: ' . json_encode($_POST));

// =============================
// CLOSE PROJECT (AJAX)
// =============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['ajax'] ?? '') === 'close_project') {
    header('Content-Type: application/json; charset=utf-8');

    // Only manager can close (change if you want team_leader too)
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
// DELETE TASKS FOR THIS PROJECT
// =============================

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["ajax"] ?? "") === "delete_task") {
    header("Content-Type: application/json; charset=UTF-8");

    $taskId = isset($_POST["task_id"]) ? (int)$_POST["task_id"] : 0;

    if ($taskId <= 0 || $projectId <= 0) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Missing/invalid task_id or project_id"]);
        exit;
    }

    // Permission check
    $roleLower = strtolower($_SESSION["role"] ?? "");
    if (!$canManageProject) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Not allowed"]);
        exit;
    }


    try {
        $db->beginTransaction();

        // delete assignments first (your real junction table)
        $stmt = $db->prepare("DELETE FROM task_assignments WHERE task_id = :task_id");
        $stmt->execute([":task_id" => $taskId]);

        // delete task scoped to project
        $stmt = $db->prepare("DELETE FROM tasks WHERE task_id = :task_id AND project_id = :project_id");
        $stmt->execute([
            ":task_id" => $taskId,
            ":project_id" => $projectId
        ]);

        if ($stmt->rowCount() === 0) {
            $db->rollBack();
            http_response_code(404);
            echo json_encode(["success" => false, "message" => "Task not found (or wrong project)"]);
            exit;
        }

        $db->commit();
        echo json_encode(["success" => true]);
        exit;
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Server error"]);
        exit;
    }
}
// =============================
// UPDATE TASK PRIORITY (AJAX)
// =============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['ajax'] ?? '') === 'update_task_priority') {
    header('Content-Type: application/json; charset=utf-8');

    // Only manager or team leader
    if (!$canManageProject) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No permission']);
        exit;
    }

    $taskId   = (int)($_POST['task_id'] ?? 0);
    $priority = strtolower(trim($_POST['priority'] ?? ''));

    $allowedPriorities = ['low', 'medium', 'high', 'urgent'];

    if ($taskId <= 0 || !in_array($priority, $allowedPriorities, true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        exit;
    }

    // Ensure task belongs to THIS project
    $stmt = $db->prepare("
        UPDATE tasks
        SET priority = :priority
        WHERE task_id = :tid
        AND project_id = :pid
        LIMIT 1
    ");

    $stmt->execute([
        ':priority' => $priority,
        ':tid'      => $taskId,
        ':pid'      => $projectId
    ]);

    if ($stmt->rowCount() < 1) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Task not found']);
        exit;
    }

    echo json_encode(['success' => true]);
    exit;
}


// =============================
// FETCH TASKS (SEARCH / FILTER / PAGINATION)
// =============================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['ajax'] ?? '') === 'fetch_tasks') {
    header('Content-Type: application/json; charset=utf-8');

    $search   = trim($_GET['search'] ?? '');
    $status   = strtolower(trim($_GET['status'] ?? ''));
    $priority = strtolower(trim($_GET['priority'] ?? ''));
    $assignee = strtolower(trim($_GET['assignee'] ?? ''));
    $due = $_GET['due'] ?? '';
    $today = date('Y-m-d');


    $page  = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(20, max(5, (int)($_GET['limit'] ?? 10)));
    $offset = ($page - 1) * $limit;

    $params = [':pid' => $projectId];

    $where = [];
    $where[] = "t.project_id = :pid";

    switch ($due) {
        case 'overdue':
            $where[] = "t.deadline IS NOT NULL AND t.deadline < :today";
            $params[':today'] = $today;
            break;

        case 'today':
            $where[] = "t.deadline = :today";
            $params[':today'] = $today;
            break;

        case 'week':
            $where[] = "t.deadline BETWEEN :today AND DATE_ADD(:today, INTERVAL 7 DAY)";
            $params[':today'] = $today;
            break;

        case 'none':
            $where[] = "t.deadline IS NULL";
            break;
    }


    // ROLE VISIBILITY
    if (!$canManageProject) {
        $where[] = "
        EXISTS (
        SELECT 1
        FROM task_assignments ta
        WHERE ta.task_id = t.task_id
        AND ta.user_id = :uid
        )
        ";

        $params[':uid'] = $userId;
    }

    // SEARCH
    if ($search !== '') {
        $where[] = "(
        LOWER(t.task_name) LIKE :search
        OR LOWER(t.description) LIKE :search
        )";

        $params[':search'] = '%' . strtolower($search) . '%';
    }

    // FILTERS
    if ($status !== '' && in_array($status, $allowedTaskStatuses, true)) {
        $where[] = "t.status = :status";
        $params[':status'] = $status;
    }

    if (in_array($priority, ['low', 'medium', 'high'], true)) {
        $where[] = "t.priority = :priority";
        $params[':priority'] = $priority;
    }

    if ($assignee !== '') {
        $where[] = "
       EXISTS (
       SELECT 1
       FROM task_assignments ta
       JOIN users u ON u.user_id = ta.user_id
       WHERE ta.task_id = t.task_id
       AND LOWER(u.email) = :assignee
       )
       ";

        $params[':assignee'] = $assignee;
    }

    $whereSql = implode(' AND ', $where);

    $sql = "
    SELECT
    t.task_id,
    t.task_name,
    t.description,
    t.status,
    t.priority,
    t.deadline,
    t.created_date,
    t.created_by
  FROM tasks t
  WHERE $whereSql
  ORDER BY t.created_date DESC
  LIMIT $limit OFFSET $offset
";


    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $taskIds = array_column($tasks, 'task_id');
    $assigneesByTask = [];

    if (!empty($taskIds)) {
        $placeholders = implode(',', array_fill(0, count($taskIds), '?'));

        $stmt2 = $db->prepare("
        SELECT
          ta.task_id,
          u.email,
          u.first_name,
          u.last_name,
          u.profile_picture
        FROM task_assignments ta
        JOIN users u ON u.user_id = ta.user_id
        WHERE ta.task_id IN ($placeholders)
    ");
        $stmt2->execute($taskIds);

        foreach ($stmt2->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $tid = (int)$r['task_id'];

            $assigneesByTask[$tid][] = [
                'email' => strtolower($r['email']),
                'name'  => trim($r['first_name'] . ' ' . $r['last_name']),
                'avatarUrl' => $r['profile_picture'] ?: null
            ];
        }
    }

    // attach to tasks
    foreach ($tasks as &$t) {
        $tid = (int)$t['task_id'];
        $t['assignedUsers'] = $assigneesByTask[$tid] ?? [];
        $t['assignedTo'] = array_map(
            fn($u) => $u['email'],
            $t['assignedUsers']
        );
    }
    unset($t);

    echo json_encode([
        'success' => true,
        'tasks'   => $tasks,
        'page'    => $page,
        'limit'   => $limit
    ]);
    exit;
}


// =============================
// GET TASKS FOR THIS PROJECT (role-based visibility + assignee pfps)
// =============================
$roleLower = strtolower((string)$role);

// If you can manage THIS project, you must see ALL tasks in it
if ($canManageProject) {
    $stmt = $db->prepare("
      SELECT 
        t.task_id,
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

// Attach BOTH:
// - assignedTo (emails) for your existing JS
// - assignedUsers (objects) so profile pictures always have direct data
foreach ($tasks as &$t) {
    $tid = (int)$t['task_id'];

    $assignedUsers = $assigneesByTask[$tid] ?? [];
    $t['assignedUsers'] = $assignedUsers;

    $t['assignedTo'] = array_map(function ($u) {
        return $u['email'];
    }, $assignedUsers);
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
    <title>Make-It-All - Project Page</title>
    <link rel="stylesheet" href="../dashboard.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
</head>

<script>
    window.__USERS__ = <?= json_encode($simUsers, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    window.__TASKS__ = <?= json_encode($tasks) ?>;
    window.__PROJECT__ = <?= json_encode($project) ?>;
    window.__ROLE__ = <?= json_encode($role) ?>;
    window.__IS_TEAM_LEADER_PROJECT__ = <?= json_encode($isTeamLeaderOfThisProject) ?>;
    window.__CAN_MANAGE_PROJECT__ = <?= json_encode($canManageProject) ?>;
    window.__CAN_CLOSE_PROJECT__ = <?= json_encode($canCloseProject) ?>; // ✅ manager-only
</script>

<script>
    console.log("__ROLE__", window.__ROLE__);
</script>

<body id="projects-page">

    <div class="dashboard-container">
        <nav class="sidebar">
            <div class="nav-top">
                <div class="logo-container">
                    <img src="../logo.png" alt="Make-It-All Logo" class="logo-icon">
                </div>
                <ul class="nav-main">
                    <li><a href="../home/home.html"><i data-feather="home"></i>Home</a></li>
                    <li class="active-parent"><a href="projects-overview.php"><i data-feather="folder"></i>Projects</a></li>
                    <li><a href="../knowledge-base/knowledge-base.html"><i data-feather="book-open"></i>Knowledge
                            Base</a></li>
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

                <!-- TOP ROW -->
                <div class="project-header-top">
                    <div class="breadcrumbs-title">
                        <p class="breadcrumbs">
                            Projects > <span id="project-name-breadcrumb"><?= htmlspecialchars($project['project_name'] ?? 'Project') ?></span>
                        </p>
                        <h1 id="project-name-header"><?= htmlspecialchars($project['project_name'] ?? 'Project') ?></h1>

                    </div>

                    <div class="project-header-right">
                        <?php if ($canCloseProject): ?>
                            <button class="close-project-btn" id="close-project-btn">
                                Close Project
                            </button>
                        <?php endif; ?>

                        <div class="task-search-wrap">

                            <input
                                type="text"
                                id="task-search-input"
                                placeholder="Search tasks..."
                                autocomplete="off" />
                        </div>

                        <div class="filter-group">
                            <button class="filter-toggle" id="filter-toggle">
                                Filters
                            </button>

                            <div class="filter-panel" id="filter-panel" hidden>
                                <label>
                                    <span>Status</span>
                                    <select id="filter-status">
                                        <option value="">All</option>
                                        <option value="to_do">To Do</option>
                                        <option value="in_progress">In Progress</option>
                                        <option value="review">Review</option>
                                        <option value="completed">Completed</option>
                                    </select>
                                </label>

                                <label>
                                    <span>Priority</span>
                                    <select id="filter-priority">
                                        <option value="">All</option>
                                        <option value="low">Low</option>
                                        <option value="medium">Medium</option>
                                        <option value="high">High</option>
                                    </select>
                                </label>

                                <label>
                                    <span>Due</span>
                                    <select id="filter-due">
                                        <option value="">Any</option>
                                        <option value="overdue">Overdue</option>
                                        <option value="today">Due today</option>
                                        <option value="week">Due this week</option>
                                        <option value="month">Due this month</option>
                                    </select>
                                </label>

                                <button type="button" class="filter-clear" id="filter-clear">
                                    Clear filters
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- NAV -->
                <nav class="project-nav" id="project-nav-links"></nav>

            </header>






            <section class="task-board">

                <div class="task-column" data-status="todo">
                    <div class="column-header todo-header">
                        <span class="task-count">0</span>
                        <h2>To Do</h2>
                        <button class="add-task">
                            <i data-feather="plus"></i>
                        </button>
                    </div>
                    <div class="task-list">
                    </div>
                </div>

                <div class="task-column" data-status="inprogress">
                    <div class="column-header inprogress-header">
                        <span class="task-count">0</span>
                        <h2>In Progress</h2>
                        <button class="add-task">
                            <i data-feather="plus"></i>
                        </button>

                    </div>
                    <div class="task-list">
                    </div>
                </div>

                <div class="task-column" data-status="review">
                    <div class="column-header review-header">
                        <span class="task-count">0</span>
                        <h2>Review</h2>
                        <button class="add-task">
                            <i data-feather="plus"></i>
                        </button>
                    </div>
                    <div class="task-list">
                    </div>
                </div>

                <div class="task-column" data-status="completed">
                    <div class="column-header completed-header">
                        <span class="task-count">0</span>
                        <h2>Completed</h2>
                        <button class="add-task">
                            <i data-feather="plus"></i>
                        </button>
                    </div>
                    <div class="task-list">
                    </div>
                </div>

            </section>
        </main>
    </div>

    <div class="modal-overlay" id="assign-task-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Assign New Task</h2>
                <button type="button" class="close-btn" id="close-modal-btn"><i data-feather="x"></i></button>
            </div>
            <div class="modal-body">
                <form id="assign-task-form" class="create-post-form" onsubmit="return false;">
                    <input type="hidden" id="modal-task-status">

                    <div class="form-group">
                        <label for="modal-task-title">Task Title</label>
                        <input type="text" id="modal-task-title" placeholder="e.g., Design homepage mockup" required>
                    </div>
                    <div class="form-group">
                        <label for="modal-task-project">Project</label>
                        <select id="modal-task-project" required>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Assign To (select one or more)</label>

                        <input
                            type="text"
                            id="assignee-search"
                            class="assignee-search"
                            placeholder="Search team member…"
                            autocomplete="off" />

                        <div id="assignee-selected-count" class="assignee-selected-count">Selected: 0</div>

                        <div id="modal-task-assignees" class="assignee-checklist">
                            <!-- JS will render checkboxes -->
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="modal-task-priority">Priority</label>
                        <select id="modal-task-priority" required>
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="modal-task-deadline">Deadline</label>
                        <input type="date" id="modal-task-deadline" required>
                    </div>
                    <div class="form-group">
                        <label for="modal-task-description">Description / Notes (Optional)</label>
                        <textarea id="modal-task-description" rows="3" placeholder="Additional details..."></textarea>
                    </div>
                    <button type="submit" class="create-post-btn">Assign Task</button>
                </form>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="task-details-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="details-task-title">Task Title</h2>
                <button type="button" class="close-btn" id="details-close-modal-btn"><i data-feather="x"></i></button>

            </div>
            <div class="modal-body">
                <div class="task-details-meta">
                    <div class="meta-item">
                        <strong>Project:</strong>
                        <span id="details-task-project"></span>
                    </div>
                    <div class="meta-item">
                        <strong>Priority:</strong>
                        <span id="details-task-priority" class="priority-badge"></span>
                    </div>
                    <div class="meta-item">
                        <strong>Assigned To:</strong>
                        <span id="details-task-assignees"></span>
                    </div>
                    <div class="meta-item">
                        <strong>Date Assigned:</strong>
                        <span id="details-task-created"></span>
                    </div>
                    <div class="meta-item">
                        <strong>Deadline:</strong>
                        <span id="details-task-deadline"></span>
                    </div>
                </div>
                <div class="task-details-description">
                    <strong>Manager Notes:</strong>
                    <p id="details-task-description"></p>
                </div>
                <div class="task-details-actions">
                    <button id="delete-task-btn" class="delete-task-btn">Delete Task</button>
                    <button id="project-complete-btn" class="project-complete-btn">Mark Complete</button>
                </div>


            </div>
        </div>
    </div>
    <!-- Close Project Confirm Modal -->
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