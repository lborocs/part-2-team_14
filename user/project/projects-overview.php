<?php
session_start();

require_once __DIR__ . '/../../config/database.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
  die("Database connection failed.");
}

//DEV BYPASS
$isLoggedIn = isset($_SESSION['role'], $_SESSION['email'], $_SESSION['user_id']);

if (!$isLoggedIn) {
  // you're viewing without login -> force safe defaults
  $role = 'manager';
  $isManager = true;

  $currentUserId = 1; // TEMP fallback
} else {
  $role = $_SESSION['role'];
  $isManager = ($role === 'manager');
  $currentUserId = $_SESSION['user_id'];
}


// actual code should have this bit but keeping upper one for access since i view page w/o login

// if (!isset($_SESSION['role'], $_SESSION['email'])) {
//   header('Location: ../index.html');
//   exit;
// }

// $role = $_SESSION['role'];     // no default
// $isManager = ($role === 'manager');


// =============================
// ACTION HANDLER (same file)
// =============================
if ($_SERVER['REQUEST_METHOD'] === 'POST'  && isset($_POST['action'])) {
  header('Content-Type: application/json');

  // Only managers can do these actions
  if (!$isManager) {
    echo json_encode(['success' => false, 'message' => 'Not allowed']);
    exit;
  }

  // Get data sent from JS
  $projectId = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
  $action = $_POST['action'] ?? '';

  if ($projectId <= 0 || $action === '') {
    echo json_encode(['success' => false, 'message' => 'Missing project_id or action']);
    exit;
  }

  try {
    if ($action === 'archive') {
      // Move to archived (keep progress as-is)
      $sql = "UPDATE projects
              SET status = 'archived', archived_at = NOW()
              WHERE project_id = :id";
      $stmt = $db->prepare($sql);
      $stmt->execute([':id' => $projectId]);
    } elseif ($action === 'complete') {
      // Mark complete AND archive (and set progress to 100)
      $sql = "UPDATE projects
              SET status = 'archived',
                  completed_date = COALESCE(completed_date, CURDATE()),
                  completion_percentage = 100.00,
                  archived_at = NOW()
              WHERE project_id = :id";
      $stmt = $db->prepare($sql);
      $stmt->execute([':id' => $projectId]);
    } elseif ($action === 'reinstate') {
      // Bring back from archive to active (keep whatever progress it had)
      $sql = "UPDATE projects
              SET status = 'active', archived_at = NULL
              WHERE project_id = :id";
      $stmt = $db->prepare($sql);
      $stmt->execute([':id' => $projectId]);
    } else {
      echo json_encode(['success' => false, 'message' => 'Unknown action']);
      exit;
    }

    echo json_encode(['success' => true]);
    exit;
  } catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
  }
}


//checks days left until deadline
function daysLeft(?string $estimated, ?string $deadline): ?int
{
  $target = $estimated ?: $deadline;
  if (!$target) return null;

  $today = new DateTime('today');
  $targetDate = new DateTime($target);
  $diff = $today->diff($targetDate);

  // signed integer days: negative = overdue
  return (int)$diff->format('%r%a');
}
//gets active projects with sql statement
$activeSql = "
  SELECT DISTINCT
    p.project_id,
    p.project_name,
    p.status,
    p.priority,
    p.deadline,
    p.estimated_completion_date,
    p.completion_percentage,
    u.first_name AS leader_first_name,
    u.last_name  AS leader_last_name,
    u.profile_picture AS leader_picture
  FROM projects p
  LEFT JOIN users u ON p.team_leader_id = u.user_id
  LEFT JOIN project_members pm
    ON pm.project_id = p.project_id
  WHERE p.status IN ('active','planning','on_hold','completed')
    AND p.status <> 'archived'
    AND (
      p.created_by = :uid
      OR p.team_leader_id = :uid
      OR pm.user_id = :uid
    )
  ORDER BY p.deadline ASC
";
$activeStmt = $db->prepare($activeSql);
$activeStmt->execute([':uid' => $currentUserId]);
$activeProjects = $activeStmt->fetchAll(PDO::FETCH_ASSOC);



