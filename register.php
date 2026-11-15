<?php
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $terms    = ($_POST['terms'] ?? '') === 'on';

    if (!preg_match("/^[A-Za-z][A-Za-z' .-]{1,79}$/u", $fullName)) {
        redirect_with('register.php', 'error', 'Enter your full name (letters, spaces, apostrophes, 2–80 characters).');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
        redirect_with('register.php', 'error', 'Please enter a valid email address.');
    }
    if (strlen($password) < 8) {
        redirect_with('register.php', 'error', 'Password must be at least 8 characters.');
    }
    if ($password !== $confirm) {
        redirect_with('register.php', 'error', 'Passwords do not match.');
    }
    if (!$terms) {
        redirect_with('register.php', 'error', 'You must agree to the Terms of Service.');
    }

    try {
        if (supabase()->getOne('profiles', 'email=eq.' . rawurlencode($email) . '&select=id')) {
            redirect_with('register.php', 'error', 'That email is already registered.');
        }

        // Derive a safe username from the email, ensure uniqueness
        $base = strtolower(preg_replace('/[^a-z0-9]/i', '', explode('@', $email)[0]));
        $base = substr($base, 0, 20);
        if (strlen($base) < 3) {
            $base = 'user' . $base;
        }
        $username = $base;
        for ($i = 0; $i < 5; $i++) {
            if (!supabase()->getOne('profiles', 'username=eq.' . rawurlencode($username) . '&select=id')) {
                break;
            }
            $username = $base . '_' . bin2hex(random_bytes(2));
        }

        $created = supabase()->insert('profiles', [
            'email'              => $email,
            'username'           => $username,
            'full_name'          => $fullName,
            'display_name'       => $fullName,
            'password_hash'      => password_hash($password, PASSWORD_DEFAULT),
            'avatar_url'         => 'assets/default-avatar.svg',
            'terms_accepted_at'  => gmdate('c'),
        ]);
    } catch (Throwable $e) {
        redirect_with('register.php', 'error', 'Registration failed: ' . $e->getMessage());
    }

    if (!empty($created['id'])) {
        $_SESSION['user_id']      = $created['id'];
        $_SESSION['email']        = $email;
        $_SESSION['username']     = $username;
        $_SESSION['display_name'] = $fullName;
        $_SESSION['avatar']       = 'assets/default-avatar.svg';
        redirect_with('dashboard.php', 'success', 'Welcome to IMG Media Vault, ' . $fullName . '!');
    }

    redirect_with('register.php', 'error', 'Registration failed. Please try again.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IMG Media Vault · Create Account</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="vault-card">
        <h1><span>🗄️</span> IMG Media <span class="logo-accent">Vault</span></h1>
        <div class="subhead">create your vault account</div>

        <?php if (isset($_SESSION['flash_error'])): ?>
            <div class="message error"><?= h($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
        <?php endif; ?>

        <form action="register.php" method="POST" class="auth-form" autocomplete="off">
            <div class="input-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" placeholder="e.g. Ada Nwosu"
                       value="<?= h($_POST['full_name'] ?? '') ?>" required maxlength="80">
            </div>
            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="you@example.com"
                       value="<?= h($_POST['email'] ?? '') ?>" required maxlength="100">
            </div>
            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="at least 8 characters"
                       required minlength="8">
            </div>
            <div class="input-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password"
                       placeholder="repeat your password" required minlength="8">
            </div>

            <label class="check-group" for="terms">
                <input type="checkbox" id="terms" name="terms" value="on" required>
                <span>I agree to the <a href="#" onclick="return false;">Terms of Service</a> and
                      <a href="#" onclick="return false;">Privacy Policy</a>.</span>
            </label>

            <button type="submit" class="btn-primary">Create Account →</button>
        </form>

        <p style="margin-top:1.5rem; text-align:center; font-size:0.9rem; color:#5b7d70;">
            Already have an account? <a href="index.php">Sign in</a>
            &nbsp;·&nbsp; <a href="index.html">← Home</a>
        </p>
    </div>
    <script src="js/script.js"></script>
</body>
</html>