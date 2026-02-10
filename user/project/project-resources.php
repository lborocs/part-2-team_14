<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (isset($_GET['project_id'])) {
    $_SESSION['current_project_id'] = (int) $_GET['project_id'];
}

$projectId = $_SESSION['current_project_id'] ?? null;

// Handle API requests
if (isset($_GET['action']) || isset($_POST['action'])) {
    header('Content-Type: application/json');

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit;
    }

    if (!$projectId) {
        echo json_encode(['success' => false, 'message' => 'No project selected']);
        exit;
    }

    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    $userId = $_SESSION['user_id'];

    try {
        $database = new Database();
        $pdo = $database->getConnection();

        function canUploadDelete($pdo, $projectId, $userId) {
            $sql = "SELECT created_by, team_leader_id FROM projects WHERE project_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$projectId]);
            $project = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$project) return false;

            return ($project['created_by'] == $userId || $project['team_leader_id'] == $userId);
        }

        // LIST FILES & PROJECT DETAILS
        if ($action === 'list') {
            $sql = "
                SELECT 
                    p.created_at,
                    p.description,
                    p.created_by,
                    p.team_leader_id,

                    creator.first_name AS manager_first_name,
                    creator.last_name  AS manager_last_name,
                    creator.profile_picture AS manager_avatar,
                    creator.user_id AS manager_id,

                    leader.first_name  AS team_leader_first_name,
                    leader.last_name   AS team_leader_last_name,
                    leader.profile_picture   AS team_leader_avatar,
                    leader.user_id AS leader_id

                FROM projects p
                LEFT JOIN users creator ON p.created_by = creator.user_id
                LEFT JOIN users leader  ON p.team_leader_id = leader.user_id
                WHERE p.project_id = ?
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$projectId]);
            $project = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$project) {
                echo json_encode(['success' => false, 'message' => 'Project not found.']);
                exit;
            }

            $sql = "SELECT resource_id, file_name, file_type, file_size, file_path,
                           description, DATE_FORMAT(uploaded_at, '%b %d, %Y') as uploaded_at
                    FROM project_resources
                    WHERE project_id = ?
                    ORDER BY uploaded_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$projectId]);
            $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'project' => $project,
                'resources' => $resources
            ]);
            exit;
        }

        // DELETE FILE
        if ($action === 'delete') {
            // Check permission
            if (!canUploadDelete($pdo, $projectId, $userId)) {
                echo json_encode(['success' => false, 'message' => 'Only your team leader or manager can delete project resources.']);
                exit;
            }

            $resourceId = $_POST['resource_id'] ?? null;
            if (!$resourceId) {
                echo json_encode(['success' => false, 'message' => 'No resource specified']);
                exit;
            }

            // Get file path
            $stmt = $pdo->prepare("SELECT file_path FROM project_resources WHERE resource_id = ? AND project_id = ?");
            $stmt->execute([$resourceId, $projectId]);
            $file = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$file) {
                echo json_encode(['success' => false, 'message' => 'File not found']);
                exit;
            }

            // Delete from database
            $stmt = $pdo->prepare("DELETE FROM project_resources WHERE resource_id = ? AND project_id = ?");
            $stmt->execute([$resourceId, $projectId]);

            // Delete the actual file from server
            $fullPath = __DIR__ . '/../../' . $file['file_path'];
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }

            echo json_encode(['success' => true]);
            exit;
        }

        // UPLOAD FILE
        if ($action === 'upload') {
            // Check permission
            if (!canUploadDelete($pdo, $projectId, $userId)) {
                echo json_encode(['success' => false, 'message' => 'Only your team leader or manager can upload project resources.']);
                exit;
            }

            if (!isset($_FILES['resource_file']) || $_FILES['resource_file']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
                exit;
            }

            $file = $_FILES['resource_file'];
            $description = $_POST['description'] ?? '';

            // Validate file size (10MB max)
            $maxSize = 10 * 1024 * 1024; // 10MB in bytes
            if ($file['size'] > $maxSize) {
                echo json_encode(['success' => false, 'message' => 'File too large (max 10MB)']);
                exit;
            }

            // Validate file type
            $allowedTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg', 'txt'];
            $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($fileExt, $allowedTypes)) {
                echo json_encode(['success' => false, 'message' => 'File type not allowed']);
                exit;
            }

            // Generate unique filename
            $newFileName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
            $uploadDir = __DIR__ . '/../../uploads/resources/';
            $uploadPath = $uploadDir . $newFileName;

            // Create directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
                echo json_encode(['success' => false, 'message' => 'Failed to save file']);
                exit;
            }

            // Insert into database
            $sql = "INSERT INTO project_resources
                    (file_name, file_type, file_size, file_path, project_id, uploaded_by, description)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $file['name'],
                $fileExt,
                $file['size'],
                'uploads/resources/' . $newFileName,
                $projectId,
                $userId,
                $description
            ]);

            echo json_encode([
                'success' => true,
                'resource' => [
                    'resource_id' => $pdo->lastInsertId(),
                    'file_name' => $file['name'],
                    'file_type' => $fileExt,
                    'file_size' => $file['size']
                ]
            ]);
            exit;
        }

        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Make-It-All - Project Resources</title>
  <link rel="stylesheet" href="../dashboard.css" />
  <link rel="stylesheet" href="progress.css" />
  <link rel="stylesheet" href="project-resources.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="icon" type="image/png" href="/favicon.png">
  <script src="https://unpkg.com/feather-icons"></script>
