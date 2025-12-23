<?php
session_start();

require_once __DIR__ . '/../../config/database.php';
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("Database connection failed.");
}

/**
 * AJAX endpoint INSIDE this same file:
 * create-project.php?ajax=leaders&q=an
 */
if (isset($_GET["ajax"]) && $_GET["ajax"] === "leaders") {
    header("Content-Type: application/json; charset=utf-8");

    $q = trim($_GET["q"] ?? "");
    if ($q === "" || mb_strlen($q) < 2) {
        echo json_encode([]);
        exit;
    }

    if (mb_strlen($q) > 50) $q = mb_substr($q, 0, 50);
    $term = "%{$q}%";

    // roles from your schema: team_member + technical_specialist
    $stmt = $db->prepare("
    SELECT user_id, first_name, last_name, email
    FROM users
    WHERE role IN ('team_member', 'technical_specialist', 'team_leader', 'manager')
      AND (
        CONCAT(first_name, ' ', last_name) LIKE :term
        OR email LIKE :term
      )
    ORDER BY first_name, last_name
    LIMIT 10
  ");
    $stmt->execute([":term" => $term]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(array_map(function ($u) {
        $fullName = $u["first_name"] . " " . $u["last_name"];
        return [
            "id" => (int)$u["user_id"],
            "label" => $fullName . " (" . $u["email"] . ")"
        ];
    }, $results));
    exit;
}

// ===============================
// CREATE PROJECT (POST handler)
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Must be logged in
    //   if (!isset($_SESSION['user_id'])) {
    //     die("Not logged in.");
    //   }

    // Collect fields from form
    $projectName = trim($_POST['project_name'] ?? '');
    $priority    = $_POST['priority'] ?? 'medium';
    $deadline    = $_POST['deadline'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $leaderId    = (int)($_POST['team_leader_id'] ?? 0);

    if ($projectName === '' || $deadline === '' || $leaderId <= 0) {
        die("Missing project name, deadline, or team leader.");
    }

    // Validate priority
    $allowedPriorities = ['low', 'medium', 'high'];
    if (!in_array($priority, $allowedPriorities, true)) {
        $priority = 'medium';
    }

    // ------------------------------
    // Get created_by (logged in user)
    // ------------------------------
    $createdBy = $_SESSION['user_id'] ?? null;

    // If your app stores email in session instead of user_id, look up user_id by email
    if (!$createdBy) {
        $sessionEmail = $_SESSION['email'] ?? null;

        if ($sessionEmail) {
            $stmtUser = $db->prepare("SELECT user_id FROM users WHERE email = :email LIMIT 1");
            $stmtUser->execute([':email' => $sessionEmail]);
            $createdBy = (int)($stmtUser->fetchColumn() ?: 0);
        }
    }

    if (!$createdBy) {
        die("Not logged in properly: missing user_id (and could not find user by session email).");
    }


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

    // Optional: add team leader into project_members
    $stmt2 = $db->prepare("INSERT INTO project_members (project_id, user_id, project_role)
                         VALUES (:pid, :uid, 'team_leader')");
    $stmt2->execute([
        ':pid' => $newProjectId,
        ':uid' => $leaderId
    ]);

    // Include user=email in the redirect (if your team wants that URL)
    $userEmail = $_SESSION['email'] ?? '';
    $redirect = "../project/projects.html?project=" . $newProjectId;

    if ($userEmail !== '') {
        $redirect .= "&user=" . urlencode($userEmail);
    }

    header("Location: " . $redirect);
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make-It-All - Create Personal To-Do</title>
    <link rel="stylesheet" href="../dashboard.css">
    <link rel="stylesheet" href="../knowledge-base/knowledge-base.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <link rel="stylesheet" href="create-project.css">

</head>

<body id="create-project-page">
    <div class="dashboard-container">
        <nav class="sidebar">
            <div class="nav-top">
                <div class="logo-container">
                    <img src="../logo.png" alt="Make-It-All Logo" class="logo-icon">
                </div>
                <ul class="nav-main">
                    <li class="active-parent"><a href="home.html"><i data-feather="home"></i>Home</a></li>
                    <li><a href="../project/projects.html"><i data-feather="folder"></i>Projects</a></li>
                    <li id="nav-archive" style="display: none;"><a href="../project/project-archive.html"><i data-feather="archive"></i>Project Archive</a></li>
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
                <p class="breadcrumbs">Home > Create Project</p>
                <h1>Create a New Project</h1>
            </header>

            <div class="kb-layout-wrapper">
                <div class="kb-main-content">
                    <form id="create-project-form" class="create-post-form" method="POST" action="create-project.php">
                        <div class="form-group">
                            <label for="project-name">Project Name</label>
                            <input type="text" id="project-name" placeholder="e.g., Q4 Marketing Campaign, Website Redesign" name="project_name" required>
                        </div>

                        <div class="form-group">
                            <label for="project-priority">Priority</label>
                            <select id="project-priority" name="priority" required>
                                <option value="">Select priority...</option>
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="project-deadline">Deadline</label>
                            <input type="date" id="project-deadline" name="deadline" min="<?= date('Y-m-d') ?>"
                                required>
                        </div>


                        <div class="form-group">
                            <label for="project-description">Description (Optional)</label>
                            <textarea id="project-description" rows="5" placeholder="Briefly describe this project's goals." name="description"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="leader-search">Assign Team Leader</label>

                            <input
                                type="text"
                                id="leader-search"
                                placeholder="Type a name or email..."
                                autocomplete="off"
                                required />

                            <!-- This is what gets submitted to PHP -->
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
                            <li><strong>Assign People:</strong> Select a Team Leader and at least one member to get started.</li>
                        </ul>
                    </div>
                </aside>
            </div>
        </main>
    </div>

    <script src="../app.js"></script>
</body>

</html>