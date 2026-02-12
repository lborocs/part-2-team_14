<?php
require_once __DIR__ . "/../../config/database.php";

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
//  FETCH EMPLOYEE BASIC INFO
// ============================================
$stmt = $pdo->prepare("
    SELECT user_id, first_name, last_name, email, role, profile_picture, specialties, is_registered
    FROM users
    WHERE user_id = ?
");
$stmt->execute([$employeeId]);
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$employee) {
    die('Employee not found');
}

$fullName = htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']);
$roleMap = [
    'manager' => 'Manager',
    'technical_specialist' => 'Technical Specialist',
];
$roleDisplay = $roleMap[$employee['role']] ?? 'Employee';
$isRegistered = $employee['is_registered'];

// Same 10-color banner palette as employee directory
$bannerColors = [
    '#5B9BD5', '#7FB069', '#9B59B6', '#D4926F', '#45B7B8',
    '#6C8EAD', '#2A9D8F', '#B56576', '#52796F', '#7D8FA0',
];
if (!isset($_SESSION['employee_colors'])) {
    $_SESSION['employee_colors'] = [];
}
if (isset($_SESSION['employee_colors'][$employeeId])) {
    $profileBannerColor = $_SESSION['employee_colors'][$employeeId];
} else {
    $profileBannerColor = $bannerColors[array_rand($bannerColors)];
    $_SESSION['employee_colors'][$employeeId] = $profileBannerColor;
}

// Decode specialties for display
$specialties = [];
if (!empty($employee['specialties'])) {
    $specialties = json_decode($employee['specialties'], true) 
        ?? explode(',', $employee['specialties']);
    $specialties = array_map('trim', $specialties);
}

// ============================================
//  FETCH EMPLOYEE'S PROJECTS
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
//  FETCH TASK STATISTICS (ALL PROJECTS)
// ============================================
// Active tasks count (not completed, exclude archived projects)
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count
    FROM tasks t
    INNER JOIN task_assignments ta ON t.task_id = ta.task_id
    INNER JOIN projects p ON t.project_id = p.project_id
    WHERE ta.user_id = ?
      AND t.status != 'completed'
      AND p.status != 'archived'
");
$stmt->execute([$employeeId]);
$activeTasks = (int) $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Overdue tasks count (exclude archived projects)
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count
    FROM tasks t
    INNER JOIN task_assignments ta ON t.task_id = ta.task_id
    INNER JOIN projects p ON t.project_id = p.project_id
    WHERE ta.user_id = ?
      AND t.status != 'completed'
      AND t.deadline < CURDATE()
      AND p.status != 'archived'
");
$stmt->execute([$employeeId]);
$overdueTasks = (int) $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Project count
$projectCount = count($projects);

