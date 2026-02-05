<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../actions/guard_project_access.php';

$database = new Database();
$db = $database->getConnection();
if (!$db) die("Database connection failed.");

$userId = $_SESSION['user_id'] ?? null;
$role   = $_SESSION['role'] ?? null;

$isLocal = in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'], true);
if (!$userId && $isLocal) {
    $userId = 1;
    $role   = 'manager';
}

if (!$userId) {
    http_response_code(401);
    exit("Not logged in.");
}

$projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
if ($projectId <= 0) {
    http_response_code(400);
    exit("Missing/invalid project_id in the URL.");
}

// 1) Must be on the project (member OR leader OR creator OR manager)
$access = guardProjectAccess($db, $projectId, (int)$userId);

$project = $access['project'];
$canManageProject = $access['canManageProject'];
$canCloseProject  = $access['canCloseProject'];

// =============================
// AJAX: MEMBER PROGRESS (SECURE)
// =============================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['ajax'] ?? '') === 'member_progress') {
    header('Content-Type: application/json; charset=utf-8');

    // must be manager/team_leader for this project
    if (!$canManageProject) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No permission']);
        exit;
    }

    try {
        // Only people who have tasks assigned in THIS project
        $stmt = $db->prepare("
            SELECT
                u.user_id,
                u.email,
                u.first_name,
                u.last_name,
                u.profile_picture,
                COUNT(DISTINCT ta.task_id) AS total_tasks,
                SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) AS completed_tasks
            FROM task_assignments ta
            JOIN tasks t
              ON t.task_id = ta.task_id
             AND t.project_id = :pid
            JOIN users u
              ON u.user_id = ta.user_id
            WHERE u.is_active = 1
            GROUP BY u.user_id, u.email, u.first_name, u.last_name, u.profile_picture
            ORDER BY u.first_name ASC, u.last_name ASC
        ");
        $stmt->execute([':pid' => $projectId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $people = array_map(function ($r) {
            $total = (int)$r['total_tasks'];
            $done  = (int)$r['completed_tasks'];
            $pct   = $total > 0 ? (int)round(($done / $total) * 100) : 0;

            $name = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
            if ($name === '') $name = strtolower(trim($r['email'] ?? ''));

            return [
                'user_id' => (int)$r['user_id'],
                'name' => $name,
                'email' => strtolower(trim($r['email'] ?? '')),
                'avatarUrl' => !empty($r['profile_picture']) ? $r['profile_picture'] : null,
                'total_tasks' => $total,
                'completed_tasks' => $done,
                'percent' => $pct
            ];
        }, $rows);

        echo json_encode(['success' => true, 'people' => $people]);
        exit;
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error']);
        exit;
    }
}

// =============================
// AJAX: DEADLINES LIST (SECURE)
// =============================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['ajax'] ?? '') === 'deadlines') {
    header('Content-Type: application/json; charset=utf-8');

    if (!$canManageProject) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No permission']);
        exit;
    }

    try {
        // Get upcoming/overdue tasks for THIS project (exclude completed)
        // Include status and priority fields
        $stmt = $db->prepare("
            SELECT
                t.task_id,
                t.task_name,
                t.deadline,
                t.updated_at,
                t.status,
                t.priority,
                t.description,

                u.user_id AS owner_id,
                u.first_name,
                u.last_name,
                u.profile_picture

            FROM tasks t
            LEFT JOIN task_assignments ta
              ON ta.task_id = t.task_id
            LEFT JOIN users u
              ON u.user_id = ta.user_id

            WHERE t.project_id = :pid
              AND t.status != 'completed'
              AND t.deadline IS NOT NULL

            GROUP BY t.task_id
            ORDER BY t.deadline ASC
            LIMIT 30
        ");
        $stmt->execute([':pid' => $projectId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $items = array_map(function ($r) {
            $ownerName = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
            if ($ownerName === '') $ownerName = 'Unassigned';

            return [
                'task_id' => (int)$r['task_id'],
                'task_name' => $r['task_name'],
                'deadline' => $r['deadline'],
                'updated_at' => $r['updated_at'],
                'status' => $r['status'] ?? 'in_progress',
                'priority' => $r['priority'] ?? 'medium',
                'description' => $r['description'] ?? '',
                'owner' => [
                    'user_id' => $r['owner_id'] ? (int)$r['owner_id'] : null,
                    'name' => $ownerName,
                    'avatar' => !empty($r['profile_picture']) ? $r['profile_picture'] : null,
                ]
            ];
        }, $rows);

        echo json_encode(['success' => true, 'items' => $items]);
        exit;
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error']);
        exit;
    }
}

// =============================
// CLOSE PROJECT (AJAX) - manager-progress.php
// (same behaviour as projects.php)
// =============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['ajax'] ?? '') === 'close_project') {
    header('Content-Type: application/json; charset=utf-8');

    // Only manager can close
    if (!$canCloseProject) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No permission']);
        exit;
    }

    // Archive project
    $upd = $db->prepare("
        UPDATE projects
        SET status = 'archived'
        WHERE project_id = :pid
        LIMIT 1
    ");
    $upd->execute([':pid' => $projectId]);

    if ($upd->rowCount() < 1) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Project not found']);
        exit;
    }

    echo json_encode(['success' => true]);
    exit;
}


