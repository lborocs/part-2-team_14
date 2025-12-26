<?php
// load database connection
require_once __DIR__ . "/../../config/database.php";

// create db object and get PDO connection (to safely run SQL queries)
$database = new Database();
$pdo = $database->getConnection();

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
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                <div class="employees-count">
                    Showing <strong>1-20</strong> of 84 Employee Results
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
                <div class="employee-grid">
                
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
                <div class="employees-pagination">
                    <button class="pagination-btn" disabled>
                        Prev
                    </button>

                    <div class="pagination-pages">
                        <button class="pagination-page active">1</button>
                        <button class="pagination-page">2</button>
                        <button class="pagination-page">3</button>
                        <span class="pagination-ellipsis">...</span>
                        <button class="pagination-page">8</button>
                    </div>

                    <button class="pagination-btn">
                        Next
                    </button>
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






