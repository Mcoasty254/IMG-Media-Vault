<?php
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IMG Media Vault · Sign In</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="vault-card">
        <h1><span>🗄️</span> IMG Media <span class="logo-accent">Vault</span></h1>
        <div class="subhead">welcome back<br>secure media &amp; document storage</div>

        <?php if (isset($_SESSION['flash_error'])): ?>
            <div class="message error"><?= h($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['flash_success'])): ?>
            <div class="message success"><?= h($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST" class="auth-form">
            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="you@example.com"
                       value="<?= h($_POST['email'] ?? '') ?>" required autocomplete="email">
            </div>
            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="••••••••"
                       required autocomplete="current-password">
            </div>
            <button type="submit" class="btn-primary">Log In →</button>
        </form>

        <p style="margin-top:1.5rem; text-align:center; font-size:0.9rem; color:#5b7d70;">
            No account? <a href="register.php">Create one</a>
            &nbsp;·&nbsp; <a href="index.html">← Home</a>
        </p>
    </div>
    <script src="js/script.js"></script>
</body>
</html>