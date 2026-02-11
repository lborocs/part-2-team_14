<?php
session_start();

require_once __DIR__ . '/../../config/database.php';
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("Database connection failed.");
}

// Define color palette (same as other files)
$bannerColors = [
    '#5B9BD5',  
    '#7FB069',  
    '#9B59B6',  
    '#D4926F',  
    '#45B7B8', 
    '#6C8EAD',  
    '#2A9D8F',  
    '#B56576',  
    '#52796F',  
    '#7D8FA0',  
];

function getEmployeeColor($userId, $bannerColors, &$colorMap) {
    if (isset($colorMap[$userId])) {
        return $colorMap[$userId];
    }
    
    $selectedColor = $bannerColors[array_rand($bannerColors)];
    $colorMap[$userId] = $selectedColor;
    
    return $selectedColor;
}

// Initialize color map in session
if (!isset($_SESSION['employee_colors'])) {
    $_SESSION['employee_colors'] = [];
}

// ===============================
// AJAX ENDPOINT: Get Employee Names
// ===============================
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_employees') {
    header('Content-Type: application/json');
    
    $employeeIds = $_GET['ids'] ?? '';
    if (empty($employeeIds)) {
        echo json_encode([]);
        exit;
    }
    
    $ids = explode(',', $employeeIds);
    $ids = array_filter(array_map('intval', $ids));
    
    if (empty($ids)) {
        echo json_encode([]);
        exit;
    }
    
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    $stmt = $db->prepare("
        SELECT user_id, first_name, last_name, email, profile_picture
        FROM users
        WHERE user_id IN ($placeholders)
    ");
    $stmt->execute($ids);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format response with colors
    $response = array_map(function($emp) use ($bannerColors) {
        $color = getEmployeeColor(
            $emp['user_id'], 
            $bannerColors, 
            $_SESSION['employee_colors']
        );
        
        return [
            'id' => (int)$emp['user_id'],
            'name' => $emp['first_name'] . ' ' . $emp['last_name'],
            'email' => $emp['email'],
            'profile_picture' => $emp['profile_picture'],
            'color' => $color
        ];
    }, $employees);

    echo json_encode($response);
    exit;
}

