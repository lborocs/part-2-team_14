<?php
/* ============================
   BOOTSTRAP & DATABASE
   ============================ */
// Start session
session_start();

// Load database configuration
require_once __DIR__ . "/../../config/database.php";

// Create DB instance & PDO connection
$database = new Database();
$pdo = $database->getConnection();

/* ============================
   ACCESS CONTROL
   ============================ */
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.html');
    exit();
}

// Check if user is a manager
if ($_SESSION['role'] !== 'manager') {
    http_response_code(403);
    die('Access denied. This page is only available to managers.');
}

/* =============================
   FILTER OPTIONS (Specialties + Projects)
   ============================= */

// Get all specialties (from active, non-manager users)
$specStmt = $pdo->prepare("
    SELECT u.specialties
    FROM users u
    WHERE u.is_active = TRUE
      AND u.role != 'manager'
      AND u.specialties IS NOT NULL
      AND u.specialties != ''
");
$specStmt->execute();

$allSpecialties = [];
while ($row = $specStmt->fetch(PDO::FETCH_ASSOC)) {
    $raw = $row['specialties'];

    // specialties may be JSON array OR comma-separated text
    $arr = json_decode($raw, true);
    if (!is_array($arr)) {
        $arr = array_map('trim', explode(',', $raw));
    }

    foreach ($arr as $s) {
        $s = trim((string)$s);
        if ($s !== '') {
            $allSpecialties[$s] = true;
        }
    }
}
$allSpecialties = array_keys($allSpecialties);
sort($allSpecialties, SORT_NATURAL | SORT_FLAG_CASE);


// Get all projects that currently have members
$projStmt = $pdo->prepare("
    SELECT DISTINCT p.project_id, p.project_name
    FROM projects p
    INNER JOIN project_members pm
        ON pm.project_id = p.project_id
       AND pm.left_at IS NULL
    ORDER BY p.project_name ASC
");
$projStmt->execute();

$allProjects = $projStmt->fetchAll(PDO::FETCH_ASSOC);

/* =============================
   PAGINATION CONFIGURATION
   ============================= */
$perPage = 20;

// Current page (defaults to 1)
$page = isset($_GET['page']) && is_numeric($_GET['page'])
    ? max(1, (int) $_GET['page'])
    : 1;
$offset = ($page - 1) * $perPage;

/* SORT BY CONFIGURATION */
$allowedSorts = [
    'name_asc'      => 'u.first_name ASC, u.last_name ASC',
    'projects_asc'  => 'project_count ASC',
    'projects_desc' => 'project_count DESC'
];

$sortKey = $_GET['sort'] ?? 'name_asc';
$orderBy = $allowedSorts[$sortKey] ?? $allowedSorts['name_asc'];

/* =============================
   READ FILTER INPUTS
   ============================= */
$selectedSpecialties = $_GET['specialty'] ?? [];
$selectedProjects    = $_GET['project'] ?? [];

if (!is_array($selectedSpecialties)) {
    $selectedSpecialties = [$selectedSpecialties];
}
if (!is_array($selectedProjects)) {
    $selectedProjects = [$selectedProjects];
}

$selectedSpecialties = array_values(
    array_filter(
        array_map('trim', $selectedSpecialties),
        fn($v) => $v !== ''
    )
);

$selectedProjects = array_values(
    array_filter($selectedProjects, fn($v) => is_numeric($v))
);
$selectedProjects = array_map('intval', $selectedProjects);


/* ====================================================
   TOTAL EMPLOYEE COUNT (for pagination + meta text)
   ==================================================== */
$countSql = "
    SELECT COUNT(DISTINCT u.user_id)
    FROM users u
    WHERE u.is_active = TRUE
      AND u.role != 'manager'
";

$countParams = [];

/* ---------- Specialty filter ---------- */
if (!empty($selectedSpecialties)) {
    foreach ($selectedSpecialties as $i => $spec) {
        $countSql .= " AND JSON_CONTAINS(u.specialties, :spec_json_$i, '$')";
        $countParams[":spec_json_$i"] = json_encode($spec);
    }
}

/* ---------- Project filter ---------- */
if (!empty($selectedProjects)) {
    $in = [];

    foreach ($selectedProjects as $i => $pid) {
        $key = ":pid_$i";
        $in[] = $key;
        $countParams[$key] = $pid;
    }

    $countSql .= "
        AND EXISTS (
            SELECT 1
            FROM project_members pm2
            WHERE pm2.user_id = u.user_id
              AND pm2.left_at IS NULL
              AND pm2.project_id IN (" . implode(',', $in) . ")
        )
    ";
}

$countStmt = $pdo->prepare($countSql);
foreach ($countParams as $k => $v) {
    $countStmt->bindValue($k, $v);
}
$countStmt->execute();

$totalEmployees = (int) $countStmt->fetchColumn();
$totalPages = (int) ceil($totalEmployees / $perPage);
$range = 1;

/* =================================================
   PRESERVE QUERY PARAMETERS (AJAX / FILTER SAFE)
   ================================================= */
$queryParams = $_GET;
unset($queryParams['page']); // page handled seperately 

$queryString = http_build_query($queryParams);
$queryString = $queryString ? '&' . $queryString : '';

/* =======================
   EMPLOYEE LIST QUERY
   ======================= */
$sql = "
SELECT
    u.user_id,
    u.email,
    u.first_name,
    u.last_name,
    u.role,
    u.profile_picture,
    u.specialties,
    COUNT(DISTINCT pm.project_id) AS project_count,
    GROUP_CONCAT(DISTINCT p.project_name SEPARATOR ', ') AS projects
FROM users u
LEFT JOIN project_members pm 
    ON u.user_id = pm.user_id 
    AND pm.left_at IS NULL
LEFT JOIN projects p 
    ON pm.project_id = p.project_id
WHERE u.is_active = TRUE
  AND u.role != 'manager'
";

$params = [];

// specialty filter (ALL selected specialties must match)
if (!empty($selectedSpecialties)) {
    // DEBUG: Let's see what we're filtering for
    error_log("Selected specialties: " . print_r($selectedSpecialties, true));
    
    foreach ($selectedSpecialties as $i => $spec) {
        $sql .= " AND JSON_CONTAINS(u.specialties, :spec_json_$i, '$')";
        $params[":spec_json_$i"] = json_encode($spec);
        error_log("Filter $i: " . json_encode($spec));
    }
}

// project filter
if (!empty($selectedProjects)) {
    $in = [];
    foreach ($selectedProjects as $i => $pid) {
        $key = ":pid_$i";
        $in[] = $key;
        $params[$key] = $pid;
    }

    $sql .= "
      AND EXISTS (
        SELECT 1
        FROM project_members pm2
        WHERE pm2.user_id = u.user_id
          AND pm2.left_at IS NULL
          AND pm2.project_id IN (" . implode(',', $in) . ")
      )
    ";
}

$sql .= "
GROUP BY u.user_id
ORDER BY $orderBy
LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =======================
   SPECIALTY → CSS CLASS MAP
   ======================= */
$specialtyClassMap = [
    'Project Management' => 'spec-project-management',
    'Strategy'           => 'spec-strategy',
    'Leadership'         => 'spec-leadership',
    'Backend'            => 'spec-backend',
    'Python'             => 'spec-python',
    'SQL'                => 'spec-sql',
    'API Design'         => 'spec-api-design',
    'Frontend'           => 'spec-frontend',
    'React'              => 'spec-react',
    'CSS'                => 'spec-css',
    'JavaScript'         => 'spec-javascript',
    'Node.js'            => 'spec-node-js',
    'MongoDB'            => 'spec-mongodb',
    'DevOps'             => 'spec-devops',
    'AWS'                => 'spec-aws',
    'Docker'             => 'spec-docker',
    'CI/CD'              => 'spec-ci-cd',
    'UI Design'          => 'spec-ui-design',
    'Figma'              => 'spec-figma',
    'Prototyping'        => 'spec-prototyping',
];

/* ==============================================
   RESULT RANGE (e.g. "Showing 21–40 of 132")
   ============================================== */ 
$start = $totalEmployees > 0 ? $offset + 1 : 0;
$end = min($offset + count($employees), $totalEmployees);

/* ========================
   AJAX REQUEST HANDLING
   ======================== */
$isAjax = isset($_GET['ajax']) && $_GET['ajax'] === '1';

if ($isAjax) {
    // Only return the grid + pagination (used by employees.js)
    include __DIR__ . '/partials/employee-grid.php';
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>Employee Directory</title>

    <!--Shared dashboard styles-->
    <link rel="stylesheet" href="../dashboard.css">
    <!--Employees page styles-->
    <link rel="stylesheet" href="employees.css">

    <!--Google Font-->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>

</head>
<body>
    <?php include '../to-do/todo_widget.php'; ?>

    <div class="dashboard-container">

        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="nav-top">
                <div class="logo-container">
                    <img src="../logo.png" class="logo-icon">
                </div>
                <ul class="nav-main">
                    <li><a href="../home/home.php"><i data-feather="home"></i>Home</a></li>
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
        </nav>

        <!-- Main content (right-side content) -->
        <main class="main-content">

            <!-- Page header (reuse project-header styles) -->
            <header class="project-header">
                <div class="project-header-top">
                    <div class="breadcrumbs-title">
                        <h1>Employee Directory</h1>
                    </div>
                </div>
            </header>

            <!-- EMPLOYEE CONTROLS (search bar and action buttons) -->
            <div class="employees-controls">

                <!-- Search bar -->
                <div class="employee-search">
                    <i data-feather="search"></i>
                    <input
                        type="text"
                        name="search"
                        placeholder="Search employees by name or speciality"
                    >
                </div>

                <!-- Action buttons -->
                <div class="employee-actions">
                    <button class="select-mode-btn" id="select-mode-btn">Select</button>
                    <button class="select-all-btn" id="select-all-btn" hidden>Select All</button>
                    <button class="cancel-select-btn" id="cancel-select-btn" hidden>Cancel</button>
                </div>      
            </div>
            <div class="section-divider"></div>

            <div class="employee-meta">

                <!--Left: employee results count -->
                <div id="employees-count" class="employees-count">
                <?php if ($totalEmployees === 0): ?>
                    No Results Found
                <?php else: ?>
                    Showing <strong><?= $start ?>-<?= $end ?></strong>
                    of <?= $totalEmployees ?> Employee Results
                <?php endif; ?>
                </div>

                <!-- Right: sort & filter -->
                <div class="employees-tools">
                    <div class="sort-wrap">
                        <span class="sort-label">Sort by:</span>
                        <select class="sort-dropdown" id="sortEmployees">
                            <option value="name_asc">Name (A-Z)</option>
                            <option value="projects_asc">Project Count (Low → High)</option>
                            <option value="projects_desc">Project Count (High → Low)</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <button class="filter-toggle" id="filter-toggle" type="button">
                            Filters
                        </button>

                        <div class="filter-panel" id="filter-panel" hidden>
                            <!-- SPECIALTIES -->
                            <div class="filter-section">
                                <span>Specialties</span>

                                <input
                                    type="text"
                                    class="filter-search"
                                    id="filter-specialty-search"
                                    placeholder="Search specialties..."
                                    autocomplete="off"
                                >

                                <div class="filter-list" id="filter-specialty-list">
                                    <?php foreach ($allSpecialties as $spec): ?>
                                        <?php $checked = in_array($spec, $selectedSpecialties, true); ?>
                                        <label class="filter-check">
                                            <input
                                                type="checkbox"
                                                name="specialty[]"
                                                class="filter-specialty"
                                                value="<?= htmlspecialchars($spec) ?>"
                                                <?= $checked ? 'checked' : '' ?>
                                            >
                                            <span><?= htmlspecialchars($spec) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- PROJECTS -->
                            <div class="filter-section">
                                <span>Projects</span>

                                <input
                                    type="text"
                                    class="filter-search"
                                    id="filter-project-search"
                                    placeholder="Search projects..."
                                    autocomplete="off"
                                >

                                <div class="filter-list" id="filter-project-list">
                                    <?php foreach ($allProjects as $proj): ?>
                                        <?php $pid = (int)$proj['project_id']; ?>
                                        <?php $checked = in_array($pid, $selectedProjects, true); ?>
                                        <label class="filter-check">
                                            <input
                                                type="checkbox"
                                                name="project[]"
                                                class="filter-project"
                                                value="<?= $pid ?>"
                                                <?= $checked ? 'checked' : '' ?>
                                            >
                                            <span><?= htmlspecialchars($proj['project_name']) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="filter-actions">
                                <button
                                    type="button"
                                    class="filter-clear"
                                    id="filter-clear"
                                >
                                    Clear
                                </button>

                                <button
                                    type="button"
                                    class="filter-apply"
                                    id="filter-apply"
                                >
                                    Apply
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="section-divider"></div>

            <!--Employee grid -->
            <div class="employee-section">
                <div id="employee-grid" class="employee-grid">
                
                    <?php foreach ($employees as $employee): ?>

                        <?php

                            // Decode specialties (stored as JSON or comma text)
                            $specialties = [];
                            if (!empty($employee['specialties'])) {
                                $specialties = json_decode($employee['specialties'], true) 
                                    ?? explode(',', $employee['specialties']);
                            }
                        ?>

                        <article 
                            class="employee-card" 
                            data-profile-url="employee-profile.php?id=<?= urlencode($employee['user_id']) ?>"
                            data-employee-id="<?= $employee['user_id'] ?>"
                        >

                            <div class="employee-card-top">
                                <div class="employee-avatar">
                                    <img
                                        src="<?= htmlspecialchars($employee['profile_picture']) ?>"
                                        alt="Avatar"
                                    >
                                </div>
                            </div>

                            <div class="employee-card-body">
                                <h3 class="employee-name">
                                    <?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?>
                                </h3>

                                <!-- SPECIALTIES -->
                                <div class="employee-specialties">
                                    <div class="block-title">Specialties</div>

                                    <div class="specialties-container collapsed">
                                        <?php foreach ($specialties as $skill): ?>
                                            <?php
                                                $skillName  = trim($skill);
                                                $skillClass = $specialtyClassMap[$skillName] ?? 'spec-default';
                                            ?>
                                            <span class="specialty-pill <?= $skillClass ?>">
                                                <?= htmlspecialchars($skillName) ?>
                                            </span>
                                        <?php endforeach; ?>

                                    </div>

                                    <button type="button" class="see-more-btn" hidden>...</button>
                                </div>

                                <div class="employee-card-footer">
                                    <i data-feather="mail"></i>
                                    <span class="employee-email">
                                        <?= htmlspecialchars($employee['email']) ?>
                                    </span>
                                </div>
                                    
                            </div>


                        </article>

                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <div id="employees-pagination" class="employees-pagination">
                    <!-- prev button -->
                    <?php if ($page > 1): ?>
                        <a class="pagination-btn" href="?page=<?= $page - 1 ?><?= $queryString ?>">
                            Prev
                        </a>
                    <?php else: ?>
                        <button class="pagination-btn" disabled>
                            Prev
                        </button>
                    <?php endif; ?>

                    <!-- page numbers -->
                    <div class="pagination-pages">
                    <?php
                    // Always show first page
                    if ($page > 1) {
                        echo '<a class="pagination-page" href="?page=1' . $queryString . '">1</a>';
                    }

                    // Left ellipsis
                    if ($page > $range + 2) {
                        echo '<span class="pagination-ellipsis">…</span>';
                    }

                    // Middle pages
                    for ($i = max(1, $page - $range); $i <= min($totalPages, $page + $range); $i++) {
                        if ($i == $page) {
                            echo '<span class="pagination-page active">' . $i . '</span>';
                        } else {
                            echo '<a class="pagination-page" href="?page=' . $i . $queryString . '">' . $i . '</a>';
                        }
                    }

                    // Right ellipsis
                    if ($page < $totalPages - ($range + 1)) {
                        echo '<span class="pagination-ellipsis">…</span>';
                    }

                    // Always show last page
                    if ($page < $totalPages) {
                        echo '<a class="pagination-page" href="?page=' . $totalPages . $queryString . '">' . $totalPages . '</a>';
                    }
                    ?>
                    </div>

                    <!-- Next button -->
                    <?php if ($page < $totalPages): ?>
                        <a class="pagination-btn" href="?page=<?= $page + 1 ?><?= $queryString ?>">
                            Next
                        </a>
                    <?php else: ?>
                        <button class="pagination-btn" disabled>
                            Next
                        </button>
                    <?php endif; ?>
                </div>

            </div>

            <!-- Fixed bottom action bar (hidden by default) -->
            <div class="bottom-action-bar" id="bottom-action-bar" hidden>
                <div class="selection-info">
                    <span id="selection-count">0 employees selected</span>
                </div>
                <div class="bottom-actions">
                    <button class="action-btn" id="assign-task-btn">
                        <span>Assign Task</span>
                    </button>
                    <button class="action-btn" id="add-to-project-btn">
                        <i data-feather="user-plus"></i>
                        <span>Add to Project</span>
                    </button>
                    <button class="action-btn" id="create-project-btn">
                        <i data-feather="plus"></i>
                        <span>Create New Project</span>
                    </button>
                </div>
            </div>
            <!-- Floating Todo Widget -->
<div class="floating-todo-widget" id="floating-todo-widget">
  <button class="todo-fab" id="todo-fab" aria-label="Toggle personal todos">
    <i data-feather="check-square"></i>
    <span class="todo-badge" id="todo-badge">0</span>
  </button>

  <div class="todo-panel" id="todo-panel" hidden>
    <div class="todo-panel-header">
      <h3>My To-Dos</h3>
      <button class="todo-close-btn" id="todo-close-btn" aria-label="Close">
        <i data-feather="x"></i>
      </button>
    </div>

    <div class="todo-panel-content">
      <div class="todo-panel-list" id="floating-todo-list">
        <!-- Populated by JavaScript -->
      </div>
      
      <div class="todo-panel-footer">
        <a href="../create-todo.html" class="todo-add-btn">
          <i data-feather="plus"></i> Add New To-Do
        </a>
      </div>
    </div>
  </div>
</div>
        </main>

    </div>
    <script src="employees.js"></script>
    <script>
        feather.replace();
    </script>
</body>
</html>