$archivedProjects = [];
if ($isManager) {
  $archivedSql = "
  SELECT DISTINCT
    p.project_id,
    p.project_name,
    p.status,
    p.priority,
    p.deadline,
    p.estimated_completion_date,
    p.completion_percentage,
    u.first_name AS leader_first_name,
    u.last_name  AS leader_last_name,
    u.profile_picture AS leader_picture
  FROM projects p
  LEFT JOIN users u ON p.team_leader_id = u.user_id
  LEFT JOIN project_members pm
    ON pm.project_id = p.project_id
  WHERE p.status = 'archived'
    AND (
      p.created_by = :uid
      OR p.team_leader_id = :uid
      OR pm.user_id = :uid
    )
  ORDER BY p.deadline DESC
";
  $archivedStmt = $db->prepare($archivedSql);
  $archivedStmt->execute([':uid' => $currentUserId]);
  $archivedProjects = $archivedStmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Make-It-All - Projects Overview</title>

  <link rel="stylesheet" href="../dashboard.css">
  <link rel="stylesheet" href="projects-overview.css">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/feather-icons"></script>
  <script src="../app.js" defer></script>


</head>

<body id="projects-overview-page">
  <?php include '../to-do/todo_widget.php'; ?>
  <div class="dashboard-container">
    <nav class="sidebar">
      <div class="nav-top">
        <div class="logo-container">
          <img src="../logo.png" alt="Make-It-All Logo" class="logo-icon">
        </div>
        <ul class="nav-main">
          <li><a href="../home/home.php"><i data-feather="home"></i>Home</a></li>
          <li class="active-parent"><a href="projects-overview.php"><i data-feather="folder"></i>Projects</a></li>
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
      <header class="projects-header">
        <h1>Projects</h1>
        <div class="projects-controls">

          <!-- If it's a manager they should be able to see this button-->
          <?php if ($isManager): ?>
            <div>

              <a href="../home/create-project.php" class="create-new-project-button">
                <i data-feather="plus"></i>
                Create New Project
              </a>
            </div>
          <?php endif; ?>

          <div class="search-bar">
            <input type="text" id="project-search" placeholder="Search projects..." autocomplete="off">
            <i data-feather="search" class="search-icon"></i>
          </div>

        </div>

      </header>

      <!-- ACTIVE -->
      <section class="projects-section" id="active-section">
        <div class="section-top">
          <h2 class="active-projects-title">Active Projects</h2>

          <!-- Sorting dropdown -->
          <div class="sort-wrap">
            <span class="sort-label">Sort by:</span>
            <select class="sort-dropdown" id="sortProjects">
              <option value="due">Due date</option>
              <option value="progress">Progress %</option>
              <option value="name">Name (A-Z)</option>
              <option value="priorityHigh">Priority (High → Low)</option>
              <option value="priorityLow">Priority (Low → High)</option>

            </select>
          </div>
        </div>

        <?php if (count($activeProjects) === 0): ?>
          <div class="empty-state">
            <i data-feather="inbox"></i>
            <p>No current active projects</p>
          </div>
        <?php else: ?>
          <div class="projects-grid-scroll">
            <div class="projects-grid">
              <!-- Project cards display -->
              <?php foreach ($activeProjects as $p): ?>
                <?php
                // todo
                $progress = (float)($p['completion_percentage'] ?? 0);
                //just in case
                if ($progress < 0) $progress = 0;
                if ($progress > 100) $progress = 100;

                $days = daysLeft($p['estimated_completion_date'] ?? null, $p['deadline'] ?? null);

                $leaderName = trim(($p['leader_first_name'] ?? '') . ' ' . ($p['leader_last_name'] ?? ''));
                if ($leaderName === '') $leaderName = 'Unassigned';

                $avatar = $p['leader_picture'] ?? '';

                // status text
                $status = $p['status'] ?? '';
                if ($status === 'completed') {
                  $dateText = 'Completed';
                  $dateClass = 'days-pill is-completed';
                } elseif ($days === null) {
                  $dateText = 'No date set';
                  $dateClass = 'days-pill';
                } elseif ($days < 0) {
                  $dateText = abs($days) . ' days overdue';
                  $dateClass = 'days-pill is-overdue';
                } elseif ($days === 0) {
                  $dateText = 'Due today';
                  $dateClass = 'days-pill is-due';
                } else {
                  $dateText = $days . ' days left';
                  $dateClass = 'days-pill';
                }
                ?>

                <!-- Project cards design -->

                <?php $priority = strtolower($p['priority'] ?? 'medium'); ?>
                <article
                  class="project-card"
                  data-name="<?= strtolower(htmlspecialchars($p['project_name'])) ?>"
                  data-progress="<?= (int)$progress ?>"
                  data-deadline="<?= htmlspecialchars($p['deadline'] ?? '') ?>"
                  data-project-id="<?= htmlspecialchars($p['project_id'] ?? '') ?>"
                  data-deadline-text="<?= htmlspecialchars($dateText) ?>"
                  data-deadline-class="<?= htmlspecialchars($dateClass) ?>"
                  data-priority="<?= htmlspecialchars($priority) ?>">

                  <!-- 3 dots for moving projects to archive -->
                  <?php if ($isManager): ?>
                    <div class="card-menu">
                      <button class="card-menu-btn" type="button" aria-label="Project actions">
                        ⋮
                      </button>

                      <div class="card-menu-dropdown" hidden>
                        <button type="button" class="card-menu-item" data-action="complete">Mark as complete</button>
                        <button type="button" class="card-menu-item" data-action="archive">Move to archives</button>
                      </div>
                    </div>
                  <?php endif; ?>


                  <h3 class="project-title"><?= htmlspecialchars($p['project_name']) ?></h3>

                  <!-- Priority label and progress pill -->

                  <div class="progress-head">
                    <span class="small-label">PROGRESS</span>

                    <span class="priority-pill priority-<?= htmlspecialchars($priority) ?>">
                      <?= ucfirst(htmlspecialchars($priority)) ?> priority
                    </span>
                  </div>



                  <div class="progress-track" aria-hidden="true">
                    <div class="progress-fill" style="width: <?= (int)round($progress) ?>%;"></div>
                  </div>
                  <div class="progress-text"><?= (int)round($progress) ?>% complete</div>

                  <div class="small-label leader-label">TEAM LEADER</div>
                  <div class="leader-row">
                    <?php if (!empty($avatar)): ?>
                      <img class="leader-avatar" src="<?= htmlspecialchars($avatar) ?>" alt="Leader pfp">
                    <?php else: ?>
                      <div class="leader-avatar leader-avatar--default" aria-hidden="true">
                        <i data-feather="user"></i>
                      </div>
                    <?php endif; ?>

                    <span class="leader-name"><?= htmlspecialchars($leaderName) ?></span>
                  </div>

                  <div class="<?= $dateClass ?>">
                    <i data-feather="clock"></i>
                    <span><?= htmlspecialchars($dateText) ?></span>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
      </section>

      <!-- ARCHIVED (MANAGER ONLY) -->
      <?php if ($isManager): ?>
        <section class="projects-section" id="archived-section">
          <div class="section-top">
            <div class="section-title-with-toggle">
              <h2>Archived Projects</h2>
              <button type="button" id="jumpToArchived" class="archive-jump-btn">
                <i data-feather="chevron-down"></i>
              </button>
            </div>
          </div>

          <div id="archived-content" class="archived-content is-hidden">

            <div class="projects-grid-scroll">
              <div class="projects-grid">

                <?php if (count($archivedProjects) === 0): ?>

                  <!-- Empty state -->
                  <div class="empty-state">
                    <i data-feather="archive"></i>
                    <p>No archived projects</p>
                  </div>

                <?php else: ?>

                  <?php foreach ($archivedProjects as $p): ?>
                    <?php
                    $progress = (float)($p['completion_percentage'] ?? 0);
                    if ($progress < 0) $progress = 0;
                    if ($progress > 100) $progress = 100;

                    $leaderName = trim(($p['leader_first_name'] ?? '') . ' ' . ($p['leader_last_name'] ?? ''));
                    if ($leaderName === '') $leaderName = 'Unassigned';

                    $avatar = $p['leader_picture'] ?? '';

                    $dateText = 'Archived';
                    $dateClass = 'days-pill';
                    ?>

                    <article
                      class="project-card project-card--archived"
                      data-name="<?= strtolower(htmlspecialchars($p['project_name'])) ?>"
                      data-progress="<?= (int)$progress ?>"
                      data-deadline="<?= htmlspecialchars($p['deadline'] ?? '') ?>"
                      data-project-id="<?= htmlspecialchars($p['project_id']) ?>"
                      data-pill-text="<?= htmlspecialchars($dateText) ?>"
                      data-pill-class="<?= htmlspecialchars($dateClass) ?>"
                      data-deadline-text="<?= htmlspecialchars($dateText) ?>"
                      data-deadline-class="<?= htmlspecialchars($dateClass) ?>"
                      data-priority="<?= htmlspecialchars($priority) ?>">

                      <!-- 3 dots menu -->
                      <div class="card-menu">
                        <button class="card-menu-btn" type="button" aria-label="Project actions">
                          ⋮
                        </button>

                        <div class="card-menu-dropdown" hidden>
                          <button type="button"
                            class="card-menu-item"
                            data-action="reinstate">
                            Reinstate
                          </button>
                        </div>
                      </div>

                      <h3 class="project-title"><?= htmlspecialchars($p['project_name']) ?></h3>

                      <div class="small-label">PROGRESS</div>
                      <div class="progress-track" aria-hidden="true">
                        <div class="progress-fill" style="width: <?= (int)round($progress) ?>%;"></div>
                      </div>
                      <div class="progress-text"><?= (int)round($progress) ?>% complete</div>

                      <div class="small-label leader-label">TEAM LEADER</div>
                      <div class="leader-row">
                        <?php if (!empty($avatar)): ?>
                          <img class="leader-avatar" src="<?= htmlspecialchars($avatar) ?>" alt="Leader pfp">
                        <?php else: ?>
                          <div class="leader-avatar leader-avatar--default" aria-hidden="true">
                            <i data-feather="user"></i>
                          </div>
                        <?php endif; ?>

                        <span class="leader-name"><?= htmlspecialchars($leaderName) ?></span>
                      </div>

                      <div class="<?= $dateClass ?>">
                        <i data-feather="clock"></i>
                        <span><?= htmlspecialchars($dateText) ?></span>
                      </div>

                    </article>

                  <?php endforeach; ?>

                <?php endif; ?>

              </div>
            </div>
          </div>
        </section>
      <?php endif; ?>


    </main>
  </div>
  <script>
    feather.replace();
  </script>

</body>

</html>