</head>
<body id="project-resources-page">
<?php include '../to-do/todo_widget.php'; ?>
<div class="dashboard-container">
  <nav class="sidebar">
    <div class="nav-top">
      <div class="logo-container">
        <img src="../logo.png" alt="Make-It-All Logo" class="logo-icon" />
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
          <p class="breadcrumbs"><a href="projects-overview.php">Projects</a> > <span id="project-name-breadcrumb">Project</span></p>
          <h1 id="project-name-header">Project</h1>
        </div>
        <button class="close-project-btn" id="close-project-btn" style="display:none;"><i data-feather="archive"></i> Archive Project</button>
      </div>

      <nav class="project-nav" id="project-nav-links">
        <a href="#" class="active">Tasks</a>
        <a href="progress.html">Progress</a>
        <a href="#">Resources</a>
      </nav>
    </header>

    <div class="resource-content">
      <div class="resource-header">
        <h2>Project Resources</h2>
      </div>

      <div class="resource-grid">
        <div class="resource-card">
          <h3>Project Contacts</h3>
          <div class="contact-list" id="project-contacts-list"></div>
        </div>

        <div class="resource-card">
          <h3>Project Details</h3>
          <ul class="details-list">
            <li>
              <strong>Created:</strong>
              <span id="project-created-date">Loading...</span>
            </li>
            <li>
              <strong>Description:</strong>
              <p id="project-description">Loading project details...</p>
            </li>
          </ul>
        </div>

        <!-- NEW: Uploaded Files -->
        <div class="resource-card resource-files-card">
          <h3>Uploaded Files</h3>

          <div class="files-toolbar" id="files-toolbar">
            <input type="file" id="resource-file-input" class="file-hidden-input" />

            <button class="file-choose-btn" id="file-choose-btn" type="button">
              <i data-feather="paperclip"></i> Choose file
            </button>

            <span class="file-chosen-name" id="file-chosen-name">No file chosen</span>

            <input
              type="text"
              id="resource-desc"
              class="file-desc"
              placeholder="Description (optional)"
            />

            <button class="file-upload-btn" id="resource-upload-btn" type="button">
              <i data-feather="upload"></i> Upload
            </button>
          </div>

          <div class="file-hint">Allowed: pdf, doc/docx, xls/xlsx, png/jpg/jpeg, txt (max 10MB)</div>
          <div class="files-status" id="files-status" aria-live="polite"></div>

          <div class="files-list" id="files-list"></div>
          <div class="files-empty" id="files-empty">No files uploaded yet.</div>
        </div>
        <!-- END NEW -->
      </div>
    </div>
  </main>
</div>

<!-- Archive Project Confirm Modal -->
<div class="modal-overlay" id="close-project-modal" style="display:none;">
    <div class="modal-content" style="max-width:520px;">
        <div class="modal-header">
            <h2>Archive Project</h2>
            <button type="button" class="close-btn" id="close-project-x">
                <i data-feather="x"></i>
            </button>
        </div>
        <div class="modal-body">
            <p style="margin:0 0 8px;">Are you sure you want to archive this project?</p>
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

<script>
    window.__ROLE__ = <?= json_encode($_SESSION['role'] ?? 'team_member') ?>;
</script>
<script src="../app.js"></script>

