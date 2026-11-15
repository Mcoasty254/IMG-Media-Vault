<?php
require_once 'config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id'])) {
    header('Location: dashboard.php');
    exit;
}

$id = (string) $_POST['id'];
if (!valid_uuid($id)) {
    redirect_with('dashboard.php', 'error', 'Invalid media item.');
}

$userId = $_SESSION['user_id'];

try {
    $row = supabase()->getOne(
        'media',
        'id=eq.' . rawurlencode($id) . '&user_id=eq.' . rawurlencode($userId) . '&select=storage_path'
    );

    if (!$row) {
        redirect_with('dashboard.php', 'error', 'Item not found.');
    }

    supabase()->delete(
        'media',
        'id=eq.' . rawurlencode($id) . '&user_id=eq.' . rawurlencode($userId)
    );

    if (!empty($row['storage_path'])) {
        try {
            supabase()->removeObject($row['storage_path'], supabase()->mediaBucket());
        } catch (Throwable $ignored) {
            // Row is gone; an orphaned object is harmless.
        }
    }

    redirect_with('dashboard.php', 'success', 'Item deleted from your vault.');
} catch (Throwable $e) {
    redirect_with('dashboard.php', 'error', 'Could not delete that item.');
}
