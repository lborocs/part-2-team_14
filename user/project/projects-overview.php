<?php
session_start();

require_once __DIR__ . '/../../config/database.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
  die("Database connection failed.");
}

// AJAX: leaders search for autocomplete
if (isset($_GET['ajax']) && $_GET['ajax'] === 'leaders') {
  header('Content-Type: application/json');

  $q = trim($_GET['q'] ?? '');
  if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
  }

  $stmt = $db->prepare("
    SELECT user_id, first_name, last_name, email
    FROM users
    WHERE is_active = 1
      AND (
        first_name LIKE :q
        OR last_name LIKE :q
        OR email LIKE :q
      )
    ORDER BY first_name ASC
    LIMIT 10
  ");
  $stmt->execute([':q' => "%$q%"]);

  $out = [];
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $full = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
    $label = $full !== '' ? "$full ({$row['email']})" : $row['email'];

    $out[] = [
      'id' => (int)$row['user_id'],
      'label' => $label
    ];
  }

  echo json_encode($out);
  exit;
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
    } elseif ($action === 'update_project') {

      $name = trim($_POST['project_name'] ?? '');
      $deadline = $_POST['deadline'] ?? null;
      $desc = trim($_POST['description'] ?? '');
      $leaderId = isset($_POST['team_leader_id']) ? (int)$_POST['team_leader_id'] : 0;

      if ($name === '') {
        echo json_encode(['success' => false, 'message' => 'Project name is required']);
        exit;
      }

      // allow blank deadline
      if ($deadline === '') $deadline = null;

      // leader required (you can relax this if you want "unassigned")
      if ($leaderId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Please select a team leader from suggestions']);
        exit;
      }

      // Update project
      $sql = "UPDATE projects
          SET project_name = :name,
              deadline = :deadline,
              description = :descr,
              team_leader_id = :leader
          WHERE project_id = :id";
      $stmt = $db->prepare($sql);
      $stmt->execute([
        ':name' => $name,
        ':deadline' => $deadline,
        ':descr' => $desc,
        ':leader' => $leaderId,
        ':id' => $projectId
      ]);

      // Return leader info for UI update
      $u = $db->prepare("SELECT first_name, last_name, profile_picture FROM users WHERE user_id = :uid LIMIT 1");
      $u->execute([':uid' => $leaderId]);
      $leader = $u->fetch(PDO::FETCH_ASSOC);

      $leaderName = trim(($leader['first_name'] ?? '') . ' ' . ($leader['last_name'] ?? ''));
      if ($leaderName === '') $leaderName = 'Unassigned';

      echo json_encode([
        'success' => true,
        'updated' => [
          'project_name' => $name,
          'deadline' => $deadline,
          'description' => $desc,
          'team_leader_id' => $leaderId,
          'leader_name' => $leaderName,
          'leader_picture' => $leader['profile_picture'] ?? ''
        ]
      ]);
      exit;
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
  p.description,
  p.team_leader_id,
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
          <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'manager'): ?>
          <li><a href="../employees/employee-directory.php"><i data-feather="users"></i>Employees</a></li>
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

        <div class="projects-grid-scroll">
          <div class="projects-grid">
            <?php if (count($activeProjects) === 0): ?>
              <!-- Empty state -->
              <div class="empty-state">
                <i data-feather="inbox"></i>
                <p>No current active projects</p>
              </div>
            <?php else: ?>
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

                $priority = strtolower($p['priority'] ?? 'medium');
                ?>

                <!-- Project cards design -->
                <article
                  class="project-card"
                  data-name="<?= strtolower(htmlspecialchars($p['project_name'])) ?>"
                  data-progress="<?= (int)$progress ?>"
                  data-deadline="<?= htmlspecialchars($p['deadline'] ?? '') ?>"
                  data-project-id="<?= htmlspecialchars($p['project_id'] ?? '') ?>"
                  data-deadline-text="<?= htmlspecialchars($dateText) ?>"
                  data-deadline-class="<?= htmlspecialchars($dateClass) ?>"
                  data-priority="<?= htmlspecialchars($priority) ?>"
                  data-description="<?= htmlspecialchars($p['description'] ?? '') ?>"
                  data-team-leader-id="<?= htmlspecialchars($p['team_leader_id'] ?? '') ?>"
                  data-team-leader-name="<?= htmlspecialchars($leaderName) ?>">

                  <!-- 3 dots for moving projects to archive -->
                  <?php if ($isManager): ?>
                    <div class="card-menu">
                      <button class="card-menu-btn" type="button" aria-label="Project actions">
                        ⋮
                      </button>

                      <div class="card-menu-dropdown" hidden>
                        <button type="button" class="card-menu-item" data-action="complete">Mark as complete</button>
                        <button type="button" class="card-menu-item" data-action="archive">Move to archives</button>
                        <button type="button" class="card-menu-item" data-action="update">Update project</button>
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
            <?php endif; ?>
          </div>
        </div>
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
                    $priority = strtolower($p['priority'] ?? 'medium'); // ✅ Get priority

                    $dateText = 'Archived';
                    $dateClass = 'days-pill';

                    // ✅ Calculate the REAL deadline text for when it's reinstated
                    $days = daysLeft($p['estimated_completion_date'] ?? null, $p['deadline'] ?? null);
                    $realDeadlineText = 'No date set';
                    $realDeadlineClass = 'days-pill';

                    if ($days !== null) {
                      if ($days < 0) {
                        $realDeadlineText = abs($days) . ' days overdue';
                        $realDeadlineClass = 'days-pill is-overdue';
                      } elseif ($days === 0) {
                        $realDeadlineText = 'Due today';
                        $realDeadlineClass = 'days-pill is-due';
                      } else {
                        $realDeadlineText = $days . ' days left';
                        $realDeadlineClass = 'days-pill';
                      }
                    }
                    ?>

                    <article
                      class="project-card project-card--archived"
                      data-name="<?= strtolower(htmlspecialchars($p['project_name'])) ?>"
                      data-progress="<?= (int)$progress ?>"
                      data-deadline="<?= htmlspecialchars($p['deadline'] ?? '') ?>"
                      data-project-id="<?= htmlspecialchars($p['project_id']) ?>"
                      data-priority="<?= htmlspecialchars($priority) ?>"
                      data-description="<?= htmlspecialchars($p['description'] ?? '') ?>"
                      data-team-leader-id="<?= htmlspecialchars($p['team_leader_id'] ?? '') ?>"
                      data-team-leader-name="<?= htmlspecialchars($leaderName) ?>"
                      data-deadline-text="<?= htmlspecialchars($realDeadlineText) ?>"
                      data-deadline-class="<?= htmlspecialchars($realDeadlineClass) ?>">

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

  <!-- UPDATE PROJECT MODAL -->
  <div class="modal-overlay" id="update-project-modal" style="display:none;">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Update Project</h2>
        <button type="button" class="close-btn" id="update-project-close-btn">
          <i data-feather="x"></i>
        </button>
      </div>

      <div class="modal-body">
        <form id="update-project-form" class="create-post-form" onsubmit="return false;">
          <input type="hidden" id="update-project-id" />

          <div class="form-group">
            <label for="update-project-name">Project Name</label>
            <input type="text" id="update-project-name" required />
          </div>

          <div class="form-group">
            <label for="update-project-deadline">Due Date</label>
            <input type="date" id="update-project-deadline" required />
          </div>

          <div class="form-group">
            <label for="update-project-description">Description</label>
            <textarea id="update-project-description" rows="4"></textarea>
          </div>

          <div class="form-group">
            <label for="update-leader-search">Team Leader</label>

            <!-- same pattern as create-project page -->
            <input type="text" id="update-leader-search" placeholder="Search team leader…" autocomplete="off" required />
            <input type="hidden" id="update-team-leader-id" />

            <div id="update-leader-results" class="autocomplete-results" style="display:none;"></div>
          </div>

          <button type="submit" class="create-post-btn">Save Changes</button>
        </form>
      </div>
    </div>
  </div>

  <script>
    feather.replace();
  </script>

</body>

</html>