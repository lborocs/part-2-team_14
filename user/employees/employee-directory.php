<?php
// load database connection
require_once __DIR__ . "/../../config/database.php";

// create db object and get PDO connection (to safely run SQL queries)
$database = new Database();
$pdo = $database->getConnection();

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
                    <img src="..logo.png" class="logo-icon">
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
                    <strong>Results:</strong> 32 Employees
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
            <div class="employee-grid">
                
                <article class="employee-card role-team-leader">

                    <!-- 1) Role-colored top band -->
                    <div class="employee-card-top">
                        <div class="employee-checkbox"></div>
                        <div class="employee-avatar"></div>
                    </div>

                    <!-- 2) Content -->
                    <div class="employee-card-body">
                        <h3 class="employee-name">Jane Doe</h3>
                        <p class="employee-role">Team Leader</p>

                        <div class="employee-block">
                            <div class="block-title">Specialities</div>
                            <div class="tag-row">
                                <span class="tag">UI</span>
                                <span class="tag">CSS</span>
                            </div>
                        </div>

                        <div class="employee-block">
                            <div class="block-title">Projects</div>
                            <div class="tag-row">
                                <span class="tag tag-muted">Dashboard</span>
                            </div>
                        </div>

                    </div>

                </article>



            </div>


        </main>

    </div>

    <script>
        feather.replace();
    </script>

</body>
</html>






