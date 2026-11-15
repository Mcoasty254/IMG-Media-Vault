<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    redirect_with('index.php', 'error', 'Enter your email and password.');
}

try {
    $user = supabase()->getOne(
        'profiles',
        'email=eq.' . rawurlencode($email) . '&select=id,email,username,full_name,display_name,password_hash,avatar_url'
    );
} catch (Throwable $e) {
    redirect_with('index.php', 'error', 'Could not reach Supabase. Check your .env keys.');
}

if ($user && password_verify($password, $user['password_hash'] ?? '')) {
    $_SESSION['user_id']      = $user['id'];
    $_SESSION['email']        = $user['email'] ?? '';
    $_SESSION['username']     = $user['username'] ?? '';
    $_SESSION['display_name'] = ($user['display_name'] ?? '') !== ''
        ? $user['display_name']
        : (($user['full_name'] ?? '') !== '' ? $user['full_name'] : ($user['username'] ?? 'Account'));
    $_SESSION['avatar']       = ($user['avatar_url'] ?? '') !== ''
        ? $user['avatar_url']
        : 'assets/default-avatar.svg';

    session_regenerate_id(true);
    redirect_with('dashboard.php', 'success', 'Signed in successfully.');
}

redirect_with('index.php', 'error', 'Invalid email or password.');