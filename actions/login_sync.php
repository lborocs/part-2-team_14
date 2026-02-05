<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['loggedIn' => false]);
    exit();
}

echo json_encode([
    'loggedIn' => true,
    'user' => [
        'user_id' => $_SESSION['user_id'],
        'email' => $_SESSION['email'],
        'name' => $_SESSION['name'],
        'role' => $_SESSION['role'],
        'profile_picture'=> $_SESSION['profile_picture']
    ]
]);
?>