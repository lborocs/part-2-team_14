<?php
session_start();

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

// Define same specialty colors
$specialtyColors = [
    'Project Management' => '#1565C0',
    'Strategy'           => '#0277BD',
    'Leadership'         => '#2E7D32',
    'Backend'            => '#512DA8',
    'Python'             => '#F9A825',
    'SQL'                => '#558B2F',
    'API Design'         => '#00695C',
    'Frontend'           => '#AD1457',
    'React'              => '#0288D1',
    'CSS'                => '#3949AB',
    'JavaScript'         => '#F9A825',
    'Node.js'            => '#2E7D32',
    'MongoDB'            => '#00796B',
    'DevOps'             => '#6A1B9A',
    'AWS'                => '#EF6C00',
    'Docker'             => '#0277BD',
    'CI/CD'              => '#455A64',
    'UI Design'          => '#C2185B',
    'Figma'              => '#7B1FA2',
    'Prototyping'        => '#303F9F',
];

function getEmployeeColor($userId, $bannerColors, &$colorMap) {
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

require_once __DIR__ . '/../../config/database.php';
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("Database connection failed.");
}

// ===============================
// AJAX ENDPOINT: Get Employee Names
// ===============================
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
    
    // Convert comma-separated string to array
    $ids = explode(',', $employeeIds);
    $ids = array_filter(array_map('intval', $ids));
    
    if (empty($ids)) {
        echo json_encode([]);
        exit;
    }
    
    // Build placeholders for IN clause
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    $stmt = $db->prepare("
        SELECT user_id, first_name, last_name, email, profile_picture, specialties
        FROM users
        WHERE user_id IN ($placeholders)
    ");
    $stmt->execute($ids);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format response with colors
    $response = array_map(function($emp) use ($bannerColors) {
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
// ADD TO PROJECT (POST handler)
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Get data from form
    $selectedProjects = $_POST['project_ids'] ?? [];
    $employeeIds = $_POST['employee_ids'] ?? [];
    
    // Validate input
    if (empty($selectedProjects) || empty($employeeIds)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Please select at least one project and one employee.'
        ]);
        exit;
    }
    
    // Ensure arrays
    if (!is_array($selectedProjects)) {
        $selectedProjects = [$selectedProjects];
    }
    if (!is_array($employeeIds)) {
        $employeeIds = [$employeeIds];
    }
    
    try {
        $db->beginTransaction();
        
        $addedCount = 0;
        $skippedCount = 0;
        
        // Loop through each project and each employee
        foreach ($selectedProjects as $projectId) {
            foreach ($employeeIds as $userId) {
                // Check if already a member
                $checkStmt = $db->prepare("
                    SELECT COUNT(*) FROM project_members
                    WHERE project_id = :pid AND user_id = :uid AND left_at IS NULL
                ");
                $checkStmt->execute([':pid' => $projectId, ':uid' => $userId]);
                
                if ($checkStmt->fetchColumn() > 0) {
                    $skippedCount++;
                    continue; // Already a member
                }
                
                // Insert into project_members
                $insertStmt = $db->prepare("
                    INSERT INTO project_members (project_id, user_id, project_role)
                    VALUES (:pid, :uid, 'member')
                ");
                $insertStmt->execute([
                    ':pid' => $projectId,
                    ':uid' => $userId
                ]);
                
                $addedCount++;
            }
        }
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'added' => $addedCount,
            'skipped' => $skippedCount,
            'message' => "Successfully added {$addedCount} assignment(s). {$skippedCount} skipped (already members)."
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
    <title>Make-It-All - Add to Project</title>
    <link rel="stylesheet" href="../dashboard.css">
    <link rel="stylesheet" href="../knowledge-base/knowledge-base.css">
    <link rel="stylesheet" href="../home/create-project.css">
    <link rel="stylesheet" href="add-to-project.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
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
                    <li class="active-parent"><a href="employee-directory.php"><i data-feather="users"></i>Employees</a></li>
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
                    > Add to Project
                </p>
                <h1>Add Employees to Project</h1>
            </header>

            <div class="kb-layout-wrapper">
                <div class="kb-main-content">
                    <form id="add-to-project-form" class="create-post-form">
                        
                        <!-- Selected Employees Display -->
                        <div class="form-group">
                            <label>Selected Employees</label>
                            <div id="selected-employees-display" class="selected-employees-box">
                                <p class="empty-message">No employees selected</p>
                            </div>
                        </div>

                        <!-- Project Selection (Multi-select) -->
                        <div class="form-group">
                            <label for="project-select">Select Project(s) *</label>
                            <div class="project-select-wrapper">
                                <button type="button" class="project-select-toggle" id="project-select-toggle">
                                    <span id="project-select-label">Select projects...</span>
                                    <i data-feather="chevron-down"></i>
                                </button>
                                
                                <div class="project-select-dropdown" id="project-select-dropdown" hidden>
                                    <input 
                                        type="text" 
                                        class="project-search-input" 
                                        id="project-search-input"
                                        placeholder="Search projects..."
                                        autocomplete="off"
                                    >
                                    
                                    <div class="project-checkbox-list" id="project-checkbox-list">
                                        <?php foreach ($availableProjects as $proj): ?>
                                            <label class="project-checkbox-item">
                                                <input 
                                                    type="checkbox" 
                                                    name="project_ids[]" 
                                                    value="<?= htmlspecialchars($proj['project_id']) ?>"
                                                    data-project-name="<?= htmlspecialchars($proj['project_name']) ?>"
                                                >
                                                <span><?= htmlspecialchars($proj['project_name']) ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Project Role (Locked) -->
                        <div class="form-group">
                            <label for="project-role">Project Role</label>
                            <input 
                                type="text" 
                                id="project-role" 
                                value="Team Member" 
                                readonly 
                                class="locked-input"
                            >
                            <p class="field-note">All employees will be added as Team Members</p>
                        </div>

                        <button type="submit" class="create-post-btn">
                            <i data-feather="user-plus"></i>
                            Add to Project
                        </button>
                    </form>
                </div>

                <aside class="kb-sidebar">
                    <div class="kb-sidebar-widget">
                        <h3>Assignment Tips</h3>
                        <ul class="best-practices-list">
                            <li><strong>Multiple Projects:</strong> You can add employees to multiple projects at once.</li>
                            <li><strong>Team Members:</strong> All assignments will use the "Team Member" role.</li>
                            <li><strong>Already Members:</strong> Employees already in a project will be skipped automatically.</li>
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
                <h2>Confirm Assignment</h2>
                <button type="button" class="close-btn" id="close-modal-btn">
                    <i data-feather="x"></i>
                </button>
            </div>
            <div class="modal-body">
                <p id="confirmation-message">Are you sure you want to add these employees to the selected projects?</p>
                
                <div class="confirmation-details">
                    <div class="detail-section">
                        <strong>Employees:</strong>
                        <ul id="confirm-employees-list"></ul>
                    </div>
                    
                    <div class="detail-section">
                        <strong>Projects:</strong>
                        <ul id="confirm-projects-list"></ul>
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

    <script src="add-to-project.js"></script>
    <script>feather.replace();</script>
</body>

</html>