// ============================================
// FETCH TASK BREAKDOWN FOR DONUT CHART (ALL PROJECTS)
// ============================================
$stmt = $pdo->prepare("
    SELECT
        t.status,
        COUNT(*) as count
    FROM tasks t
    INNER JOIN task_assignments ta ON t.task_id = ta.task_id
    INNER JOIN projects p ON t.project_id = p.project_id
    WHERE ta.user_id = ?
      AND p.status != 'archived'
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
//  AJAX ENDPOINT: CHART DATA BY PROJECT
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
// ============================================
// AJAX ENDPOINT: GET ACTIVE TASKS
// ============================================
if (isset($_GET['ajax']) && $_GET['ajax'] === 'active_tasks') {
    header('Content-Type: application/json');
    
    $stmt = $pdo->prepare("
        SELECT 
            t.task_id,
            t.task_name,
            t.priority,
            t.deadline,
            t.status,
            p.project_name
        FROM tasks t
        INNER JOIN task_assignments ta ON t.task_id = ta.task_id
        INNER JOIN projects p ON t.project_id = p.project_id
        WHERE ta.user_id = ?
          AND t.status != 'completed'
          AND p.status != 'archived'
        ORDER BY t.deadline ASC
    ");
    $stmt->execute([$employeeId]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'tasks' => $tasks
    ]);
    exit;
}

// ============================================
//  AJAX ENDPOINT: GET OVERDUE TASKS
// ============================================
if (isset($_GET['ajax']) && $_GET['ajax'] === 'overdue_tasks') {
    header('Content-Type: application/json');

    $stmt = $pdo->prepare("
        SELECT
            t.task_id,
            t.task_name,
            t.priority,
            t.deadline,
            t.status,
            p.project_name
        FROM tasks t
        INNER JOIN task_assignments ta ON t.task_id = ta.task_id
        INNER JOIN projects p ON t.project_id = p.project_id
        WHERE ta.user_id = ?
          AND t.status != 'completed'
          AND t.deadline < CURDATE()
          AND p.status != 'archived'
        ORDER BY t.deadline ASC
    ");
    $stmt->execute([$employeeId]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'tasks' => $tasks
    ]);
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
            padding: 24px 20px;
            border-radius: 0 0 16px 16px;
            margin: 0 0 20px 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .banner-content {
            max-width: 100%;
            margin: 0;
            display: flex;
            align-items: center !important;
            justify-content: space-between;
            gap: 40px;
        }

        .banner-left {
            display: flex;
            align-items: center;
            gap: 20px;
            padding-left: 20px;
        }

        .profile-avatar-large {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 4px solid rgba(255, 255, 255, 0.3);
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
            color: #fff;
        }

        .profile-basic-info .role {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.9);
            margin: 0 0 4px 0;
        }

        .profile-basic-info .email {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .banner-right {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 0;
            align-items: stretch;
            padding-right: 20px;
        }

        .banner-btn {
            display: inline-flex;
            align-items: center;
            justify-content: flex-start;
            gap: 8px;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            color: #fff;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
            white-space: nowrap;
            min-width: 200px;
            text-align: left;
        }

        .banner-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.5);
            color: #fff;
            transform: translateY(-1px);
        }

        .banner-btn i {
            width: 16px;
            height: 16px;
        }

        .analytics-section {
            padding: 0 40px;
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
        }

        /* Two Column Layout */
        .profile-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
            width: 100%;
            max-width: none;
        }

        .profile-left-column {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .profile-right-column {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        /* Content Grid */
        .profile-content {
            display: grid !important;
            grid-template-columns: 1fr !important;
            gap: 16px !important;
        }

        /* Specialties Card */
        .specialties-card {
            background: white;
            border-radius: 12px;
            padding: 18px;
            border: 1px solid #EAECEE;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
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
            color: #E6A100;
        }

        .specialties-container {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .projects-card {
            background: white;
            border-radius: 12px;
            padding: 18px;
            border: 1px solid #EAECEE;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
        }

        .projects-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .projects-card h2 {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .projects-count {
            font-weight: 500;
            color: #888;
            font-size: 16px;
        }

        .projects-card h2 i {
            width: 20px;
            height: 20px;
            color: #E6A100;
        }

        .projects-filter select {
            appearance: none;
            background: #f5f7fa;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 8px 32px 8px 12px;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            color: #333;
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23666' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
        }

        .projects-filter select:hover {
            border-color: #E6A100;
        }

        .projects-filter select:focus {
            outline: none;
            border-color: #E6A100;
            box-shadow: 0 0 0 3px rgba(251, 192, 45, 0.25);
        }

        .projects-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
            max-height: 200px;
            overflow-y: auto;
            padding-right: 4px;
        }

        .projects-list::-webkit-scrollbar {
            width: 4px;
        }

        .projects-list::-webkit-scrollbar-thumb {
            background: #ccc;
            border-radius: 2px;
        }

        .project-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #FBC02D;
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
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 14px;
            border: 1px solid #EAECEE;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
            display: flex;
            align-items: center;
            gap: 12px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .stat-icon svg {
            width: 20px;
            height: 20px;
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
            font-size: 22px;
            font-weight: 700;
            color: #333;
            line-height: 1.1;
        }

        .stat-label {
            font-size: 13px;
            color: #666;
            margin-top: 2px;
        }

        /* Donut Chart Card */
        .chart-card {
            background: white;
            border-radius: 12px;
            padding: 18px;
            border: 1px solid #EAECEE;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .chart-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
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
            color: #E6A100;
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
            border-color: #E6A100;
        }

        .chart-filter select:focus {
            outline: none;
            border-color: #E6A100;
            box-shadow: 0 0 0 3px rgba(251, 192, 45, 0.25);
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
            gap: 30px;
            flex: 1;
        }

        .chart-wrapper {
            position: relative;
            width: 180px;
            height: 180px;
            flex-shrink: 0;
        }

        .chart-wrapper canvas {
            position: relative;
            z-index: 2;
        }

        .chart-center {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            z-index: 1;
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
           TASK MODALS
           ================================ */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
        }

        .modal-overlay.show {
            display: flex;
        }

        .tasks-modal-content {
            background: #fff;
            border-radius: 12px;
            width: 90%;
            max-width: 700px;
            max-height: 85vh;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            animation: modalSlideIn 0.25s ease;
            display: flex;
            flex-direction: column;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 24px 28px;
            border-bottom: 1px solid #EAECEE;
            flex-shrink: 0;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 22px;
            font-weight: 600;
            color: #1E1E1E;
        }

        .modal-header .close-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 6px;
            border-radius: 50%;
            transition: background 0.15s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-header .close-btn:hover {
            background: #F0F0F0;
        }

        .modal-header .close-btn svg {
            width: 22px;
            height: 22px;
            color: #555;
        }

        .modal-body {
            padding: 20px 28px 28px;
            overflow-y: auto;
            flex: 1;
        }

        .tasks-list-container {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .task-item {
            background: #FAFAFA;
            border: 1px solid #E5E7EB;
            border-radius: 10px;
            padding: 16px;
            transition: all 0.2s ease;
        }

        .task-item-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }

        .task-item-title {
            font-size: 16px;
            font-weight: 600;
            color: #1E1E1E;
            margin: 0 0 6px 0;
            line-height: 1.4;
        }

        .task-item-project {
            font-size: 13px;
            color: #666;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .task-item-project svg {
            width: 14px;
            height: 14px;
        }

        .task-item-priority {
            display: inline-block;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 12px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            flex-shrink: 0;
        }

        .task-item-priority.low {
            background: #E6F7F0;
            color: #34A853;
        }

        .task-item-priority.medium {
            background: #FFF4C1;
            color: #E6A100;
        }

        .task-item-priority.high {
            background: #FFEBEB;
            color: #D93025;
        }

        .task-item-meta {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #E5E7EB;
            font-size: 13px;
            color: #666;
        }

        .task-meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .task-meta-item svg {
            width: 16px;
            height: 16px;
        }

        .task-status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .task-status-badge.to_do {
            background: #FFEBEB;
            color: #D93025;
        }

        .task-status-badge.in_progress {
            background: #FFF4C1;
            color: #E6A100;
        }

        .task-status-badge.review {
            background: #E7F3FF;
            color: #1A73E8;
        }

        .empty-tasks-message {
            text-align: center;
            padding: 40px 20px;
            color: #888;
        }

        .empty-tasks-message svg {
            width: 64px;
            height: 64px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .empty-tasks-message p {
            margin: 0;
            font-size: 15px;
        }

        .stat-card.clickable {
            cursor: pointer;
            user-select: none;
        }

        .stat-card.clickable:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
        }

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
                text-align: stretch;
            }
            
            .banner-left {
                flex-direction: column;
                text-align: center;
                padding-left: 0;
            }

            .banner-right {
                width: 100%;
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
                        <li<?php if ($employeeId !== (int)$_SESSION['user_id']): ?> class="active-parent"<?php endif; ?>>
                            <a href="employee-directory.php"><i data-feather="users"></i>Employees</a>
                        </li>
                    <?php endif; ?>
                    <li><a href="../knowledge-base/knowledge-base.html"><i data-feather="book-open"></i>Knowledge Base</a></li>
                </ul>
            </div>
            <div class="nav-footer">
                <ul>
                    <li id="nav-my-profile"<?php if ($employeeId === (int)$_SESSION['user_id']): ?> class="active-parent"<?php endif; ?>><a href="employee-profile.php?id=<?= $_SESSION['user_id'] ?>"><i data-feather="user"></i>My Profile</a></li>
                    <li><a href="../settings.php"><i data-feather="settings"></i>Settings</a></li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="main-content">
            <!-- Profile Banner -->
            <section class="profile-banner" style="background: <?= htmlspecialchars($profileBannerColor) ?>; color: #fff;">
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
                            <?php $isRegistered = (int)$employee['is_registered'] === 1;?>
                            <p class="<?= $isRegistered ? 'registered-pill' : 'unregistered-pill' ?>">
                                <?= $isRegistered ? 'Registered' : 'Unregistered' ?>
                            </p>
                        </div>
                    </div>
                    <?php if ($_SESSION['role'] === 'manager' && $employeeId !== (int)$_SESSION['user_id']): ?>
                    <div class="banner-right">
                        <a href="employee-directory.php" class="banner-btn">
                            <i data-feather="arrow-left"></i>
                            Back to Directory
                        </a>
                        <a href="mailto:<?= htmlspecialchars($employee['email']) ?>" class="banner-btn">
                            <i data-feather="mail"></i>
                            Send Email
                        </a>
                        <a href="#" class="banner-btn" onclick="assignToProject()">
                            <i data-feather="user-plus"></i>
                            Add to Project
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Analytics Section -->
            <section class="analytics-section">
                <!-- Two column layout -->
                <div class="profile-layout">
                    <!-- LEFT COLUMN -->
                    <div class="profile-left-column">
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
                                <div class="projects-header">
                                    <h2>
                                        <i data-feather="folder"></i>
                                        Projects <span class="projects-count">(<?= count($projects) ?>)</span>
                                    </h2>
                                    <?php if (!empty($projects)): ?>
                                    <div class="projects-filter">
                                        <select id="projects-status-filter">
                                            <option value="all">All Statuses</option>
                                            <option value="active">Active</option>
                                            <option value="archived">Archived</option>
                                        </select>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($projects)): ?>
                                    <div class="projects-list" id="projects-list">
                                        <?php foreach ($projects as $project): ?>
                                            <div class="project-item" data-status="<?= htmlspecialchars($project['status']) ?>">
                                                <span class="project-name">
                                                    <?= htmlspecialchars($project['project_name']) ?>
                                                </span>
                                                <span class="project-status <?= $project['status'] ?>">
                                                    <?= ucfirst($project['status']) ?>
                                                </span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <p class="no-projects" id="no-filtered-projects" style="display:none; font-style: normal;">No projects match this filter.</p>
                                <?php else: ?>
                                    <div class="no-projects">
                                        <p>Not assigned to any projects</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>

                    <!-- RIGHT COLUMN -->
                    <div class="profile-right-column">
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
                                            <div class="chart-center-label">Total Task(s)</div>
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

                        <!-- Statistics Cards -->
                        <div class="stats-row">
                            <div class="stat-card clickable" id="active-tasks-card" onclick="showActiveTasks()">
                                <div class="stat-icon active">
                                    <i data-feather="clipboard"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-value"><?= $activeTasks ?></div>
                                    <div class="stat-label">Active Tasks</div>
                                </div>
                            </div>
                            <div class="stat-card clickable" id="overdue-tasks-card" onclick="showOverdueTasks()">
                                <div class="stat-icon overdue">
                                    <i data-feather="alert-circle"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-value"><?= $overdueTasks ?></div>
                                    <div class="stat-label">Overdue Tasks</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script>
        // wait for DOM to be fully load
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Feather Icons
            feather.replace();

            // ================================
            // TASK MODALS FUNCTIONALITY
            // ================================
            const employeeId = <?= $employeeId ?>;
            
            // Modal elements
            const activeModal = document.getElementById('active-tasks-modal');
            const overdueModal = document.getElementById('overdue-tasks-modal');
            const closeActiveBtn = document.getElementById('close-active-modal');
            const closeOverdueBtn = document.getElementById('close-overdue-modal');
            const activeTasksList = document.getElementById('active-tasks-list');
            const overdueTasksList = document.getElementById('overdue-tasks-list');
            
            // Show Active Tasks Modal  
            window.showActiveTasks = async function() {
                try {
                    const response = await fetch(`?id=${employeeId}&ajax=active_tasks`);
                    const result = await response.json();
                    
                    if (result.success) {
                        renderTasksList(result.tasks, activeTasksList, 'active');
                        activeModal.classList.add('show');
                        feather.replace();
                    } else {
                        activeTasksList.innerHTML = '<div class="empty-tasks-message"><p>Error loading tasks</p></div>';
                        activeModal.classList.add('show');
                    }
                } catch (error) {
                    console.error('Error fetching active tasks:', error);
                    activeTasksList.innerHTML = '<div class="empty-tasks-message"><p>Error loading tasks</p></div>';
                    activeModal.classList.add('show');
                }
            }
            
            // Show Overdue Tasks Modal
            window.showOverdueTasks = async function() {
                try {
                    const response = await fetch(`?id=${employeeId}&ajax=overdue_tasks`);
                    const result = await response.json();
                    
                    if (result.success) {
                        renderTasksList(result.tasks, overdueTasksList, 'overdue');
                        overdueModal.classList.add('show');
                        feather.replace();
                    } else {
                        overdueTasksList.innerHTML = '<div class="empty-tasks-message"><p>Error loading tasks</p></div>';
                        overdueModal.classList.add('show');
                    }
                } catch (error) {
                    console.error('Error fetching overdue tasks:', error);
                    overdueTasksList.innerHTML = '<div class="empty-tasks-message"><p>Error loading tasks</p></div>';
                    overdueModal.classList.add('show');
                }
            }
            
            // Render tasks list
            function renderTasksList(tasks, container, type) {
                if (!tasks || tasks.length === 0) {
                    container.innerHTML = `
                        <div class="empty-tasks-message">
                            <i data-feather="inbox"></i>
                            <p>No ${type} tasks found</p>
                        </div>
                    `;
                    return;
                }
                
                const tasksHTML = tasks.map(task => {
                    const deadline = new Date(task.deadline);
                    const formattedDeadline = deadline.toLocaleDateString('en-GB', {
                        day: 'numeric',
                        month: 'short',
                        year: 'numeric'
                    });
                    
                    const statusLabels = {
                        'to_do': 'To Do',
                        'in_progress': 'In Progress',
                        'review': 'In Review',
                        'completed': 'Completed'
                    };
                    
                    return `
                        <div class="task-item">
                            <div class="task-item-header">
                                <div>
                                    <h3 class="task-item-title">${escapeHtml(task.task_name)}</h3>
                                    <div class="task-item-project">
                                        <i data-feather="folder"></i>
                                        ${escapeHtml(task.project_name)}
                                    </div>
                                </div>
                                <span class="task-item-priority ${task.priority}">
                                    ${task.priority}
                                </span>
                            </div>
                            <div class="task-item-meta">
                                <div class="task-meta-item">
                                    <i data-feather="calendar"></i>
                                    <span>Due: ${formattedDeadline}</span>
                                </div>
                                <div class="task-meta-item">
                                    <span class="task-status-badge ${task.status}">
                                        ${statusLabels[task.status] || task.status}
                                    </span>
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');
                
                container.innerHTML = tasksHTML;
            }
            
            // Escape HTML to prevent XSS
            function escapeHtml(text) {
                const map = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                };
                return text.replace(/[&<>"']/g, m => map[m]);
            }
            
            // Close modal handlers
            closeActiveBtn.addEventListener('click', () => {
                activeModal.classList.remove('show');
            });
            
            closeOverdueBtn.addEventListener('click', () => {
                overdueModal.classList.remove('show');
            });
            
            // Close on background click
            activeModal.addEventListener('click', (e) => {
                if (e.target === activeModal) {
                    activeModal.classList.remove('show');
                }
            });
            
            overdueModal.addEventListener('click', (e) => {
                if (e.target === overdueModal) {
                    overdueModal.classList.remove('show');
                }
            });
            
            // Close on Escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    activeModal.classList.remove('show');
                    overdueModal.classList.remove('show');
                }
            });

            // Projects status filter
            const projectsFilter = document.getElementById('projects-status-filter');
            if (projectsFilter) {
                projectsFilter.addEventListener('change', function() {
                    const status = this.value;
                    const items = document.querySelectorAll('#projects-list .project-item');
                    const noMsg = document.getElementById('no-filtered-projects');
                    let visibleCount = 0;

                    items.forEach(item => {
                        if (status === 'all' || item.dataset.status === status) {
                            item.style.display = '';
                            visibleCount++;
                        } else {
                            item.style.display = 'none';
                        }
                    });

                    if (noMsg) {
                        noMsg.style.display = visibleCount === 0 ? '' : 'none';
                    }
                });
            }

            // Add to Project Function
            window.assignToProject = function() {
                sessionStorage.setItem('preselectedEmployees', JSON.stringify([<?= $employeeId ?>]));
                window.location.href = 'add-to-project.php';
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
                            display: false 
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
        });
    </script>

    <!-- Active Tasks Modal -->
    <div class="modal-overlay" id="active-tasks-modal">
        <div class="modal-content tasks-modal-content">
            <div class="modal-header">
                <h2>Active Tasks</h2>
                <button type="button" class="close-btn" id="close-active-modal">
                    <i data-feather="x"></i>
                </button>
            </div>
            <div class="modal-body">
                <div id="active-tasks-list" class="tasks-list-container"></div>
            </div>
        </div>
    </div>

    <!-- Overdue Tasks Modal -->
    <div class="modal-overlay" id="overdue-tasks-modal">
        <div class="modal-content tasks-modal-content">
            <div class="modal-header">
                <h2>Overdue Tasks</h2>
                <button type="button" class="close-btn" id="close-overdue-modal">
                    <i data-feather="x"></i>
                </button>
            </div>
            <div class="modal-body">
                <div id="overdue-tasks-list" class="tasks-list-container"></div>
            </div>
        </div>
    </div>
</body>
</html>