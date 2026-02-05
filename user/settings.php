<?php
session_start();

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
  die("Database connection failed.");
}

// DEV BYPASS
$isLoggedIn = isset($_SESSION['role'], $_SESSION['email'], $_SESSION['user_id']);

if (!$isLoggedIn) {
  $role = 'manager';
  $isManager = true;
  $currentUserId = 1; // TEMP fallback
} else {
  $role = $_SESSION['role'];
  $isManager = ($role === 'manager');
  $currentUserId = $_SESSION['user_id'];
}

// Get current user's profile picture from database
$stmt = $db->prepare("SELECT profile_picture FROM users WHERE user_id = ?");
$stmt->execute([$currentUserId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$profile_picture = $user['profile_picture'];

// Update profile picture in database
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (isset($input['profile_picture'])) {
        $profile_picture = $input['profile_picture'];

        $stmt = $db->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
        if ($stmt->execute([$profile_picture, $currentUserId])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'DB update failed']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'No profile picture provided']);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make-It-All - Settings</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="knowledge-base/knowledge-base.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
</head>
<body id="settings-page"> <div class="dashboard-container">
    <?php include 'to-do/todo_widget.php'; ?>
        <nav class="sidebar">
            <div class="nav-top">
                <div class="logo-container">
                    <img src="logo.png" alt="Make-It-All Logo" class="logo-icon">
                </div>
                <ul class="nav-main">
                    <li><a href="home/home.php"><i data-feather="home"></i>Home</a></li>
                    <li><a href="project/projects-overview.php"><i data-feather="folder"></i>Projects</a></li>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'manager'): ?>
                        <li><a href="employees/employee-directory.php"><i data-feather="users"></i>Employees</a></li>
                    <?php endif; ?>
                    <li><a href="knowledge-base/knowledge-base.html"><i data-feather="book-open"></i>Knowledge Base</a></li>
                </ul>
            </div>
            <div class="nav-footer">
                <ul>
                    <li class="active-parent"><a href="settings.php"><i data-feather="settings"></i>Settings</a></li>
                </ul>
            </div>
        </nav>

        <main class="main-content">
            <header class="kb-header">
                <h1>Settings</h1>
            </header>

            <div class="kb-layout-wrapper">
                <div class="kb-main-content">

                    <!-- Profile Details -->
                    <form id="profile-form" class="settings-card">
                        <h2>Profile Information</h2>
                        <div class="profile-content">
                            <div class="profile-fields">
                                <div class="form-group">
                                    <label for="profile-name">Full Name</label>
                                    <input type="text" id="profile-name" value="Loading..." readonly disabled>
                                </div>
                                <div class="form-group">
                                    <label for="profile-email">Email Address</label>
                                    <input type="email" id="profile-email" value="Loading..." readonly disabled>
                                </div>
                                <div class="form-group">
                                    <label for="profile-role">Role</label>
                                    <input type="text" id="profile-role" value="Loading..." readonly disabled>
                                </div>
                                <button type="submit" class="create-post-btn">Save Profile</button>
                            </div>

                            <div class="profile-avatar">
                                <img id="profile-picture" src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile Picture">
                                <input type="file" id="profile-image-input" accept="image/*" style="display:none;">
                                <div class="avatar-buttons">
                                    <button type="button" class="create-post-btn" id="upload-image-btn">Upload Icon</button>
                                    <button type="button" class="create-post-btn" id="delete-image-btn">Delete Icon</button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <form id="password-form" class="settings-card">
                        <h2>Change Password</h2>
                        <div class="form-group">
                            <label for="current-password">Current Password</label>
                            <input type="password" id="current-password" placeholder="Enter your current password">
                        </div>
                        <div class="form-group">
                            <label for="new-password">New Password</label>
                            <input type="password" id="new-password" placeholder="Enter a new password">
                        </div>
                        <div class="form-group">
                            <label for="confirm-password">Confirm New Password</label>
                            <input type="password" id="confirm-password" placeholder="Confirm your new password">
                        </div>
                        <button type="submit" class="create-post-btn">Update Password</button>
                    </form>

                    <form id="notifications-form" class="settings-card">
                        <h2>Notification Preferences</h2>
                        <div class="checkbox-group">
                            <input type="checkbox" id="notify-replies" checked>
                            <label for="notify-replies">Email me when someone replies to my post</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" id="notify-announcements" checked>
                            <label for="notify-announcements">Email me about company-wide announcements</label>
                        </div>
                        <button type="submit" class="create-post-btn">Save Preferences</button>
                    </form>

                    <div class="settings-card">
                        <h2>Account</h2>
                        <p>Clicking "Sign Out" will end your current session and return you to the login page.</p>
                        <button id="sign-out-btn" class="sign-out-btn">
                            <i data-feather="log-out"></i> Sign Out
                        </button>
                    </div>

                </div>
            </div>
        </main>
    </div>

    <script src="app.js"></script>
</body>
</html>