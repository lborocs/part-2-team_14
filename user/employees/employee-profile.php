<?php
require_once __DIR__ . "/../../config/database.php";

// Start session for access control
session_start();

// Check login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.html');
    exit();
}

// Validate employee ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Invalid employee ID');
}

$employeeId = (int) $_GET['id'];

$database = new Database();
$pdo = $database->getConnection();

// ============================================
// 1. FETCH EMPLOYEE BASIC INFO
// ============================================
$stmt = $pdo->prepare("
    SELECT user_id, first_name, last_name, email, role, profile_picture, specialties
    FROM users
    WHERE user_id = ?
");
$stmt->execute([$employeeId]);
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$employee) {
    die('Employee not found');
}

$fullName = htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']);
$roleDisplay = ucfirst(str_replace('_', ' ', $employee['role']));

// Decode specialties for display
$specialties = [];
if (!empty($employee['specialties'])) {
    $specialties = json_decode($employee['specialties'], true) 
        ?? explode(',', $employee['specialties']);
    $specialties = array_map('trim', $specialties);
}

// ============================================
// 2. FETCH EMPLOYEE'S PROJECTS
// ============================================
$stmt = $pdo->prepare("
    SELECT DISTINCT p.project_id, p.project_name, p.status
    FROM projects p
    INNER JOIN project_members pm ON p.project_id = pm.project_id
    WHERE pm.user_id = ?
      AND pm.left_at IS NULL
    ORDER BY p.project_name
");
$stmt->execute([$employeeId]);
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================
// 3. FETCH TASK STATISTICS (ALL PROJECTS)
// ============================================
// Active tasks count (not completed)
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count
    FROM tasks t
    INNER JOIN task_assignments ta ON t.task_id = ta.task_id
    WHERE ta.user_id = ?
      AND t.status != 'completed'
");
$stmt->execute([$employeeId]);
$activeTasks = (int) $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Overdue tasks count
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count
    FROM tasks t
    INNER JOIN task_assignments ta ON t.task_id = ta.task_id
    WHERE ta.user_id = ?
      AND t.status != 'completed'
      AND t.deadline < CURDATE()
");
$stmt->execute([$employeeId]);
$overdueTasks = (int) $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Project count
$projectCount = count($projects);

// ============================================
// 4. FETCH TASK BREAKDOWN FOR DONUT CHART (ALL PROJECTS)
// ============================================
$stmt = $pdo->prepare("
    SELECT 
        t.status,
        COUNT(*) as count
    FROM tasks t
    INNER JOIN task_assignments ta ON t.task_id = ta.task_id
    WHERE ta.user_id = ?
    GROUP BY t.status
");
$stmt->execute([$employeeId]);
$taskBreakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Convert to associative array
$taskStats = [
    'to_do'       => 0,
    'in_progress' => 0,
    'review'      => 0,
    'completed'   => 0
];

foreach ($taskBreakdown as $row) {
    if (isset($taskStats[$row['status']])) {
        $taskStats[$row['status']] = (int) $row['count'];
    }
}

$totalTasks = array_sum($taskStats);

// ============================================
// 5. AJAX ENDPOINT: CHART DATA BY PROJECT
// ============================================
if (isset($_GET['ajax']) && $_GET['ajax'] === 'chart_data') {
    header('Content-Type: application/json');
    
    $projectFilter = $_GET['project_id'] ?? 'all';
    
    if ($projectFilter === 'all') {
        echo json_encode([
            'success' => true,
            'data'    => $taskStats,
            'total'   => $totalTasks
        ]);
    } else {
        $stmt = $pdo->prepare("
            SELECT 
                t.status,
                COUNT(*) as count
            FROM tasks t
            INNER JOIN task_assignments ta ON t.task_id = ta.task_id
            WHERE ta.user_id = ?
              AND t.project_id = ?
            GROUP BY t.status
        ");
        $stmt->execute([$employeeId, (int) $projectFilter]);
        $filtered = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $filteredStats = [
            'to_do'       => 0,
            'in_progress' => 0,
            'review'      => 0,
            'completed'   => 0
        ];
        
        foreach ($filtered as $row) {
            if (isset($filteredStats[$row['status']])) {
                $filteredStats[$row['status']] = (int) $row['count'];
            }
        }
        
        echo json_encode([
            'success' => true,
            'data'    => $filteredStats,
            'total'   => array_sum($filteredStats)
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
    <title><?= $fullName ?> - Employee Profile</title>
    
    <!-- Shared dashboard styles -->
    <link rel="stylesheet" href="../dashboard.css">
    
    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/favicon.png">
    
    <!-- Feather Icons -->
    <script src="https://unpkg.com/feather-icons"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        /* ================================
           EMPLOYEE PROFILE SPECIFIC STYLES
           ================================ */
        .profile-page {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Banner Section */
        .profile-banner {
            background: linear-gradient(135deg, #4A90A4 0%, #2C7873 100%);
            color: white;
            padding: 30px 40px;
            border-radius: 0 0 16px 16px;
            margin: -20px -40px 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .banner-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
        }

        .banner-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .profile-avatar-large {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 4px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
            background: #fff;
        }

        .profile-avatar-large img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-basic-info h1 {
            font-size: 32px;
            font-weight: 700;
            margin: 0 0 8px 0;
            color: white;
        }

        .profile-basic-info .role {
            font-size: 16px;
            opacity: 0.9;
            margin: 0 0 4px 0;
        }

        .profile-basic-info .email {
            font-size: 14px;
            opacity: 0.8;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .banner-right {
            display: flex;
            gap: 12px;
        }

        .banner-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            color: white;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
        }

        .banner-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.4);
            transform: translateY(-1px);
        }

        .banner-btn i {
            width: 16px;
            height: 16px;
        }

        /* Analytics Section */
        .analytics-section {
            padding: 0 40px;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }

        /* Content Grid */
        .profile-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }

        /* Specialties Card */
        .specialties-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }

        .specialties-card h2 {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin: 0 0 16px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .specialties-card h2 i {
            width: 20px;
            height: 20px;
            color: #4A90A4;
        }

        .specialties-container {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        /* Projects Card */
        .projects-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }

        .projects-card h2 {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin: 0 0 16px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .projects-card h2 i {
            width: 20px;
            height: 20px;
            color: #4A90A4;
        }

        .projects-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .project-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #4A90A4;
        }

        .project-name {
            font-weight: 500;
            color: #333;
        }

        .project-status {
            font-size: 12px;
            padding: 4px 10px;
            border-radius: 4px;
            font-weight: 600;
        }

        .project-status.active {
            background: #E8F5E9;
            color: #2E7D32;
        }

        .project-status.completed {
            background: #E3F2FD;
            color: #1565C0;
        }

        .no-projects {
            text-align: center;
            padding: 20px;
            color: #888;
            font-style: italic;
        }

        /* Statistics Cards */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            display: flex;
            align-items: center;
            gap: 16px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        }

        .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .stat-icon svg {
            width: 28px;
            height: 28px;
            color: white;
        }

        .stat-icon.active {
            background: linear-gradient(135deg, #4285F4 0%, #5B9BF8 100%);
        }

        .stat-icon.overdue {
            background: linear-gradient(135deg, #D93025 0%, #EA4335 100%);
        }

        .stat-icon.projects {
            background: linear-gradient(135deg, #34A853 0%, #46B865 100%);
        }

        .stat-content {
            flex: 1;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #333;
            line-height: 1.1;
        }

        .stat-label {
            font-size: 14px;
            color: #666;
            margin-top: 4px;
        }

        /* Donut Chart Card */
        .chart-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            margin-bottom: 40px;
        }

        .chart-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .chart-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chart-title svg {
            width: 22px;
            height: 22px;
            color: #4A90A4;
        }

        .chart-filter {
            position: relative;
        }

        .chart-filter select {
            appearance: none;
            background: #f5f7fa;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 10px 36px 10px 14px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            color: #333;
            cursor: pointer;
            min-width: 200px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .chart-filter select:hover {
            border-color: #4A90A4;
        }

        .chart-filter select:focus {
            outline: none;
            border-color: #4A90A4;
            box-shadow: 0 0 0 3px rgba(74, 144, 164, 0.15);
        }

        .chart-filter::after {
            content: '';
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            width: 0;
            height: 0;
            border-left: 5px solid transparent;
            border-right: 5px solid transparent;
            border-top: 5px solid #666;
            pointer-events: none;
        }

        .chart-body {
            display: flex;
            align-items: center;
            gap: 40px;
        }

        .chart-wrapper {
            position: relative;
            width: 220px;
            height: 220px;
            flex-shrink: 0;
        }

        .chart-center {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }

        .chart-center-value {
            font-size: 36px;
            font-weight: 700;
            color: #333;
            line-height: 1;
        }

        .chart-center-label {
            font-size: 13px;
            color: #888;
            margin-top: 4px;
        }

        .chart-legend {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .legend-color {
            width: 14px;
            height: 14px;
            border-radius: 4px;
            flex-shrink: 0;
        }

        .legend-color.todo       { background: #D93025; }
        .legend-color.inprogress { background: #E6A100; }
        .legend-color.review     { background: #34A853; }
        .legend-color.completed  { background: #4285F4; }

        .legend-text {
            flex: 1;
            font-size: 14px;
            color: #333;
        }

        .legend-value {
            font-size: 14px;
            font-weight: 600;
            color: #333;
        }

        /* Empty state */
        .chart-empty {
            text-align: center;
            padding: 40px;
            color: #888;
        }

        .chart-empty svg {
            width: 48px;
            height: 48px;
            margin-bottom: 12px;
            opacity: 0.5;
        }

        /* Specialty Pill Colors - Reuse from employee-directory styles */
        .specialty-pill {
            display: inline-flex;
            align-items: center;
            height: 26px;
            padding: 0 12px;
            font-size: 13px;
            line-height: 26px;
            font-weight: 600;
            border-radius: 999px;
            white-space: nowrap;
        }

        .spec-default {
            background:#ECEFF1;
            color:#455A64;
        }

        /* Specialty Color Palette */
        .spec-project-management { background:#E3F2FD; color:#1565C0; }
        .spec-strategy           { background:#E1F5FE; color:#0277BD; }
        .spec-leadership         { background:#E8F5E9; color:#2E7D32; }
        .spec-backend            { background:#EDE7F6; color:#512DA8; }
        .spec-python             { background:#FFFDE7; color:#F9A825; }
        .spec-sql                { background:#F1F8E9; color:#558B2F; }
        .spec-api-design         { background:#E0F2F1; color:#00695C; }
        .spec-frontend           { background:#FCE4EC; color:#AD1457; }
        .spec-react              { background:#E3F2FD; color:#0288D1; }
        .spec-css                { background:#E8EAF6; color:#3949AB; }
        .spec-javascript         { background:#FFF8E1; color:#F9A825; }
        .spec-node-js            { background:#E8F5E9; color:#2E7D32; }
        .spec-mongodb            { background:#E0F2F1; color:#00796B; }
        .spec-devops             { background:#F3E5F5; color:#6A1B9A; }
        .spec-aws                { background:#FFF3E0; color:#EF6C00; }
        .spec-docker             { background:#E1F5FE; color:#0277BD; }
        .spec-ci-cd              { background:#ECEFF1; color:#455A64; }
        .spec-ui-design          { background:#FCE4EC; color:#C2185B; }
        .spec-figma              { background:#F3E5F5; color:#7B1FA2; }
        .spec-prototyping        { background:#E8EAF6; color:#303F9F; }

        /* ================================
           RESPONSIVE
           ================================ */
        @media (max-width: 900px) {
            .stats-row {
                grid-template-columns: 1fr;
            }
            
            .chart-body {
                flex-direction: column;
            }
            
            .profile-content {
                grid-template-columns: 1fr;
            }
            
            .banner-content {
                flex-direction: column;
                text-align: center;
            }
            
            .banner-left {
                flex-direction: column;
                text-align: center;
            }
        }

        @media (max-width: 768px) {
            .profile-banner {
                padding: 20px;
                margin: -20px -20px 20px;
            }
            
            .analytics-section {
                padding: 0 20px;
            }
        }
    </style>
</head>
<body>
    <?php include '../to-do/todo_widget.php'; 
    ?>

    <div class="dashboard-container">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="nav-top">
                <div class="logo-container">
                    <img src="../logo.png" alt="Make-It-All Logo" class="logo-icon">
                </div>
                <ul class="nav-main">
                    <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'manager' || $_SESSION['role'] === 'team_leader')): ?>
                        <li><a href="../home/home.php"><i data-feather="home"></i>Home</a></li>
                    <?php endif; ?>
                    <li><a href="../project/projects-overview.php"><i data-feather="folder"></i>Projects</a></li>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'manager'): ?>
                        <li class="active-parent">
                            <a href="employee-directory.php"><i data-feather="users"></i>Employees</a>
                        </li>
                    <?php endif; ?>
                    <li><a href="../knowledge-base/knowledge-base.html"><i data-feather="book-open"></i>Knowledge Base</a></li>
                </ul>
            </div>
            <div class="nav-footer">
                <ul>
                    <li><a href="../settings.html"><i data-feather="settings"></i>Settings</a></li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="main-content">
            <!-- Page Header -->
            <header class="project-header">
                <div class="project-header-top">
                    <div class="breadcrumbs-title">
                        <h1>Employee Profile</h1>
                    </div>
                </div>
            </header>

            <!-- Profile Banner -->
            <section class="profile-banner">
                <div class="banner-content">
                    <div class="banner-left">
                        <div class="profile-avatar-large">
                            <img src="<?= htmlspecialchars($employee['profile_picture']) ?>" alt="<?= $fullName ?>">
                        </div>
                        <div class="profile-basic-info">
                            <h1><?= $fullName ?></h1>
                            <p class="role"><?= $roleDisplay ?></p>
                            <p class="email">
                                <i data-feather="mail"></i>
                                <?= htmlspecialchars($employee['email']) ?>
                            </p>
                        </div>
                    </div>
                    <div class="banner-right">
                        <a href="employee-directory.php" class="banner-btn">
                            <i data-feather="arrow-left"></i>
                            Back to Directory
                        </a>
                        <a href="mailto:<?= htmlspecialchars($employee['email']) ?>" class="banner-btn">
                            <i data-feather="mail"></i>
                            Send Email
                        </a>
                        <?php if ($_SESSION['role'] === 'manager'): ?>
                        <a href="#" class="banner-btn" onclick="assignToProject()">
                            <i data-feather="user-plus"></i>
                            Add to Project
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <!-- Analytics Section -->
            <section class="analytics-section">
                <!-- Specialties & Projects Row -->
                <div class="profile-content">
                    <!-- Specialties Card -->
                    <div class="specialties-card">
                        <h2>
                            <i data-feather="star"></i>
                            Specialties
                        </h2>
                        <div class="specialties-container">
                            <?php if (!empty($specialties)): ?>
                                <?php 
                                // Specialty class map from employee-directory
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
                                ?>
                                <?php foreach ($specialties as $skill): ?>
                                    <?php $skillClass = $specialtyClassMap[$skill] ?? 'spec-default'; ?>
                                    <span class="specialty-pill <?= $skillClass ?>">
                                        <?= htmlspecialchars($skill) ?>
                                    </span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="color: #888; font-style: italic;">No specialties listed</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Projects Card -->
                    <div class="projects-card">
                        <h2>
                            <i data-feather="folder"></i>
                            Current Projects
                        </h2>
                        <?php if (!empty($projects)): ?>
                            <div class="projects-list">
                                <?php foreach ($projects as $project): ?>
                                    <div class="project-item">
                                        <span class="project-name">
                                            <?= htmlspecialchars($project['project_name']) ?>
                                        </span>
                                        <span class="project-status <?= $project['status'] ?>">
                                            <?= ucfirst($project['status']) ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="no-projects">
                                <p>Not assigned to any projects</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="stats-row">
                    <!-- Active Tasks -->
                    <div class="stat-card">
                        <div class="stat-icon active">
                            <i data-feather="clipboard"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value"><?= $activeTasks ?></div>
                            <div class="stat-label">Active Tasks</div>
                        </div>
                    </div>

                    <!-- Overdue Tasks -->
                    <div class="stat-card">
                        <div class="stat-icon overdue">
                            <i data-feather="alert-circle"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value"><?= $overdueTasks ?></div>
                            <div class="stat-label">Overdue Tasks</div>
                        </div>
                    </div>

                    <!-- Project Count -->
                    <div class="stat-card">
                        <div class="stat-icon projects">
                            <i data-feather="folder"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value"><?= $projectCount ?></div>
                            <div class="stat-label">Projects</div>
                        </div>
                    </div>
                </div>

                <!-- Donut Chart - Task Breakdown -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h2 class="chart-title">
                            <i data-feather="pie-chart"></i>
                            Task Performance
                        </h2>
                        
                        <!-- Project Filter Dropdown -->
                        <?php if (!empty($projects)): ?>
                        <div class="chart-filter">
                            <select id="project-filter">
                                <option value="all">All Projects</option>
                                <?php foreach ($projects as $proj): ?>
                                    <option value="<?= (int) $proj['project_id'] ?>">
                                        <?= htmlspecialchars($proj['project_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($totalTasks > 0): ?>
                        <div class="chart-body">
                            <!-- Donut Chart -->
                            <div class="chart-wrapper">
                                <canvas id="taskDonutChart"></canvas>
                                <div class="chart-center">
                                    <div class="chart-center-value" id="chart-total"><?= $totalTasks ?></div>
                                    <div class="chart-center-label">Total Tasks</div>
                                </div>
                            </div>

                            <!-- Legend -->
                            <div class="chart-legend">
                                <div class="legend-item">
                                    <div class="legend-color todo"></div>
                                    <span class="legend-text">To Do</span>
                                    <span class="legend-value" id="legend-todo"><?= $taskStats['to_do'] ?></span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color inprogress"></div>
                                    <span class="legend-text">In Progress</span>
                                    <span class="legend-value" id="legend-inprogress"><?= $taskStats['in_progress'] ?></span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color review"></div>
                                    <span class="legend-text">In Review</span>
                                    <span class="legend-value" id="legend-review"><?= $taskStats['review'] ?></span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color completed"></div>
                                    <span class="legend-text">Completed</span>
                                    <span class="legend-value" id="legend-completed"><?= $taskStats['completed'] ?></span>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="chart-empty">
                            <i data-feather="inbox"></i>
                            <p>No tasks assigned to this employee yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <script>
        // Initialize Feather Icons
        feather.replace();

        // Add to Project Function
        function assignToProject() {
            alert('This would open a modal to add <?= $fullName ?> to a project. Feature coming soon!');
            // In a real implementation, you'd open a modal with project selection
        }

        <?php if ($totalTasks > 0): ?>
        // ================================
        // DONUT CHART INITIALIZATION
        // ================================
        const ctx = document.getElementById('taskDonutChart').getContext('2d');
        
        // Initial data from PHP
        let chartData = {
            to_do: <?= $taskStats['to_do'] ?>,
            in_progress: <?= $taskStats['in_progress'] ?>,
            review: <?= $taskStats['review'] ?>,
            completed: <?= $taskStats['completed'] ?>
        };

        // Chart colors (match your existing prototype)
        const chartColors = {
            todo: '#D93025',
            inprogress: '#E6A100',
            review: '#34A853',
            completed: '#4285F4'
        };

        // Create the donut chart
        const taskChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['To Do', 'In Progress', 'In Review', 'Completed'],
                datasets: [{
                    data: [
                        chartData.to_do,
                        chartData.in_progress,
                        chartData.review,
                        chartData.completed
                    ],
                    backgroundColor: [
                        chartColors.todo,
                        chartColors.inprogress,
                        chartColors.review,
                        chartColors.completed
                    ],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                cutout: '70%',
                plugins: {
                    legend: {
                        display: false // We use custom legend
                    },
                    tooltip: {
                        backgroundColor: '#333',
                        titleFont: {
                            family: "'Poppins', sans-serif",
                            size: 13
                        },
                        bodyFont: {
                            family: "'Poppins', sans-serif",
                            size: 12
                        },
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const value = context.raw;
                                const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                return `${context.label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

        // ================================
        // PROJECT FILTER FUNCTIONALITY
        // ================================
        const projectFilter = document.getElementById('project-filter');
        const employeeId = <?= $employeeId ?>;

        if (projectFilter) {
            projectFilter.addEventListener('change', async function() {
                const projectId = this.value;
                
                try {
                    // Fetch filtered data via AJAX
                    const response = await fetch(
                        `?id=${employeeId}&ajax=chart_data&project_id=${encodeURIComponent(projectId)}`
                    );
                    const result = await response.json();
                    
                    if (result.success) {
                        // Update chart data
                        taskChart.data.datasets[0].data = [
                            result.data.to_do,
                            result.data.in_progress,
                            result.data.review,
                            result.data.completed
                        ];
                        taskChart.update();

                        // Update center total
                        document.getElementById('chart-total').textContent = result.total;

                        // Update legend values
                        document.getElementById('legend-todo').textContent = result.data.to_do;
                        document.getElementById('legend-inprogress').textContent = result.data.in_progress;
                        document.getElementById('legend-review').textContent = result.data.review;
                        document.getElementById('legend-completed').textContent = result.data.completed;
                    }
                } catch (error) {
                    console.error('Error fetching chart data:', error);
                }
            });
        }
        <?php endif; ?>
    </script>
</body>
</html>