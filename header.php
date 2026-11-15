<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$navName = $_SESSION['display_name'] ?? $_SESSION['username'] ?? 'Account';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IMG Media Vault · <?= h($pageTitle ?? 'Dashboard') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php if (isset($_SESSION['user_id'])): ?>
<nav class="topbar">
    <a href="dashboard.php" class="topbar-brand">
        🗄️ IMG Media <span class="logo-accent">Vault</span>
    </a>
    <div class="topbar-right">
        <a href="profile.php" class="topbar-user">
            <img src="<?= h($_SESSION['avatar'] ?? 'assets/default-avatar.svg') ?>"
                 alt="avatar" class="avatar-sm"
                 onerror="this.src='assets/default-avatar.svg'">
            <span><?= h($navName) ?></span>
        </a>
        <a href="logout.php" class="logout-btn" title="Sign out">⏻</a>
    </div>
</nav>
<?php endif; ?>