//  2) Must be manager/team leader for THIS project to view manager-progress.php
if (!$canManageProject) {
    http_response_code(403);
    exit("You don't have access to the manager progress view.");
}

// Fetch active users for avatars + names (same as projects.php)
$stmt = $db->prepare("
  SELECT user_id, email, first_name, last_name, role, profile_picture
  FROM users
  WHERE is_active = 1
");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$roleMap = [
    'team_member' => 'member',
    'technical_specialist' => 'specialist',
    'team_leader' => 'team_leader',
    'manager' => 'manager',
];

$simUsers = [];
foreach ($users as $u) {
    $email = strtolower(trim($u['email']));
    $fullName = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
    $avatarClass = 'avatar-' . ((int)$u['user_id'] % 4 + 1);
    $dbRole = $u['role'] ?? 'team_member';
    $jsRole = $roleMap[$dbRole] ?? 'member';

    $simUsers[$email] = [
        'name' => $fullName !== '' ? $fullName : $email,
        'role' => $jsRole,
        'avatarClass' => $avatarClass,
        'avatarUrl' => !empty($u['profile_picture']) ? $u['profile_picture'] : null,
    ];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make-It-All - Manager Progress</title>

    <link rel="stylesheet" href="../dashboard.css">
    <link rel="stylesheet" href="progress.css">
    <link rel="stylesheet" href="manager-progress.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>

    <script>
        window.__USERS__ = <?= json_encode($simUsers, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        window.__PROJECT__ = <?= json_encode($project) ?>;
        window.__ROLE__ = <?= json_encode($role) ?>;
        window.__CAN_MANAGE_PROJECT__ = <?= json_encode($canManageProject) ?>;
        window.__CAN_CLOSE_PROJECT__ = <?= json_encode($canCloseProject) ?>;
    </script>

</head>

<body id="manager-progress-page">
    <div class="dashboard-container">
        <nav class="sidebar">
            <div class="nav-top">
                <div class="logo-container">
                    <img src="../logo.png" alt="Make-It-All Logo" class="logo-icon">
                </div>
                <ul class="nav-main">
                    <li><a href="../home/home.html"><i data-feather="home"></i>Home</a></li>
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
            <header class="project-header">
                <div class="project-header-top">
                    <div class="breadcrumbs-title">
                        <p class="breadcrumbs">Projects > <span id="project-name-breadcrumb"><?= htmlspecialchars($project['project_name'] ?? 'Project') ?></span></p>
                        <h1 id="project-name-header"><?= htmlspecialchars($project['project_name'] ?? 'Project') ?></h1>
                    </div>
                    <?php if ($canCloseProject): ?>
                        <button class="close-project-btn" id="close-project-btn">Close Project</button>
                    <?php endif; ?>


                </div>

                <!-- ✅ Let JS fill correct links (uses __ROLE__ + __CAN_MANAGE_PROJECT__) -->
                <nav class="project-nav" id="project-nav-links"></nav>
            </header>

            <div class="manager-progress-layout">
                <div class="manager-progress-left">
                    <section class="member-progress-card">
                        <div class="member-progress-header">
                            <h2>Team Progress</h2>

                            <div class="member-progress-search-wrap">
                                <i data-feather="search"></i>
                                <input
                                    type="text"
                                    id="member-progress-search"
                                    class="member-progress-search"
                                    placeholder="Search members..."
                                    autocomplete="off" />
                            </div>
                        </div>


                        <div class="member-progress-list" id="member-progress-list">
                            <!-- JS renders rows -->
                        </div>

                        <p class="member-progress-hint" id="member-progress-hint" style="display:none;">
                            No matches found.
                        </p>
                    </section>

                </div>

                <div class="manager-progress-right">
                    <section class="deadlines-card">
                        <h2>Upcoming Deadlines</h2>

                      <div class="deadlines-scroll">
  <div class="deadlines-columns">
    <div>Task</div>
    <div>Due</div>
    <div>Activity</div>
  </div>

  <div class="deadlines-list" id="deadlines-list"></div>
</div>

                    </section>


                </div>
            </div>
        </main>
    </div>
    <!-- Close Project Confirm Modal (REQUIRED for app.js close button) -->
    <div class="modal-overlay" id="close-project-modal" style="display:none;">
        <div class="modal-content" style="max-width:520px;">
            <div class="modal-header">
                <h2>Close Project</h2>
                <button type="button" class="close-btn" id="close-project-x">
                    <i data-feather="x"></i>
                </button>
            </div>

            <div class="modal-body">
                <p style="margin:0 0 8px;">Are you sure you want to close this project?</p>
                <p style="margin:0 0 16px; color:#666;">
                    This project will be moved to archives.
                </p>

                <div style="display:flex; gap:10px; justify-content:flex-end;">
                    <button type="button" class="create-post-btn" id="close-project-cancel"
                        style="background:#eee; color:#111;">
                        Cancel
                    </button>

                    <button type="button" class="create-post-btn" id="close-project-ok">
                        OK
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="../app.js"></script>
    <script>
        feather.replace();
    </script>
<script>
(function () {
  const list = document.getElementById('deadlines-list');
  if (!list) return;

  const projectId = new URLSearchParams(location.search).get('project_id');
  
  // Store full task data when we load deadlines
  let taskDataMap = {};

  function parseDate(s){
    return new Date((s || '').replace(' ', 'T'));
  }

  function dueLabel(deadlineStr){
    const d = parseDate(deadlineStr);
    if (isNaN(d)) return '-';

    const now = new Date();
    const diffMs = d - now;
    const abs = Math.abs(diffMs);

    const mins = Math.round(abs / 60000);
    const hours = Math.round(abs / 3600000);
    const days = Math.round(abs / 86400000);

    if (abs < 3600000) return diffMs < 0 ? `${mins}m overdue` : `in ${mins}m`;
    if (abs < 86400000) return diffMs < 0 ? `${hours}h overdue` : `in ${hours}h`;
    return diffMs < 0 ? `${days}d overdue` : `in ${days}d`;
  }

  function activityLabel(updatedStr){
    const d = parseDate(updatedStr);
    if (isNaN(d)) return '-';

    const now = new Date();
    const diff = now - d;
    const mins = Math.round(diff / 60000);
    const hours = Math.round(diff / 3600000);
    const days = Math.round(diff / 86400000);

    if (mins < 60) return `${mins}m ago`;
    if (hours < 24) return `${hours}h ago`;
    return `${days}d ago`;
  }

  function renderRow(item){
    const row = document.createElement('div');
    row.className = 'deadline-row';
    row.style.cursor = 'pointer';
    row.dataset.taskId = item.task_id;

    const due = dueLabel(item.deadline);
    const activity = activityLabel(item.updated_at);

    row.innerHTML = `
      <div class="col-task">${item.task_name || '-'}</div>
      <div class="col-due right">${due}</div>
      <div class="col-activity right">${activity}</div>
    `;
    
    // Store full task data
    taskDataMap[item.task_id] = item;
    
    // Add click handler directly to the row
    row.addEventListener('click', () => {
      openTaskModal(item.task_id);
    });
    
    return row;
  }

  async function loadDeadlines(){
    list.innerHTML = '';
    taskDataMap = {};

    const url = `manager-progress.php?project_id=${encodeURIComponent(projectId)}&ajax=deadlines`;
    const res = await fetch(url, { credentials: 'same-origin' });
    const data = await res.json();

    if (!data.success) {
      list.innerHTML = `<div style="padding:10px;color:#777;">Unable to load deadlines</div>`;
      return;
    }

    (data.items || []).forEach(item => list.appendChild(renderRow(item)));
  }
  
  function openTaskModal(taskId) {
    const task = taskDataMap[taskId];
    if (!task) {
      console.error('Task not found:', taskId);
      return;
    }
    
    // Create a modal matching progress.css styles
    let modal = document.getElementById('deadline-task-modal');
    
    if (!modal) {
      modal = document.createElement('div');
      modal.id = 'deadline-task-modal';
      modal.className = 'modal-overlay';
      
      modal.innerHTML = `
        <div class="task-detail-modal">
          <div class="task-detail-header">
            <h3 id="modal-task-name"></h3>
            <div class="task-detail-actions">
              <button class="task-menu-btn" id="task-menu-btn">
                <i data-feather="more-vertical"></i>
              </button>
              <button class="modal-close-btn" id="modal-close-btn">
                <i data-feather="x"></i>
              </button>
            </div>
          </div>

          <!-- Dropdown menu -->
          <div class="task-actions-menu" id="task-actions-menu" style="display:none;">
            <div class="menu-section">
              <div class="menu-section-title">Status</div>
              <div id="status-options"></div>
            </div>
            <div class="menu-section">
              <div class="menu-section-title">Priority</div>
              <div id="priority-options"></div>
            </div>
            <div class="menu-section">
              <button class="menu-item menu-item-danger" id="delete-task-btn">
                <i data-feather="trash-2"></i>
                Delete Task
              </button>
            </div>
          </div>

          <div class="task-detail-body">
            <div class="task-detail-row">
              <div class="task-detail-label">Assigned To</div>
              <div class="task-detail-value" id="modal-task-owner"></div>
            </div>

            <div class="task-detail-row">
              <div class="task-detail-label">Status</div>
              <div class="task-detail-value">
                <span class="status-badge" id="modal-task-status"></span>
              </div>
            </div>

            <div class="task-detail-row">
              <div class="task-detail-label">Priority</div>
              <div class="task-detail-value">
                <span class="priority-badge" id="modal-task-priority"></span>
              </div>
            </div>

            <div class="task-detail-row">
              <div class="task-detail-label">Deadline</div>
              <div class="task-detail-value" id="modal-task-deadline"></div>
            </div>

            <div class="task-detail-row task-detail-row-notes">
  <div class="task-detail-label">Manager Notes</div>
  <div class="task-detail-value" id="modal-task-notes"></div>
</div>


            <div class="task-detail-row">
              <div class="task-detail-label">Last Updated</div>
              <div class="task-detail-value" id="modal-task-updated"></div>
            </div>
          </div>
        </div>
      `;
      
      document.body.appendChild(modal);
      
      // Close handlers
      const closeBtn = modal.querySelector('#modal-close-btn');
      const menuBtn = modal.querySelector('#task-menu-btn');
      const menu = modal.querySelector('#task-actions-menu');
      
      closeBtn.addEventListener('click', () => {
        modal.style.display = 'none';
        document.body.style.overflow = '';
        menu.style.display = 'none';
      });
      
      modal.addEventListener('click', (e) => {
        if (e.target === modal) {
          modal.style.display = 'none';
          document.body.style.overflow = '';
          menu.style.display = 'none';
        }
      });
      
      // Menu toggle
      menuBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
      });
      
      // Close menu when clicking outside
      document.addEventListener('click', (e) => {
        if (!menu.contains(e.target) && !menuBtn.contains(e.target)) {
          menu.style.display = 'none';
        }
      });
    }
    
    // Populate modal with task data
    const statusMap = {
      'to_do': { label: 'To Do', class: 'status-todo' },
      'in_progress': { label: 'In Progress', class: 'status-progress' },
      'review': { label: 'Review', class: 'status-review' },
      'completed': { label: 'Completed', class: 'status-completed' }
    };
    
    const priorityMap = {
      'low': { label: 'Low', class: 'priority-low' },
      'medium': { label: 'Medium', class: 'priority-medium' },
      'high': { label: 'High', class: 'priority-high' }
    };
    
    // Normalize status and priority
    const currentStatus = (task.status || 'in_progress').toLowerCase();
    const currentPriority = (task.priority || 'medium').toLowerCase();
    
    // Set task name
    document.getElementById('modal-task-name').textContent = task.task_name || 'Untitled Task';
    
    // Set owner
    document.getElementById('modal-task-owner').textContent = task.owner?.name || 'Unassigned';
    
    // Set status badge
    const statusInfo = statusMap[currentStatus] || statusMap['in_progress'];
    const statusEl = document.getElementById('modal-task-status');
    statusEl.textContent = statusInfo.label;
    statusEl.className = 'status-badge ' + statusInfo.class;
    
    // Set priority badge
    const priorityInfo = priorityMap[currentPriority] || priorityMap['medium'];
    const priorityEl = document.getElementById('modal-task-priority');
    priorityEl.textContent = priorityInfo.label;
    priorityEl.className = 'priority-badge ' + priorityInfo.class;
    
    // Set deadline
    const deadline = parseDate(task.deadline);
    document.getElementById('modal-task-deadline').textContent = !isNaN(deadline) 
      ? deadline.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })
      : 'No deadline';
    
    // Set last updated
    const updated = parseDate(task.updated_at);
    document.getElementById('modal-task-updated').textContent = !isNaN(updated)
      ? updated.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) + ' at ' + updated.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' })
      : 'Unknown';

      const notesEl = document.getElementById('modal-task-notes');
