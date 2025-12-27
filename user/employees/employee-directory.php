<?php
// load database connection
require_once __DIR__ . "/../../config/database.php";

// create db object and get PDO connection (to safely run SQL queries)
$database = new Database();
$pdo = $database->getConnection();

// pagination configuration 
$perPage = 20;
$page = isset($_GET['page']) && is_numeric($_GET['page'])
    ? max(1, (int) $_GET['page'])
    : 1;
$offset = ($page - 1) * $perPage;

$countStmt = $pdo->prepare("
    SELECT COUNT(DISTINCT u.user_id)
    FROM users u
    WHERE u.is_active = TRUE
");
$countStmt->execute();
$totalEmployees = (int) $countStmt->fetchColumn();
$totalPages = (int) ceil($totalEmployees / $perPage);
$range = 1;

// Preserve existing query parameters (except page)
$queryParams = $_GET;
unset($queryParams['page']);

$queryString = http_build_query($queryParams);
$queryString = $queryString ? '&' . $queryString : '';

$sql = "
SELECT
    u.user_id,
    u.email,
    u.first_name,
    u.last_name,
    u.role,
    u.profile_picture,
    u.specialties,
    GROUP_CONCAT(DISTINCT p.project_name SEPARATOR ', ') AS projects
FROM users u
LEFT JOIN project_members pm 
    ON u.user_id = pm.user_id 
    AND pm.left_at IS NULL
LEFT JOIN projects p 
    ON pm.project_id = p.project_id
WHERE u.is_active = TRUE
GROUP BY u.user_id
ORDER BY u.first_name ASC
LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Results range display numbers 
$start = $totalEmployees > 0 ? $offset + 1 : 0;
$end = min($offset + count($employees), $totalEmployees);

$isAjax = isset($_GET['ajax']) && $_GET['ajax'] === '1';

if ($isAjax) {
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

    <div class="dashboard-container">

        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="nav-top">
                <div class="logo-container">
                    <img src="../logo.png" class="logo-icon">
                </div>
                <ul class="nav-main">
                    <li><a href="../home/home.html"><i data-feather="home"></i>Home</a></li>
                    <li><a href="../project/projects.html"><i data-feather="folder"></i>Projects</a></li>
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
                    <button class="create-post-btn">Assign Task</button>
                    <button class="create-post-btn">Add to Project</button>
                    <button class="create-post-btn">Create Project</button>
                </div>         
            </div>
            <div class="section-divider"></div>

            <div class="employee-meta">

                <!--Left: employee results count -->
                <div id="employees-count" class="employees-count">
                    Showing <strong><?= $start ?>-<?= $end ?></strong>
                    of <?= $totalEmployees ?> Employee Results
                </div>

                <!-- Right: sort & filter -->
                <div class="employees-tools">
                    <select class="employees-sort">
                        <option value="">Sort by</option>
                        <option value="name_asc">Name (A-Z)</option>
                        <option value="name_desc">Name (Z-A)</option>
                        <option value="speciality">Speciality (A-Z)</option>
                        <option value="project_count_asc">Project Count (low-high)</option>
                        <option value="project_count_desc">Project Count (high-low)</option>
                    </select>

                    <button class="filter-btn" type="button">
                        Filter
                    </button>
                </div>
            </div>

            <div class="section-divider"></div>

            <!--Employee grid -->
            <div class="employee-section">
                <div id="employee-grid" class="employee-grid">
                
                    <?php foreach ($employees as $employee): ?>

                        <?php
                            // Map role -> CSS class 
                            $roleClass = match ($employee['role']) {
                                'manager' => 'role-manager',
                                'team_leader' => 'role-team-leader',
                                'team_member' => 'role-team-member',
                                'technical_specialist' => 'role-technical-specialist',
                                default => 'role-team-member',
                            };

                            // Decode specialties (stored as JSON or comma text)
                            $specialties = [];
                            if (!empty($employee['specialties'])) {
                                $specialties = json_decode($employee['specialties'], true) 
                                    ?? explode(',', $employee['specialties']);
                            }
                        ?>

                        <article 
                            class="employee-card <?= $roleClass ?>" 
                            data-profile-url="employee-profile.php?id=<?= urlencode($employee['user_id']) ?>"
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

                                <p class="employee-role">
                                    <?= ucfirst(str_replace('_', ' ', $employee['role'])) ?>
                                </p>

                                <!-- SPECIALTIES -->
                                <div class="block-title">Specialties</div>
                                    <div class="specialties-container collapsed">
                                        <?php foreach ($specialties as $skill): ?>
                                            <span class="tag"><?= htmlspecialchars(trim($skill)) ?></span>
                                        <?php endforeach; ?>
                                    </div>

                                    <button type="button" class="see-more-btn" hidden>
                                        See more
                                    </button>

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
        </main>

    </div>

    <script>
        feather.replace();
    </script>

    <script src="employees.js"></script>

</body>
</html>






