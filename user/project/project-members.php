<?php
session_start();
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../actions/guard_project_access.php";

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.html');
    exit();
}

if (isset($_GET['project_id'])) {
    $_SESSION['current_project_id'] = (int) $_GET['project_id'];
}

$projectId = $_SESSION['current_project_id'] ?? null;
$userId = (int) $_SESSION['user_id'];
$role = $_SESSION['role'] ?? '';

$database = new Database();
$pdo = $database->getConnection();

if (!$pdo || !$projectId) {
    die("Database connection failed or no project selected.");
}

// Verify access
$access = guardProjectAccess($pdo, (int) $projectId, $userId);
$project = $access['project'];
$isManager = $access['isManager'];
$canManageProject = $access['canManageProject'];

// Banner colors (same as employee directory)
$bannerColors = [
    '#5B9BD5', '#7FB069', '#9B59B6', '#D4926F', '#45B7B8',
    '#6C8EAD', '#2A9D8F', '#B56576', '#52796F', '#7D8FA0',
];
if (!isset($_SESSION['employee_colors'])) {
    $_SESSION['employee_colors'] = [];
}

// Specialty class map
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

// =============================
// AJAX: Preview Remove Member (GET) - shows task impact
// =============================
if (isset($_GET['action']) && $_GET['action'] === 'preview_remove') {
    header('Content-Type: application/json');

    if ($role !== 'manager') {
        echo json_encode(['success' => false, 'message' => 'Only managers can remove members.']);
        exit;
    }

    $removeUserId = (int) ($_GET['user_id'] ?? 0);
    if ($removeUserId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID.']);
        exit;
    }

    try {
        // Find tasks this user is assigned to in this project
        $stmt = $pdo->prepare("
            SELECT t.task_id, t.task_name,
                   (SELECT COUNT(*) FROM task_assignments ta2 WHERE ta2.task_id = t.task_id) AS assignee_count
            FROM task_assignments ta
            JOIN tasks t ON t.task_id = ta.task_id
            WHERE ta.user_id = :uid AND t.project_id = :pid
        ");
        $stmt->execute([':uid' => $removeUserId, ':pid' => $projectId]);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $soloTasks = [];
        $sharedTasks = [];
        foreach ($tasks as $task) {
            if ((int)$task['assignee_count'] === 1) {
                $soloTasks[] = $task['task_name'];
            } else {
                $sharedTasks[] = $task['task_name'];
            }
        }

        echo json_encode([
            'success' => true,
            'solo_tasks' => $soloTasks,
            'shared_tasks' => $sharedTasks,
            'total_tasks' => count($tasks)
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
    exit;
}

// =============================
// AJAX: Remove Member (POST)
// =============================
if (isset($_POST['action']) && $_POST['action'] === 'remove_member') {
    header('Content-Type: application/json');

    if ($role !== 'manager') {
        echo json_encode(['success' => false, 'message' => 'Only managers can remove members.']);
        exit;
    }

    $removeUserId = (int) ($_POST['user_id'] ?? 0);
    if ($removeUserId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID.']);
        exit;
    }

    // Prevent removing the team leader or manager
    if ($removeUserId === (int) $project['team_leader_id']) {
        echo json_encode(['success' => false, 'message' => 'Cannot remove the team leader from their project.']);
        exit;
    }
    if ($removeUserId === (int) $project['created_by']) {
        echo json_encode(['success' => false, 'message' => 'Cannot remove the project manager.']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Remove their task assignments for this project
        $stmt = $pdo->prepare("
            DELETE ta FROM task_assignments ta
            JOIN tasks t ON t.task_id = ta.task_id
            WHERE ta.user_id = :uid AND t.project_id = :pid
        ");
        $stmt->execute([':uid' => $removeUserId, ':pid' => $projectId]);
        $tasksRemoved = $stmt->rowCount();

        // Soft-delete from project_members
        $stmt = $pdo->prepare("
            UPDATE project_members
            SET left_at = NOW()
            WHERE project_id = :pid AND user_id = :uid AND left_at IS NULL
        ");
        $stmt->execute([':pid' => $projectId, ':uid' => $removeUserId]);

        if ($stmt->rowCount() > 0) {
            $pdo->commit();
            echo json_encode([
                'success' => true,
                'message' => 'Member removed successfully.',
                'tasks_unassigned' => $tasksRemoved
            ]);
        } else {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Member not found or already removed.']);
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// =============================
// AJAX: List Members (GET)
// =============================
if (isset($_GET['action']) && $_GET['action'] === 'list_members') {
    header('Content-Type: application/json');

    try {
        // Fetch active project members
        $stmt = $pdo->prepare("
            SELECT
                u.user_id, u.first_name, u.last_name, u.email,
                u.profile_picture, u.specialties, u.role AS system_role,
                pm.project_role
            FROM project_members pm
            INNER JOIN users u ON u.user_id = pm.user_id
            WHERE pm.project_id = :pid AND pm.left_at IS NULL
            ORDER BY
                CASE pm.project_role
                    WHEN 'team_leader' THEN 1
                    ELSE 2
                END,
                u.first_name ASC
        ");
        $stmt->execute([':pid' => $projectId]);
        $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Also fetch the project manager (created_by) if not already in members
        $managerId = (int) $project['created_by'];
        $managerInList = false;
        foreach ($members as $m) {
            if ((int) $m['user_id'] === $managerId) {
                $managerInList = true;
                break;
            }
        }

        if (!$managerInList) {
            $stmt = $pdo->prepare("
                SELECT user_id, first_name, last_name, email, profile_picture, specialties, role AS system_role
                FROM users WHERE user_id = :uid
            ");
            $stmt->execute([':uid' => $managerId]);
            $manager = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($manager) {
                $manager['project_role'] = 'manager';
                array_unshift($members, $manager);
            }
        }

        // Mark the manager in the list
        foreach ($members as &$m) {
            if ((int) $m['user_id'] === $managerId && $m['project_role'] !== 'manager') {
                $m['is_manager'] = true;
            }
        }
        unset($m);

        echo json_encode(['success' => true, 'members' => $members]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// =============================
// Fetch members for initial render
// =============================
$stmt = $pdo->prepare("
    SELECT
        u.user_id, u.first_name, u.last_name, u.email,
        u.profile_picture, u.specialties, u.role AS system_role,
        pm.project_role
    FROM project_members pm
    INNER JOIN users u ON u.user_id = pm.user_id
    WHERE pm.project_id = :pid AND pm.left_at IS NULL
    ORDER BY
        CASE pm.project_role
            WHEN 'team_leader' THEN 1
            ELSE 2
        END,
        u.first_name ASC
");
$stmt->execute([':pid' => $projectId]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include manager if not already a project_member
$managerId = (int) $project['created_by'];
$teamLeaderId = (int) $project['team_leader_id'];
$managerInList = false;

foreach ($members as $m) {
    if ((int) $m['user_id'] === $managerId) {
        $managerInList = true;
        break;
    }
}

if (!$managerInList) {
    $stmt = $pdo->prepare("
        SELECT user_id, first_name, last_name, email, profile_picture, specialties, role AS system_role
        FROM users WHERE user_id = :uid
    ");
    $stmt->execute([':uid' => $managerId]);
    $manager = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($manager) {
        $manager['project_role'] = 'manager';
        array_unshift($members, $manager);
    }
}

$memberCount = count($members);

// Helper to get consistent banner color
function getMemberColor($uid, $bannerColors, &$colorMap) {
    if (isset($colorMap[$uid])) {
        return $colorMap[$uid];
    }
    $color = $bannerColors[array_rand($bannerColors)];
    $colorMap[$uid] = $color;
    return $color;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make-It-All - Project Members</title>
    <link rel="stylesheet" href="../dashboard.css">
    <link rel="stylesheet" href="progress.css">
    <link rel="stylesheet" href="project-members.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="/favicon.png">
    <script src="https://unpkg.com/feather-icons"></script>
</head>
<body id="project-members-page">
<?php include '../to-do/todo_widget.php'; ?>
<div class="dashboard-container">
    <nav class="sidebar">
        <div class="nav-top">
            <div class="logo-container">
                <img src="../logo.png" alt="Make-It-All Logo" class="logo-icon">
            </div>
            <ul class="nav-main">
                <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'manager' || $_SESSION['role'] === 'team_leader')): ?>
                    <li><a href="../home/home.php"><i data-feather="home"></i>Home</a></li>
                <?php endif; ?>
                <li class="active-parent">
                    <a href="projects-overview.php"><i data-feather="folder"></i>Projects</a>
                    <ul class="nav-sub" id="project-sidebar-list"></ul>
                </li>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'manager'): ?>
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
                    <p class="breadcrumbs"><a href="projects-overview.php">Projects</a> > <span id="project-name-breadcrumb"><?= htmlspecialchars($project['project_name']) ?></span></p>
                    <h1 id="project-name-header"><?= htmlspecialchars($project['project_name']) ?></h1>
                </div>
                <button class="close-project-btn" id="close-project-btn" style="display:none;"><i data-feather="archive"></i> Close Project</button>
            </div>
            <nav class="project-nav" id="project-nav-links">
                <a href="#">Tasks</a>
                <a href="#">Progress</a>
                <a href="#">Resources</a>
                <a href="#" class="active">Members</a>
            </nav>
        </header>

        <div class="members-content">
            <div class="members-header">
                <h2>
                    Project Members
                    <span class="members-count-badge" id="members-count"><?= $memberCount ?></span>
                </h2>
                <?php if ($isManager): ?>
                    <a href="../employees/employee-directory.php" class="add-member-btn">
                        <i data-feather="user-plus"></i>
                        Add Member
                    </a>
                <?php endif; ?>
            </div>

            <div class="members-grid" id="members-grid">
                <?php foreach ($members as $member): ?>
                    <?php
                        $uid = (int) $member['user_id'];
                        $fullName = htmlspecialchars($member['first_name'] . ' ' . $member['last_name']);
                        $email = htmlspecialchars($member['email']);
                        $profilePic = htmlspecialchars($member['profile_picture']);
                        $bannerColor = getMemberColor($uid, $bannerColors, $_SESSION['employee_colors']);

                        // Determine display role
                        $displayRole = 'Member';
                        $roleClass = 'role-member';
                        if ($uid === $managerId) {
                            $displayRole = 'Manager';
                            $roleClass = 'role-manager';
                        } elseif ($uid === $teamLeaderId || $member['project_role'] === 'team_leader') {
                            $displayRole = 'Team Leader';
                            $roleClass = 'role-team-leader';
                        }

                        // Parse specialties
                        $specialties = [];
                        if (!empty($member['specialties'])) {
                            $specialties = json_decode($member['specialties'], true)
                                ?? explode(',', $member['specialties']);
                            $specialties = array_map('trim', $specialties);
                        }

                        // Can this member be removed?
                        $canRemove = $isManager && $uid !== $managerId && $uid !== $teamLeaderId;

                        // Managers can click cards (except their own) to go to profile
                        $isClickable = $isManager && $uid !== $userId;
                        $cardClasses = 'member-card' . ($isClickable ? ' clickable' : '');
                    ?>
                    <div class="<?= $cardClasses ?>" data-user-id="<?= $uid ?>"<?php if ($isClickable): ?> data-profile-url="../employees/employee-profile.php?id=<?= $uid ?>"<?php endif; ?>>
                        <div class="member-card-top" style="background-color: <?= htmlspecialchars($bannerColor) ?>;">
                            <span class="member-role-badge <?= $roleClass ?>"><?= $displayRole ?></span>
                            <div class="member-avatar">
                                <img src="<?= $profilePic ?>" alt="<?= $fullName ?>">
                            </div>
                        </div>
                        <div class="member-card-body">
                            <h4 class="member-name"><?= $fullName ?></h4>
                            <p class="member-email">
                                <i data-feather="mail"></i>
                                <span><?= $email ?></span>
                            </p>
                            <?php if (!empty($specialties)): ?>
                                <div class="member-specialties">
                                    <?php foreach ($specialties as $skill): ?>
                                        <?php $skillClass = $specialtyClassMap[trim($skill)] ?? 'spec-default'; ?>
                                        <span class="specialty-pill <?= $skillClass ?>">
                                            <?= htmlspecialchars(trim($skill)) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($canRemove): ?>
                                <button type="button" class="remove-member-btn" data-user-id="<?= $uid ?>" data-name="<?= $fullName ?>">
                                    <i data-feather="user-minus"></i>
                                    Remove
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($memberCount === 0): ?>
                <div class="members-empty">
                    <i data-feather="users"></i>
                    <p>No members assigned to this project yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Remove Confirmation Modal -->
<div class="remove-modal-overlay" id="remove-modal">
    <div class="remove-modal">
        <div class="remove-modal-header">
            <h3>Remove Member</h3>
            <button type="button" class="remove-modal-close" id="remove-modal-close">
                <i data-feather="x"></i>
            </button>
        </div>
        <div class="remove-modal-body" id="remove-modal-body">
            Are you sure you want to remove <strong id="remove-member-name"></strong> from this project?
        </div>
        <div class="remove-modal-footer">
            <button type="button" class="remove-modal-cancel" id="remove-modal-cancel">Cancel</button>
            <button type="button" class="remove-modal-confirm" id="remove-modal-confirm">
                Remove
            </button>
        </div>
    </div>
</div>

<script>
    window.__PROJECT__ = <?= json_encode($project) ?>;
    window.__ROLE__ = <?= json_encode($role) ?>;
    window.__CAN_MANAGE_PROJECT__ = <?= json_encode($canManageProject) ?>;
</script>
<script src="../app.js"></script>
<script>
(function() {
    let removeUserId = null;

    document.addEventListener('DOMContentLoaded', () => {
        feather.replace();

        const modal = document.getElementById('remove-modal');
        const modalName = document.getElementById('remove-member-name');
        const modalClose = document.getElementById('remove-modal-close');
        const modalCancel = document.getElementById('remove-modal-cancel');
        const modalConfirm = document.getElementById('remove-modal-confirm');

        // Clickable cards navigate to employee profile
        document.querySelectorAll('.member-card.clickable').forEach(card => {
            card.addEventListener('click', () => {
                const url = card.dataset.profileUrl;
                if (url) window.location.href = url;
            });
        });

        // Attach remove button listeners
        const modalBody = document.getElementById('remove-modal-body');
        document.querySelectorAll('.remove-member-btn').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.stopPropagation();
                removeUserId = btn.dataset.userId;
                const memberName = btn.dataset.name;
                modalName.textContent = memberName;

                // Fetch task impact preview
                let warningHtml = '';
                try {
                    const res = await fetch(`project-members.php?action=preview_remove&user_id=${removeUserId}`);
                    const preview = await res.json();
                    if (preview.success && preview.total_tasks > 0) {
                        warningHtml = '<div style="margin-top:12px; padding:12px; background:#FFF8E1; border-radius:8px; font-size:13px; color:#7A6100;">';
                        if (preview.solo_tasks.length > 0) {
                            warningHtml += `<p style="margin:0 0 6px;"><strong>Solo tasks (will become unassigned):</strong></p><ul style="margin:0 0 8px; padding-left:18px;">`;
                            preview.solo_tasks.forEach(t => { warningHtml += `<li>${t}</li>`; });
                            warningHtml += '</ul>';
                        }
                        if (preview.shared_tasks.length > 0) {
                            warningHtml += `<p style="margin:0 0 6px;"><strong>Shared tasks (will remain with other assignees):</strong></p><ul style="margin:0; padding-left:18px;">`;
                            preview.shared_tasks.forEach(t => { warningHtml += `<li>${t}</li>`; });
                            warningHtml += '</ul>';
                        }
                        warningHtml += '</div>';
                    }
                } catch (err) {
                    console.error('Preview error:', err);
                }

                modalBody.innerHTML = `Are you sure you want to remove <strong>${memberName}</strong> from this project?${warningHtml}`;
                modal.classList.add('show');
            });
        });

        // Close modal
        function closeModal() {
            modal.classList.remove('show');
            removeUserId = null;
        }

        modalClose.addEventListener('click', closeModal);
        modalCancel.addEventListener('click', closeModal);
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeModal();
        });

        // Confirm removal
        modalConfirm.addEventListener('click', async () => {
            if (!removeUserId) return;

            modalConfirm.disabled = true;
            modalConfirm.textContent = 'Removing...';

            try {
                const fd = new FormData();
                fd.append('action', 'remove_member');
                fd.append('user_id', removeUserId);

                const res = await fetch('project-members.php', {
                    method: 'POST',
                    body: fd
                });
                const result = await res.json();

                if (result.success) {
                    // Remove the card from DOM
                    const card = document.querySelector(`.member-card[data-user-id="${removeUserId}"]`);
                    if (card) {
                        card.style.transition = 'opacity 0.3s, transform 0.3s';
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.9)';
                        setTimeout(() => {
                            card.remove();
                            // Update count
                            const countEl = document.getElementById('members-count');
                            const currentCount = parseInt(countEl.textContent) - 1;
                            countEl.textContent = currentCount;

                            // Show empty state if no members left
                            if (currentCount === 0) {
                                document.getElementById('members-grid').innerHTML = '';
                                const emptyDiv = document.createElement('div');
                                emptyDiv.className = 'members-empty';
                                emptyDiv.innerHTML = '<i data-feather="users"></i><p>No members assigned to this project yet.</p>';
                                document.querySelector('.members-content').appendChild(emptyDiv);
                                feather.replace();
                            }
                        }, 300);
                    }
                    closeModal();
                } else {
                    alert(result.message || 'Failed to remove member.');
                }
            } catch (err) {
                console.error('Error removing member:', err);
                alert('An error occurred. Please try again.');
            } finally {
                modalConfirm.disabled = false;
                modalConfirm.textContent = 'Remove';
            }
        });
    });
})();
</script>
</body>
</html>
