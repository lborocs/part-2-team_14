<?php
session_start();

require_once __DIR__ . '/../../config/database.php';
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("Database connection failed.");
}

// Define same 10-color banner palette
$bannerColors = [
    '#5B9BD5',  // Soft Blue
    '#7FB069',  // Sage Green
    '#9B59B6',  // Muted Purple
    '#D4926F',  // Muted Orange
    '#45B7B8',  // Teal
    '#6C8EAD',  // Slate Blue
    '#2A9D8F',  // Deep Teal
    '#B56576',  // Mauve/Rose
    '#52796F',  // Forest Green
    '#7D8FA0',  // Dusty Blue
];

function getEmployeeColor($userId, $bannerColors, &$colorMap)
{
    // If color already assigned in this session, return it
    if (isset($colorMap[$userId])) {
        return $colorMap[$userId];
    }

    // Randomly assign one of the 10 colors
    $selectedColor = $bannerColors[array_rand($bannerColors)];

    // Store in session for persistence
    $colorMap[$userId] = $selectedColor;

    return $selectedColor;
}

// Initialize color map in session if not exists
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

    // Format response with colors (matching add-to-project.php)
    $response = array_map(function ($emp) use ($bannerColors) {
        // Get color from session
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
// CREATE PROJECT (POST handler)
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    // Collect fields from form
    $projectName = trim($_POST['project_name'] ?? '');
    $priority    = $_POST['priority'] ?? 'medium';
    $deadline    = $_POST['deadline'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $leaderId    = (int)($_POST['team_leader_id'] ?? 0);
    $employeeIds = $_POST['employee_ids'] ?? [];

    if ($projectName === '' || $deadline === '' || $leaderId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing project name, deadline, or team leader.'
        ]);
        exit;
    }

    // Validate priority
    $allowedPriorities = ['low', 'medium', 'high'];
    if (!in_array($priority, $allowedPriorities, true)) {
        $priority = 'medium';
    }

    // Get created_by (logged in user)
    $createdBy = $_SESSION['user_id'] ?? 1;

    if ($createdBy <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Not logged in.'
        ]);
        exit;
    }

    try {
        $db->beginTransaction();

        // Insert project
        $sql = "INSERT INTO projects
                (project_name, description, created_by, team_leader_id, start_date, deadline, status, priority)
              VALUES
                (:name, :description, :created_by, :leader_id, CURDATE(), :deadline, 'active', :priority)";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':name'        => $projectName,
            ':description' => $description !== '' ? $description : null,
            ':created_by'  => $createdBy,
            ':leader_id'   => $leaderId,
            ':deadline'    => $deadline,
            ':priority'    => $priority
        ]);

        $newProjectId = (int)$db->lastInsertId();

        // Add team leader into project_members with 'team_leader' role
        $stmt2 = $db->prepare("INSERT INTO project_members (project_id, user_id, project_role)
                             VALUES (:pid, :uid, 'team_leader')");
        $stmt2->execute([
            ':pid' => $newProjectId,
            ':uid' => $leaderId
        ]);

        // Manager never demoted
        // Promote ONLY team_member to team_leader in users table
        // Technical specialists should NOT change global role.
        $promote = $db->prepare("
            UPDATE users
            SET role = 'team_leader'
            WHERE user_id = :uid
            AND role = 'team_member'
        ");
        $promote->execute([':uid' => $leaderId]);

        // Add additional selected employees as team members (NOT team leader)
        if (!empty($employeeIds)) {
            foreach ($employeeIds as $empId) {
                $empId = (int)$empId;
                if ($empId <= 0 || $empId == $leaderId) continue; // Skip invalid or leader

                // Check if already a member
                $checkStmt = $db->prepare("
                    SELECT COUNT(*) FROM project_members
                    WHERE project_id = :pid AND user_id = :uid AND left_at IS NULL
                ");
                $checkStmt->execute([':pid' => $newProjectId, ':uid' => $empId]);

                if ($checkStmt->fetchColumn() > 0) continue; // Already a member

                // Insert as team member (NOT team leader)
                $insertStmt = $db->prepare("
                    INSERT INTO project_members (project_id, user_id, project_role)
                    VALUES (:pid, :uid, 'member')
                ");
                $insertStmt->execute([
                    ':pid' => $newProjectId,
                    ':uid' => $empId
                ]);
            }
        }

        $db->commit();

        echo json_encode([
            'success' => true,
            'project_id' => $newProjectId,
            'message' => 'Project created successfully!'
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
    <title>Make-It-All - Create New Project</title>
    <link rel="stylesheet" href="../dashboard.css">
    <link rel="stylesheet" href="../knowledge-base/knowledge-base.css">
    <link rel="stylesheet" href="../home/create-project.css">
    <link rel="stylesheet" href="create-new-project.css">
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
                    > Create New Project
                </p>
                <h1>Create a New Project</h1>
            </header>

            <div class="kb-layout-wrapper">
                <div class="kb-main-content">
                    <form id="create-project-form" class="create-post-form">

                        <!-- Selected Employees Display -->
                        <div class="form-group">
                            <label>Selected Employees (<span id="employee-count">0</span>)</label>
                            <div id="selected-employees-display" class="selected-employees-box">
                                <p class="empty-message">No employees selected</p>
                            </div>
                            <p class="field-note" id="minimum-requirement-note">
                                These employees will be added as team members. <strong>Minimum 4 employees required.</strong>
                            </p>
                        </div>

                        <!-- Project Name -->
                        <div class="form-group">
                            <label for="project-name">Project Name</label>
                            <input
                                type="text"
                                id="project-name"
                                name="project_name"
                                placeholder="e.g., Q4 Marketing Campaign, Website Redesign"
                                required>
                        </div>

                        <!-- Priority -->
                        <div class="form-group">
                            <label for="project-priority">Priority</label>
                            <select id="project-priority" name="priority" required>
                                <option value="">Select priority...</option>
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>

                        <!-- Deadline -->
                        <div class="form-group">
                            <label for="project-deadline">Deadline</label>
                            <input
                                type="date"
                                id="project-deadline"
                                name="deadline"
                                min="<?= date('Y-m-d') ?>"
                                required>
                        </div>

                        <!-- Description -->
                        <div class="form-group">
                            <label for="project-description">Description (Optional)</label>
                            <textarea
                                id="project-description"
                                name="description"
                                rows="5"
                                placeholder="Briefly describe this project's goals."></textarea>
                        </div>

                        <!-- Team Leader Selection -->
                        <div class="form-group">
                            <label for="leader-search">Assign Team Leader</label>
                            <input
                                type="text"
                                id="leader-search"
                                placeholder="Type a name or email..."
                                autocomplete="off"
                                required />

                            <input type="hidden" name="team_leader_id" id="team-leader-id" required />

                            <!-- Suggestions dropdown -->
                            <div id="leader-results" class="autocomplete-results" style="display:none;"></div>
                        </div>

                        <button type="submit" class="create-post-btn">Create Project</button>
                    </form>
                </div>

                <aside class="kb-sidebar">
                    <div class="kb-sidebar-widget">
                        <h3>Project Tips</h3>
                        <ul class="best-practices-list">
                            <li><strong>Clear Names:</strong> Use a descriptive name that your team will recognize.</li>
                            <li><strong>Define Goals:</strong> A brief description helps align everyone.</li>
                            <li><strong>Team Leader:</strong> Select from your chosen employees to lead the project.</li>
                            <li><strong>Minimum Team:</strong> At least 4 employees required for a project.</li>
                        </ul>
                    </div>
                </aside>
            </div>
        </main>
    </div>

    <script src="create-new-project.js"></script>
    <script>
        feather.replace();
    </script>
</body>

</html>