notesEl.textContent = (task.description && task.description.trim())
  ? task.description.trim()
  : '—';

    
    // Populate status options (exclude current status)
    const statusContainer = document.getElementById('status-options');
    statusContainer.innerHTML = '';
    
    Object.entries(statusMap).forEach(([key, info]) => {
      if (key === currentStatus) return;
      
      const btn = document.createElement('button');
      btn.className = 'menu-item';
      btn.innerHTML = `<i data-feather="arrow-right"></i> ${info.label}`;
      btn.onclick = () => updateTaskStatus(task.task_id, key);
      statusContainer.appendChild(btn);
    });
    
    // Populate priority options (exclude current priority)
    const priorityContainer = document.getElementById('priority-options');
    priorityContainer.innerHTML = '';
    
    Object.entries(priorityMap).forEach(([key, info]) => {
      if (key === currentPriority) return;
      
      const btn = document.createElement('button');
      btn.className = 'menu-item';
      btn.innerHTML = `<i data-feather="flag"></i> ${info.label}`;
      btn.onclick = () => updateTaskPriority(task.task_id, key);
      priorityContainer.appendChild(btn);
    });
    
    // Delete button
    const deleteBtn = document.getElementById('delete-task-btn');
    const newDeleteBtn = deleteBtn.cloneNode(true);
    deleteBtn.parentNode.replaceChild(newDeleteBtn, deleteBtn);
    newDeleteBtn.onclick = () => deleteTask(task.task_id);
    
    // Show modal
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    if (window.feather) feather.replace();
  }

  async function updateTaskStatus(taskId, newStatus) {
    try {
      const res = await fetch(`projects.php?project_id=${encodeURIComponent(projectId)}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          ajax: 'update_task_status',
          task_id: taskId,
          new_status: newStatus
        })
      });
      
      const data = await res.json();
      
      if (!data.success) {
        alert(data.message || 'Failed to update status');
        return;
      }
      
      showNotification('Task status updated!');
      
      document.getElementById('deadline-task-modal').style.display = 'none';
      document.body.style.overflow = '';
      document.getElementById('task-actions-menu').style.display = 'none';
      
      await loadDeadlines();
      
      if (typeof initManagerMemberProgressList === 'function') {
        initManagerMemberProgressList();
      }
      
    } catch (err) {
      console.error('Status update error:', err);
      alert('Failed to update status. Please try again.');
    }
  }

  async function updateTaskPriority(taskId, newPriority) {
    try {
      const res = await fetch(`projects.php?project_id=${encodeURIComponent(projectId)}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          ajax: 'update_task_priority',
          task_id: taskId,
          priority: newPriority
        })
      });
      
      const data = await res.json();
      
      if (!data.success) {
        alert(data.message || 'Failed to update priority');
        return;
      }
      
      showNotification('Task priority updated!');
      
      document.getElementById('deadline-task-modal').style.display = 'none';
      document.body.style.overflow = '';
      document.getElementById('task-actions-menu').style.display = 'none';
      
      await loadDeadlines();
      
    } catch (err) {
      console.error('Priority update error:', err);
      alert('Failed to update priority. Please try again.');
    }
  }

  async function deleteTask(taskId) {
    if (!confirm('Are you sure you want to delete this task? This cannot be undone.')) return;
    
    try {
      const res = await fetch(`projects.php?project_id=${encodeURIComponent(projectId)}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          ajax: 'delete_task',
          task_id: taskId
        })
      });
      
      const data = await res.json();
      
      if (!data.success) {
        alert(data.message || 'Failed to delete task');
        return;
      }
      
      showNotification('Task deleted successfully!');
      
      document.getElementById('deadline-task-modal').style.display = 'none';
      document.body.style.overflow = '';
      
      await loadDeadlines();
      
      if (typeof initManagerMemberProgressList === 'function') {
        initManagerMemberProgressList();
      }
      
    } catch (err) {
      console.error('Delete error:', err);
      alert('Failed to delete task. Please try again.');
    }
  }

  function showNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'success-notification';
    notification.innerHTML = `
      <i data-feather="check-circle"></i>
      <span>${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    if (window.feather) feather.replace();
    
    setTimeout(() => {
      notification.classList.add('show');
    }, 10);
    
    setTimeout(() => {
      notification.classList.remove('show');
      setTimeout(() => notification.remove(), 300);
    }, 3000);
  }

  loadDeadlines();
})();
</script>
</body>

</html>