<?php
require_once 'config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$userId   = $_SESSION['user_id'];
$title    = trim($_POST['title'] ?? '');
$category = $_POST['category'] ?? '';
$url      = trim($_POST['url'] ?? '');
$allowed  = ['video', 'audio', 'image', 'document'];

if ($title === '' || mb_strlen($title) > 150 || !in_array($category, $allowed, true)) {
    redirect_with('dashboard.php', 'error', 'Title and a valid category are required.');
}

$hasFile = isset($_FILES['file']) && ($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

if (!$hasFile && $url === '') {
    redirect_with('dashboard.php', 'error', 'Add a file or paste a URL.');
}

$storagePath = null;
$mimeType    = null;
$fileSize    = null;

if ($hasFile) {
    $file = $_FILES['file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        redirect_with('dashboard.php', 'error', 'Upload failed (error code ' . (int) $file['error'] . ').');
    }
    if ($file['size'] > 25 * 1024 * 1024) {
        redirect_with('dashboard.php', 'error', 'File too large. Maximum is 25 MB.');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = $finfo ? finfo_file($finfo, $file['tmp_name']) : false;
    if ($finfo) {
        finfo_close($finfo);
    }

    $map = [
        'image' => [
            'image/jpeg'    => 'jpg',
            'image/png'     => 'png',
            'image/webp'    => 'webp',
            'image/gif'     => 'gif',
            'image/svg+xml' => 'svg',
        ],
        'video' => [
            'video/mp4'       => 'mp4',
            'video/webm'      => 'webm',
            'video/quicktime' => 'mov',
        ],
        'audio' => [
            'audio/mpeg' => 'mp3',
            'audio/wav'  => 'wav',
            'audio/ogg'  => 'ogg',
            'audio/mp4'  => 'm4a',
        ],
        'document' => [
            'application/pdf'                                                                    => 'pdf',
            'text/plain'                                                                         => 'txt',
            'text/csv'                                                                           => 'csv',
            'application/msword'                                                                 => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'            => 'docx',
            'application/vnd.ms-excel'                                                           => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'                  => 'xlsx',
            'application/zip'                                                                    => 'zip',
            'application/octet-stream'                                                           => 'bin',
        ],
    ];

    if ($mime === false || !isset($map[$category][$mime])) {
        redirect_with('dashboard.php', 'error', 'That file type does not match the chosen category.');
    }

    $ext         = $map[$category][$mime];
    $storagePath = $userId . '/' . bin2hex(random_bytes(8)) . '.' . $ext;
    $binary      = file_get_contents($file['tmp_name']);
    if ($binary === false) {
        redirect_with('dashboard.php', 'error', 'Could not read the uploaded file.');
    }

    try {
        $url = supabase()->uploadPublic($storagePath, $binary, $mime, supabase()->mediaBucket());
    } catch (Throwable $e) {
        redirect_with('dashboard.php', 'error', 'Storage upload failed. Create the "media" bucket first.');
    }

    $mimeType = $mime;
    $fileSize = (int) $file['size'];
}

try {
    supabase()->insert('media', [
        'user_id'      => $userId,
        'title'        => $title,
        'category'     => $category,
        'url'          => $url,
        'storage_path' => $storagePath,
        'mime_type'    => $mimeType,
        'file_size'    => $fileSize,
    ]);
    redirect_with('dashboard.php', 'success', 'Added to your vault.');
} catch (Throwable $e) {
    redirect_with('dashboard.php', 'error', 'Failed to save to the database.');
}
