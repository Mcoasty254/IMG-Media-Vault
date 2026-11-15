<?php
require_once 'config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['avatar'])) {
    header('Location: profile.php');
    exit;
}

$file = $_FILES['avatar'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    redirect_with('profile.php', 'error', 'Upload failed. Please try again.');
}

if ($file['size'] > 2 * 1024 * 1024) {
    redirect_with('profile.php', 'error', 'File too large. Max 2 MB.');
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$allowed = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/gif'  => 'gif',
];

if (!isset($allowed[$mime])) {
    redirect_with('profile.php', 'error', 'Invalid file type. Only JPG, PNG, WEBP, GIF allowed.');
}

$userId = $_SESSION['user_id'];
$objectPath = $userId . '/' . time() . '.' . $allowed[$mime];
$binary = file_get_contents($file['tmp_name']);
if ($binary === false) {
    redirect_with('profile.php', 'error', 'Could not read the uploaded file.');
}

try {
    $old = supabase()->getOne('profiles', 'id=eq.' . rawurlencode($userId) . '&select=avatar_url');
    $publicUrl = supabase()->uploadPublic($objectPath, $binary, $mime);

    supabase()->update('profiles', 'id=eq.' . rawurlencode($userId), [
        'avatar_url' => $publicUrl,
    ]);

    $bucket = (string) env('SUPABASE_STORAGE_BUCKET', 'avatars');
    $prefix = '/storage/v1/object/public/' . $bucket . '/';
    if (!empty($old['avatar_url']) && str_contains($old['avatar_url'], $prefix)) {
        $oldPath = substr($old['avatar_url'], strpos($old['avatar_url'], $prefix) + strlen($prefix));
        if ($oldPath !== '') {
            try {
                supabase()->removeObject(rawurldecode($oldPath));
            } catch (Throwable $ignored) {
                // Old file can stay if delete fails; new avatar already saved.
            }
        }
    }

    $_SESSION['avatar'] = $publicUrl;
    redirect_with('profile.php', 'success', 'Profile picture updated!');
} catch (Throwable $e) {
    redirect_with('profile.php', 'error', 'Could not upload to Supabase Storage. Create the avatars bucket first.');
}