<script>
(function () {
  const $ = (id) => document.getElementById(id);

  function setStatus(msg, type) {
    const el = $('files-status');
    el.textContent = msg || '';
    el.className = 'files-status ' + (type ? ('is-' + type) : '');
  }

  function formatBytes(bytes) {
    const b = Number(bytes || 0);
    if (b < 1024) return b + ' B';
    const kb = b / 1024;
    if (kb < 1024) return kb.toFixed(1) + ' KB';
    const mb = kb / 1024;
    if (mb < 1024) return mb.toFixed(1) + ' MB';
    return (mb / 1024).toFixed(1) + ' GB';
  }

  function escapeHtml(s) {
    return String(s || '').replace(/[&<>"']/g, (c) => ({
      '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
    }[c]));
  }

  function iconForType(ext) {
    ext = (ext || '').toLowerCase();
    if (['png','jpg','jpeg'].includes(ext)) return 'image';
    if (ext === 'pdf') return 'file-text';
    if (['doc','docx'].includes(ext)) return 'file';
    if (['xls','xlsx'].includes(ext)) return 'grid';
    if (ext === 'txt') return 'align-left';
    return 'paperclip';
  }

  async function listFiles() {
    const res = await fetch('project-resources.php?action=list', {
      headers: { 'Accept': 'application/json' }
    });
    const text = await res.text();
    let data;
    try { data = JSON.parse(text); } catch { throw new Error(text); }
    if (!res.ok || data.success === false) throw new Error(data.message || 'Failed to load files');
    return data.resources || [];
  }

  async function uploadFile(file, description) {
    const fd = new FormData();
    fd.append('action', 'upload');
    fd.append('description', description || '');
    fd.append('resource_file', file);

    const res = await fetch('project-resources.php?action=upload', { method: 'POST', body: fd });
    const text = await res.text();
    let data;
    try { data = JSON.parse(text); } catch { throw new Error(text); }
    if (!res.ok || data.success === false) throw new Error(data.message || 'Upload failed');
    return data.resource;
  }

  function render(rows) {
    const list = $('files-list');
    const empty = $('files-empty');

    list.innerHTML = '';

    if (!rows || rows.length === 0) {
      empty.style.display = 'block';
      return;
    }
    empty.style.display = 'none';

    rows.forEach(r => {
      const ext = (r.file_type || '').toLowerCase();
      const downloadHref = `../../${r.file_path}`;

      const row = document.createElement('div');
      row.className = 'file-row';
      row.innerHTML = `
        <div class="file-left">
          <div class="file-ico"><i data-feather="${iconForType(ext)}"></i></div>
          <div class="file-meta">
            <div class="file-name">${escapeHtml(r.file_name)}</div>
            <div class="file-sub">
              <span class="file-pill">${ext ? ext.toUpperCase() : 'FILE'}</span>
              <span class="file-dot">•</span>
              <span>${formatBytes(r.file_size)}</span>
              ${r.uploaded_at ? `<span class="file-dot">•</span><span>${escapeHtml(r.uploaded_at)}</span>` : ''}
            </div>
            ${r.description ? `<div class="file-desc-text">${escapeHtml(r.description)}</div>` : ''}
          </div>
        </div>

        <div class="file-actions">
          <a class="file-download" href="${downloadHref}" target="_blank" rel="noopener">
            <i data-feather="download"></i> Download
          </a>
          <button type="button" class="delete-file-btn" data-resource-id="${r.resource_id}">✖</button>
        </div>
      `;
      list.appendChild(row);
    });

    feather.replace();

    // Attach delete listeners **after DOM is rendered**
    document.querySelectorAll('.delete-file-btn').forEach(btn => {
      btn.addEventListener('click', async () => {
        const resourceId = btn.dataset.resourceId;
        if (!confirm('Are you sure you want to delete this file?')) return;

        setStatus('Deleting...', 'info');
        try {
          const fd = new FormData();
          fd.append('action', 'delete');
          fd.append('resource_id', resourceId);

          const res = await fetch('project-resources.php', { method: 'POST', body: fd });
          const text = await res.text();
          const data = JSON.parse(text);

          if (!data.success) throw new Error(data.message || 'Delete failed');
          setStatus('File deleted successfully.', 'ok');
          await refresh();
        } catch (e) {
          setStatus(e.message || 'Delete failed', 'error');
        }
      });
    });
  }


  async function refresh() {
    try {
      setStatus('', '');
      const rows = await listFiles();
      render(rows);
    } catch (e) {
      setStatus(e.message || 'Failed to fetch', 'error');
      render([]);
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    // Choose button -> open hidden file input
    $('file-choose-btn').addEventListener('click', () => $('resource-file-input').click());

    // Update chosen filename
    $('resource-file-input').addEventListener('change', () => {
      const f = $('resource-file-input').files[0];
      $('file-chosen-name').textContent = f ? f.name : 'No file chosen';
    });

    // Upload
    $('resource-upload-btn').addEventListener('click', async () => {
      const file = $('resource-file-input').files[0];
      const desc = $('resource-desc').value || '';

      if (!file) {
        setStatus('Choose a file first.', 'error');
        return;
      }

      setStatus('Uploading...', 'info');
      try {
        await uploadFile(file, desc);
        $('resource-file-input').value = '';
        $('resource-desc').value = '';
        $('file-chosen-name').textContent = 'No file chosen';
        setStatus('Uploaded successfully.', 'ok');
        await refresh();
      } catch (e) {
        setStatus(e.message || 'Upload failed', 'error');
      }
    });

    refresh();
    feather.replace();
  });
})();
</script>
</body>
</html>
