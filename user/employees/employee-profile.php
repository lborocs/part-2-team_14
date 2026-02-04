<?php
require_once __DIR__ . "/../../config/database.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Invalid employee ID');
}

$employeeId = (int) $_GET['id'];

$database = new Database();
$pdo = $database->getConnection();

$stmt = $pdo->prepare("
    SELECT first_name, last_name, role, profile_picture, specialties
    FROM users
    WHERE user_id = ?
");
$stmt->execute([$employeeId]);
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$employee) {
    die('Employee not found');
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($employee['first_name']) ?> Profile</title>
</head>
<body>
    <h1>
        <?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?>
    </h1>
    <p><?= ucfirst(str_replace('_', ' ', $employee['role'])) ?></p>
</body>
</html>
