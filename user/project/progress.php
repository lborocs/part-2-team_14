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
    $role   = 'team_member';
}

if (!$userId) {
    http_response_code(401);
    exit("Not logged in.");
}

$projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
if ($projectId <= 0) exit("Missing/invalid project_id in the URL.");

// SINGLE SOURCE OF TRUTH (access + roles for THIS project)
$access = guardProjectAccess($db, $projectId, (int)$userId);

$project = $access['project'];
$canManageProject = $access['canManageProject'];


// If they can manage, send them to manager-progress.php (no HTML loop anymore)
if ($canManageProject) {
    header("Location: manager-progress.php?project_id=" . urlencode($projectId));
    exit;
}

// Users for name lookup (assignees)
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
    <title>Make-It-All - Progress</title>

    <link rel="stylesheet" href="../dashboard.css">
    <link rel="stylesheet" href="progress.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="/favicon.png">
    <script src="https://unpkg.com/feather-icons"></script>

    <script>
        window.__USERS__ = <?= json_encode($simUsers, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        window.__PROJECT__ = <?= json_encode($project) ?>;
        window.__ROLE__ = <?= json_encode($role) ?>;
        window.__CAN_MANAGE_PROJECT__ = <?= json_encode($canManageProject) ?>;
    </script>
</head>

<body id="progress-page">
    <div class="dashboard-container">
        <nav class="sidebar">
            <div class="nav-top">
                <div class="logo-container">
                    <img src="../logo.png" alt="Make-It-All Logo" class="logo-icon">
                </div>
                <ul class="nav-main">
                    <?php if ($role === 'manager' || $role === 'team_leader'): ?>
                        <li><a href="../home/home.php"><i data-feather="home"></i>Home</a></li>
                    <?php endif; ?>
                    <li class="active-parent"><a href="projects-overview.php"><i data-feather="folder"></i>Projects</a></li>
                    <?php if ($role === 'manager'): ?>
                        <li><a href="../employees/employee-directory.php"><i data-feather="users"></i>Employees</a></li>
                    <?php endif; ?>
                    <li><a href="../knowledge-base/knowledge-base.html"><i data-feather="book-open"></i>Knowledge Base</a></li>
                </ul>
            </div>
            <div class="nav-footer">
                <ul>
                    <li><a href="../settings.php"><i data-feather="settings"></i>Settings</a></li>
                </ul>
            </div>
        </nav>

        <main class="main-content">
            <header class="project-header">
                <div class="project-header-top">
                    <div class="breadcrumbs-title">
                        <p class="breadcrumbs">
                            <a href="projects-overview.php">Projects</a> >
                            <span id="project-name-breadcrumb">
                                <?= htmlspecialchars($project['project_name'] ?? 'Project') ?>
                            </span>
                        </p>

                        <h1 id="project-name-header">
                            <?= htmlspecialchars($project['project_name'] ?? 'Project') ?>
                        </h1>

                    </div>
                </div>

                <nav class="project-nav" id="project-nav-links"></nav>
            </header>

         <div class="progress-layout">
            <!-- Deadline Countdown Widget -->
        <section class="countdown-card">
            <div class="countdown-header">
                <span class="countdown-pulse"></span>
            </div>
            <div id="countdown-content" class="countdown-content">
                <!-- Countdown will be rendered here -->
            </div>
        </section>
    <!-- Left Column -->
    <div class="progress-left">
        <!-- Task Progress Card -->
        <section class="progress-card">
            <h2>Task Progress</h2>
            <div class="progress-bar-container">
                <div class="progress-bar">
                    <div class="progress-fill" id="task-progress-fill" style="width: 0%">
                        <span class="progress-icon">🏆</span>
                    </div>
                </div>
            </div>
            <p class="progress-text" id="progress-text">
                You have completed 0% of your assigned tasks.
            </p>
        </section>

        
    </div>

    <!-- Right Column -->
    <div class="progress-right">
        <!-- Upcoming Deadlines Card -->
        <section class="deadlines-card">
            <h2>Upcoming Deadlines</h2>
            <div class="deadlines-list-header">
    <div class="deadlines-list-header-left">Task</div>
    <div class="deadlines-list-header-right">
        <span>Due</span>
        <span>Status</span>
    </div>
</div>
<div class="deadlines-list" id="deadlines-list">
    <!-- Deadlines will be rendered here -->
</div>
        </section>
    </div>
</div>

      </main>
    </div>

    <!-- Task Details Modal -->
    <div class="modal-overlay" id="task-details-modal">
        <div class="task-detail-modal">
            <div class="task-detail-header">
    <h3 id="modal-task-name">Task Title</h3>
    <button class="modal-close-btn" id="modal-close-btn">
        <i data-feather="x"></i>
    </button>
</div>

            <div class="task-detail-body">
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
                    <div class="task-detail-label">Date Assigned</div>
                    <div class="task-detail-value" id="modal-task-created"></div>
                </div>
            </div>

            <div class="task-detail-actions" style="padding: 20px 28px; border-top: 1px solid #f0f0f0;">
                <button id="mark-complete-btn" class="project-complete-btn" style="margin: 0;">
                    Mark Complete
                </button>
            </div>
        </div>
    </div>

    <script src="../app.js"></script>
    <script>
        feather.replace();
    </script>
    
    <script>
    window.initProgressWidgets = function() {
    const projectId = new URLSearchParams(location.search).get('project_id');
    if (!projectId) return;

    let taskDataMap = {};
    let countdownInterval = null;

// Countdown Timer Function — PROJECT deadline
function startCountdown() {
    const contentDiv = document.getElementById('countdown-content');
    if (!contentDiv) return;

    const project = window.__PROJECT__ || {};
    const rawDeadline = project.deadline; // projects.deadline (DATE)

    // No project deadline (should not happen as DB is NOT NULL, but safe guard)
    if (!rawDeadline) {
        contentDiv.innerHTML = `
            <div class="countdown-empty">
                <div class="countdown-empty-icon">📅</div>
                <p class="countdown-empty-text">No project deadline set</p>
                <p class="countdown-empty-subtext">Ask your manager to add one.</p>
            </div>
        `;
        return;
    }

    // Force end-of-day to avoid DATE timezone issues
    const deadlineDate = new Date(`${rawDeadline}T23:59:59`);

    // Clear any existing interval
    if (countdownInterval) clearInterval(countdownInterval);

    function updateCountdown() {
        const now = new Date();
        const timeLeft = deadlineDate - now;

        // Deadline passed
        if (timeLeft <= 0) {
            clearInterval(countdownInterval);
            contentDiv.innerHTML = `
                <div class="countdown-expired">
                    <div class="countdown-expired-icon">⚠️</div>
                    <p class="countdown-expired-text">Project deadline passed!</p>
                </div>
            `;
            return;
        }

        const days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
        const hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);

        contentDiv.innerHTML = `
            <div class="countdown-task-name">
                ${project.project_name || 'Project'}
            </div>

            <div class="countdown-timer">
                <div class="countdown-unit">
                    <div class="countdown-value">${String(days).padStart(2, '0')}</div>
                    <div class="countdown-label">Days</div>
                </div>
                <div class="countdown-separator">:</div>
                <div class="countdown-unit">
                    <div class="countdown-value">${String(hours).padStart(2, '0')}</div>
                    <div class="countdown-label">Hours</div>
                </div>
                <div class="countdown-separator">:</div>
                <div class="countdown-unit">
                    <div class="countdown-value">${String(minutes).padStart(2, '0')}</div>
                    <div class="countdown-label">Minutes</div>
                </div>
                <div class="countdown-separator">:</div>
                <div class="countdown-unit">
                    <div class="countdown-value">${String(seconds).padStart(2, '0')}</div>
                    <div class="countdown-label">Seconds</div>
                </div>
            </div>

            <div class="countdown-deadline-date">
                Due: ${deadlineDate.toLocaleDateString('en-GB', {
                    weekday: 'long',
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric'
                })}
            </div>
        `;
    }

    // Initial render
    updateCountdown();

    // Update every second
    countdownInterval = setInterval(updateCountdown, 1000);
}



    // Fetch and render deadlines
    async function loadDeadlines() {
        try {
            const tasks = window.__TASKS_NORM__ || [];
            const currentUserEmail = (window.__CURRENT_USER__?.email || '').toLowerCase();

            // Filter tasks assigned to current user
            const userTasks = tasks.filter(t => {
                if (!Array.isArray(t.assignedTo)) return false;
                return t.assignedTo.some(email => 
                    String(email).toLowerCase() === currentUserEmail
                );
            });

            // Filter incomplete tasks with deadlines
            const upcomingTasks = userTasks
                .filter(t => t.status !== 'completed' && t.deadline)
                .sort((a, b) => new Date(a.deadline) - new Date(b.deadline))
                .slice(0, 10);

            const list = document.getElementById('deadlines-list');
            if (!list) return;

            if (upcomingTasks.length === 0) {
                list.innerHTML = '<p class="no-deadlines" style="text-align: center; color: #999; padding: 20px;">No upcoming deadlines. You\'re all caught up!</p>';
                return;
            }

            list.innerHTML = '';
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            upcomingTasks.forEach(task => {
                taskDataMap[task.id] = task;

                const deadline = new Date(task.deadline);
                const formattedDate = deadline.toLocaleDateString('en-US', { 
                    weekday: 'short', 
                    day: 'numeric', 
                    month: 'short' 
                });

                let status = 'on-track';
                let statusText = 'On track';

                if (deadline < today) {
                    status = 'overdue';
                    statusText = 'Overdue';
                } else {
                    const daysUntil = Math.ceil((deadline - today) / (1000 * 60 * 60 * 24));
                    if (daysUntil <= 2) {
                        status = 'at-risk';
                        statusText = 'At risk';
                    }
                }

                const item = document.createElement('div');
                item.className = 'deadline-item';
                item.dataset.taskId = task.id;
                item.innerHTML = `
                    <p class="deadline-title">${task.title}</p>
                    <div class="deadline-info">
                        <span class="deadline-date">${formattedDate}</span>
                        <span class="deadline-status ${status}">${statusText}</span>
                    </div>
                `;

                item.addEventListener('click', () => openTaskModal(task.id));
                list.appendChild(item);
            });

        } catch (err) {
            console.error('Load deadlines error:', err);
        }
    }

    function openTaskModal(taskId) {
        const task = taskDataMap[taskId];
        if (!task) return;

        const modal = document.getElementById('task-details-modal');
        if (!modal) return;

        const statusMap = {
            'todo': { label: 'To Do', class: 'status-todo' },
            'inprogress': { label: 'In Progress', class: 'status-progress' },
            'review': { label: 'Review', class: 'status-review' },
            'completed': { label: 'Completed', class: 'status-completed' }
        };

        const priorityMap = {
            'low': { label: 'Low', class: 'priority-low' },
            'medium': { label: 'Medium', class: 'priority-medium' },
            'high': { label: 'High', class: 'priority-high' }
        };

        // Set task name
        document.getElementById('modal-task-name').textContent = task.title || 'Untitled Task';

        // Set status
        const statusInfo = statusMap[task.status] || statusMap['inprogress'];
        const statusEl = document.getElementById('modal-task-status');
        statusEl.textContent = statusInfo.label;
        statusEl.className = 'status-badge ' + statusInfo.class;

        // Set priority
        const priorityInfo = priorityMap[task.priority] || priorityMap['medium'];
        const priorityEl = document.getElementById('modal-task-priority');
        priorityEl.textContent = priorityInfo.label;
        priorityEl.className = 'priority-badge ' + priorityInfo.class;

        // Set deadline
        const deadline = new Date(task.deadline);
        document.getElementById('modal-task-deadline').textContent = !isNaN(deadline)
            ? deadline.toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' })
            : 'No deadline';

        // Set notes
        const notesEl = document.getElementById('modal-task-notes');
        notesEl.textContent = (task.description && task.description.trim()) 
            ? task.description.trim() 
            : '—';

        // Set created date - tasks are normalized from DB with proper fields
const created = task.createdDate ? new Date(task.createdDate) : null;
document.getElementById('modal-task-created').textContent = (created && !isNaN(created))
    ? created.toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' })
    : '—';

        // Show/hide mark complete button
        const markBtn = document.getElementById('mark-complete-btn');
        const canMarkComplete = task.status === 'todo' || task.status === 'inprogress';
        if (markBtn) markBtn.style.display = canMarkComplete ? 'inline-flex' : 'none';

        // Show modal
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        if (window.feather) feather.replace();
    }

    // Close modal
    const closeBtn = document.getElementById('modal-close-btn');
    const modal = document.getElementById('task-details-modal');

    if (closeBtn && modal) {
        closeBtn.addEventListener('click', () => {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        });

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.style.display = 'none';
                document.body.style.overflow = '';
            }
        });
    }

    // Mark complete button
    const markBtn = document.getElementById('mark-complete-btn');
    if (markBtn) {
        markBtn.addEventListener('click', async () => {
            const taskName = document.getElementById('modal-task-name').textContent;
            const taskId = Object.keys(taskDataMap).find(id => taskDataMap[id].title === taskName);
            
            if (!taskId) return;

            const ok = confirm('Mark this task as complete? It will be moved to Review.');
            if (!ok) return;

            try {
                const res = await fetch(`projects.php?project_id=${encodeURIComponent(projectId)}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        ajax: 'update_task_status',
                        task_id: taskId,
                        new_status: 'review'
                    })
                });

                const data = await res.json();

                if (!data.success) {
                    alert(data.message || 'Failed to update task');
                    return;
                }

                showSuccessNotification('Task sent to Review!');
                modal.style.display = 'none';
                document.body.style.overflow = '';

                // Reload page to refresh data
                setTimeout(() => location.reload(), 1000);

            } catch (err) {
                console.error('Update error:', err);
                alert('Failed to update task. Please try again.');
            }
        });
    }

    if (typeof startCountdown === 'function') {
        startCountdown();
    }
    if (typeof loadDeadlines === 'function') {
        loadDeadlines();
    }

    // Cleanup on page unload
    window.addEventListener('beforeunload', () => {
        if (countdownInterval) {
            clearInterval(countdownInterval);
        }
    });
};
</script>
</body>

</html>