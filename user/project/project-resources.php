<?php
header('Content-Type: application/json; charset=utf-8');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

require_once __DIR__ . '/../../config/database.php';

/*
|--------------------------------------------------------------------------
| I HARD CODED IT FOR NOW
|--------------------------------------------------------------------------
| 
*/
$HARDCODED_PROJECT_ID  = 1;
$HARDCODED_UPLOADED_BY = 1;

/*
|--------------------------------------------------------------------------
| Upload rules
|--------------------------------------------------------------------------
*/
$ALLOWED_EXT = ['pdf','doc','docx','xls','xlsx','png','jpg','jpeg','txt'];
$MAX_SIZE_BYTES = 10 * 1024 * 1024; // 10MB
$UPLOAD_DIR = __DIR__ . '/../../uploads/resources'; // projectRoot/uploads/resources

function fail(string $msg, int $code = 400): void {
  http_response_code($code);
  echo json_encode(['success' => false, 'message' => $msg]);
  exit;
}

function safe_name(string $name): string {
  $base = pathinfo($name, PATHINFO_FILENAME);
  $base = preg_replace('/[^a-zA-Z0-9 _-]/', '', $base);
  $base = trim(preg_replace('/\s+/', '_', $base));
  return $base !== '' ? $base : 'file';
}

try {
  $db = new Database();
  $conn = $db->getConnection();
  if (!$conn) fail('Database connection failed', 500);
} catch (Exception $e) {
  fail('Database init failed: ' . $e->getMessage(), 500);
}

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

/*
|--------------------------------------------------------------------------
| LIST (GET) 
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
  try {
    $stmt = $conn->prepare("
      SELECT resource_id, file_name, file_type, file_size, file_path,
             project_id, uploaded_by, description, uploaded_at
      FROM project_resources
      WHERE project_id = :pid
      ORDER BY uploaded_at DESC
    ");
    $stmt->execute([':pid' => $HARDCODED_PROJECT_ID]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
      'success' => true,
      'project_id' => $HARDCODED_PROJECT_ID,
      'resources' => $rows
    ]);
    exit;
  } catch (Exception $e) {
    fail('List failed: ' . $e->getMessage(), 500);
  }
}

/*
|--------------------------------------------------------------------------
| UPLOAD (POST)
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'upload') {
  if (!isset($_FILES['resource_file'])) fail('No file uploaded (resource_file missing)');

  $f = $_FILES['resource_file'];

  if ($f['error'] !== UPLOAD_ERR_OK) fail('Upload error code: ' . $f['error']);
  if ($f['size'] > $MAX_SIZE_BYTES) fail('File too large (max 10MB)');

  $originalName = $f['name'];
  $tmpPath = $f['tmp_name'];

  $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
  if ($ext === '' || !in_array($ext, $ALLOWED_EXT, true)) {
    fail('Invalid file type. Allowed: ' . implode(', ', $ALLOWED_EXT));
  }

  if (!is_dir($UPLOAD_DIR)) {
    if (!mkdir($UPLOAD_DIR, 0775, true)) fail('Failed to create upload folder', 500);
  }

  $uniqueName = safe_name($originalName) . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
  $destPath = rtrim($UPLOAD_DIR, '/\\') . DIRECTORY_SEPARATOR . $uniqueName;

  if (!move_uploaded_file($tmpPath, $destPath)) fail('Failed to save file', 500);

  // store relative path in DB
  $dbFilePath = 'uploads/resources/' . $uniqueName;

  $description = isset($_POST['description']) ? trim($_POST['description']) : '';

  try {
    $stmt = $conn->prepare("
      INSERT INTO project_resources
        (file_name, file_type, file_size, file_path, project_id, uploaded_by, description, uploaded_at)
      VALUES
        (:file_name, :file_type, :file_size, :file_path, :project_id, :uploaded_by, :description, NOW())
    ");

    $stmt->execute([
      ':file_name'   => $originalName,
      ':file_type'   => $ext,
      ':file_size'   => (int)$f['size'],
      ':file_path'   => $dbFilePath,
      ':project_id'  => $HARDCODED_PROJECT_ID,
      ':uploaded_by' => $HARDCODED_UPLOADED_BY,
      ':description' => $description
    ]);

    $newId = $conn->lastInsertId();

    echo json_encode([
      'success' => true,
      'message' => 'Uploaded',
      'resource' => [
        'resource_id' => $newId,
        'file_name' => $originalName,
        'file_type' => $ext,
        'file_size' => (int)$f['size'],
        'file_path' => $dbFilePath,
        'project_id' => $HARDCODED_PROJECT_ID,
        'uploaded_by' => $HARDCODED_UPLOADED_BY,
        'description' => $description,
        'uploaded_at' => date('Y-m-d H:i:s')
      ]
    ]);
    exit;

  } catch (Exception $e) {
    if (file_exists($destPath)) @unlink($destPath);
    fail('DB insert failed: ' . $e->getMessage(), 500);
  }
}

fail('Invalid route. Use GET ?action=list or POST ?action=upload', 404);
