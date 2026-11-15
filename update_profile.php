<?php
require_once 'config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: profile.php');
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($action === 'name') {
    $display = trim($_POST['display_name'] ?? '');
    if ($display === '' || mb_strlen($display) > 80) {
        redirect_with('profile.php', 'error', 'Display name is required (max 80 chars).');
    }

    try {
        supabase()->update('profiles', 'id=eq.' . rawurlencode($userId), [
            'display_name' => $display,
        ]);
        $_SESSION['display_name'] = $display;
        redirect_with('profile.php', 'success', 'Display name updated.');
    } catch (Throwable $e) {
        redirect_with('profile.php', 'error', 'Could not update display name.');
    }
}

if ($action === 'password') {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (strlen($new) < 8) {
        redirect_with('profile.php', 'error', 'New password must be at least 8 characters.');
    }
    if ($new !== $confirm) {
        redirect_with('profile.php', 'error', 'New passwords do not match.');
    }

    try {
        $row = supabase()->getOne('profiles', 'id=eq.' . rawurlencode($userId) . '&select=password_hash');
        if (!$row || !password_verify($current, $row['password_hash'] ?? '')) {
            redirect_with('profile.php', 'error', 'Current password is incorrect.');
        }

        supabase()->update('profiles', 'id=eq.' . rawurlencode($userId), [
            'password_hash' => password_hash($new, PASSWORD_DEFAULT),
        ]);
        redirect_with('profile.php', 'success', 'Password updated successfully.');
    } catch (Throwable $e) {
        redirect_with('profile.php', 'error', 'Could not update password.');
    }
}

header('Location: profile.php');
exit;