// ===============================
// AJAX ENDPOINT: Check Project Membership
// ===============================
if (isset($_GET['ajax']) && $_GET['ajax'] === 'check_membership') {
    header('Content-Type: application/json');
    
    $projectId = (int)($_GET['project_id'] ?? 0);
    $employeeIds = $_GET['employee_ids'] ?? '';
    
    if ($projectId <= 0 || empty($employeeIds)) {
        echo json_encode(['not_members' => []]);
        exit;
    }
    
    $ids = explode(',', $employeeIds);
    $ids = array_filter(array_map('intval', $ids));
    
    if (empty($ids)) {
        echo json_encode(['not_members' => []]);
        exit;
    }
    
    // Build IN clause with named placeholders
    $inClause = [];
    $params = ['pid' => $projectId];
    foreach ($ids as $index => $id) {
        $key = "id$index";
        $inClause[] = ":$key";
        $params[$key] = $id;
    }
    $placeholders = implode(',', $inClause);
    
    // Get employees who are NOT active members of this project
    $stmt = $db->prepare("
        SELECT u.user_id, u.first_name, u.last_name
        FROM users u
        WHERE u.user_id IN ($placeholders)
        AND NOT EXISTS (
            SELECT 1 FROM project_members pm
            WHERE pm.user_id = u.user_id
            AND pm.project_id = :pid
            AND pm.left_at IS NULL
        )
    ");
    
    $stmt->execute($params);
    
    $notMembers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response = array_map(function($emp) {
        return [
            'id' => (int)$emp['user_id'],
            'name' => $emp['first_name'] . ' ' . $emp['last_name']
        ];
    }, $notMembers);
    
    echo json_encode(['not_members' => $response]);
    exit;
}

// ===============================
// GET ACTIVE PROJECTS FOR DROPDOWN
// ===============================
$projectsSql = "
    SELECT project_id, project_name
    FROM projects
    WHERE status IN ('active', 'planning')
    ORDER BY project_name ASC
";
$projectsStmt = $db->prepare($projectsSql);
$projectsStmt->execute();
$availableProjects = $projectsStmt->fetchAll(PDO::FETCH_ASSOC);

// ===============================
// ASSIGN TASK (POST handler)
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Get current user (logged in)
    $createdBy = $_SESSION['user_id'] ?? 1;
    
    // Collect form data
    $taskName    = trim($_POST['task_name'] ?? '');
    $projectId   = (int)($_POST['project_id'] ?? 0);
    $priority    = strtolower(trim($_POST['priority'] ?? 'medium'));
    $deadline    = trim($_POST['deadline'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $employeeIds = $_POST['employee_ids'] ?? [];
    
    if ($taskName === '' || $projectId <= 0 || $deadline === '' || empty($employeeIds)) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields (task name, project, deadline, or assignees).'
        ]);
        exit;
    }
    
    // Validate priority
    $allowedPriority = ['low', 'medium', 'high'];
    if (!in_array($priority, $allowedPriority, true)) {
        $priority = 'medium';
    }
    
    // Convert deadline to timestamp
    $deadlineTs = $deadline . " 17:00:00";
    
    try {
        $db->beginTransaction();
        
        // Insert task
        $ins = $db->prepare("
            INSERT INTO tasks (task_name, description, project_id, created_by, deadline, status, priority)
            VALUES (:name, :desc, :pid, :uid, :deadline, 'to_do', :priority)
        ");
        $ins->execute([
            ':name'     => $taskName,
            ':desc'     => $description,
            ':pid'      => $projectId,
            ':uid'      => $createdBy,
            ':deadline' => $deadlineTs,
            ':priority' => $priority,
        ]);
        
        $newTaskId = (int)$db->lastInsertId();
        
        // Assign task to selected employees
        $aIns = $db->prepare("
            INSERT INTO task_assignments (task_id, user_id, assigned_by)
            VALUES (:tid, :uid, :by)
        ");
        
        // Ensure employees are project members
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
        
        foreach ($employeeIds as $empId) {
            $empId = (int)$empId;
            if ($empId <= 0) continue;
            
            // Assign task
            $aIns->execute([
                ':tid' => $newTaskId,
                ':uid' => $empId,
                ':by'  => $createdBy,
            ]);
            
            // Ensure they're an active project member
            $pmActiveCheck->execute([
                ':pid' => $projectId,
                ':uid' => $empId,
            ]);
            
            if (!$pmActiveCheck->fetchColumn()) {
                $pmReactivate->execute([
                    ':pid' => $projectId,
                    ':uid' => $empId,
                ]);
                
                // If nothing reactivated, insert new membership
                if ($pmReactivate->rowCount() === 0) {
                    $pmInsert->execute([
                        ':pid' => $projectId,
                        ':uid' => $empId,
                    ]);
                }
            }
        }
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'task_id' => $newTaskId,
            'message' => 'Task assigned successfully!'
        ]);
        
    } catch (PDOException $e) {
        $db->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
    
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make-It-All - Assign Task</title>
    <link rel="stylesheet" href="../dashboard.css">
    <link rel="stylesheet" href="../knowledge-base/knowledge-base.css">
    <link rel="stylesheet" href="../home/create-project.css">
    <link rel="stylesheet" href="assign-task.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="/favicon.png">
    <script src="https://unpkg.com/feather-icons"></script>
</head>

<body id="create-project-page">
    <div class="dashboard-container">
        <nav class="sidebar">
            <div class="nav-top">
                <div class="logo-container">
                    <img src="../logo.png" alt="Make-It-All Logo" class="logo-icon">
                </div>
                <ul class="nav-main">
                    <li><a href="../home/home.html"><i data-feather="home"></i>Home</a></li>
                    <li><a href="../project/projects-overview.php"><i data-feather="folder"></i>Projects</a></li>
                    <li class="active-parent">
                        <a href="employee-directory.php"><i data-feather="users"></i>Employees</a>
                    </li>
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
            <header class="kb-header">
                <p class="breadcrumbs">
                    <a href="employee-directory.php" style="color: #8C8C8C; text-decoration: none;">Employee Directory</a>
                    > Assign Task
                </p>
                <h1>Assign New Task</h1>
            </header>

            <div class="kb-layout-wrapper">
                <div class="kb-main-content">
                    <form id="assign-task-form" class="create-post-form">
                        
                        <!-- Selected Employees Display -->
                        <div class="form-group">
                            <label>Assign To (<span id="employee-count">0</span> selected)</label>
                            <div id="selected-employees-display" class="selected-employees-box">
                                <p class="empty-message">No employees selected</p>
                            </div>
                        </div>

                        <!-- Task Title -->
                        <div class="form-group">
                            <label for="task-title">Task Title</label>
                            <input 
                                type="text" 
                                id="task-title" 
                                name="task_name"
                                placeholder="e.g., Design homepage mockup" 
                                required
                            >
                        </div>

                        <!-- Project Selection -->
                        <div class="form-group">
                            <label for="task-project">Project</label>
                            <select id="task-project" name="project_id" required>
                                <option value="">Select project...</option>
                                <?php foreach ($availableProjects as $proj): ?>
                                    <option value="<?= htmlspecialchars($proj['project_id']) ?>">
                                        <?= htmlspecialchars($proj['project_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Priority -->
                        <div class="form-group">
                            <label for="task-priority">Priority</label>
                            <select id="task-priority" name="priority" required>
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>

                        <!-- Deadline -->
                        <div class="form-group">
                            <label for="task-deadline">Deadline</label>
                            <input 
                                type="date" 
                                id="task-deadline" 
                                name="deadline"
                                min="<?= date('Y-m-d') ?>"
                                required
                            >
                        </div>

                        <!-- Description -->
                        <div class="form-group">
                            <label for="task-description">Description / Notes (Optional)</label>
                            <textarea 
                                id="task-description" 
                                name="description"
                                rows="4" 
                                placeholder="Additional details..."
                            ></textarea>
                        </div>

                        <button type="submit" class="create-post-btn">Assign Task</button>
                    </form>
                </div>

                <aside class="kb-sidebar">
                    <div class="kb-sidebar-widget">
                        <h3>Task Assignment Tips</h3>
                        <ul class="best-practices-list">
                            <li><strong>Clear Titles:</strong> Use descriptive task names that explain the work.</li>
                            <li><strong>Set Deadlines:</strong> Provide realistic timeframes for completion.</li>
                            <li><strong>Add Details:</strong> Include any important notes or requirements in the description.</li>
                            <li><strong>Auto-Added:</strong> Selected employees will automatically be added to the project if not already members.</li>
                        </ul>
                    </div>
                </aside>
            </div>
        </main>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal-overlay" id="confirmation-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Confirm Task Assignment</h2>
                <button type="button" class="close-btn" id="close-modal-btn">
                    <i data-feather="x"></i>
                </button>
            </div>
            <div class="modal-body">
                <p id="confirmation-message"></p>
                
                <div class="confirmation-details">
                    <div class="detail-section" id="not-members-section" style="display: none;">
                        <strong>Not in project (will be added):</strong>
                        <ul id="not-members-list"></ul>
                    </div>
                    
                    <div class="detail-section">
                        <strong>Task Details:</strong>
                        <ul id="task-details-list"></ul>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="cancel-btn" id="cancel-confirm-btn">Cancel</button>
                <button type="button" class="confirm-btn" id="confirm-submit-btn">
                    <i data-feather="check"></i>
                    Confirm
                </button>
            </div>
        </div>
    </div>

    <script src="assign-task.js"></script>
    <script>feather.replace();</script>
</body>

</html>