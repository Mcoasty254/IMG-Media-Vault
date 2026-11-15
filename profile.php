<?php
require_once 'config.php';
requireLogin();

$userId = $_SESSION['user_id'];
$pageTitle = 'Profile';

try {
    $user = supabase()->getOne(
        'profiles',
        'id=eq.' . rawurlencode($userId) . '&select=id,username,display_name,avatar_url,created_at'
    );
} catch (Throwable $e) {
    $user = null;
}

if (!$user) {
    header('Location: logout.php');
    exit;
}

include 'includes/header.php';
?>
<div class="vault-card" style="max-width:640px;">
    <h1><span>👤</span> Your <span class="logo-accent">Profile</span></h1>
    <div class="subhead">manage how you appear in the vault</div>

    <?php if (isset($_SESSION['flash_success'])): ?>
        <div class="message success"><?= h($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="message error"><?= h($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
    <?php endif; ?>

    <div class="profile-avatar-row">
        <img src="<?= h($user['avatar_url'] ?: 'assets/default-avatar.svg') ?>"
             alt="avatar" class="avatar-lg"
             onerror="this.src='assets/default-avatar.svg'">

        <div>
            <form action="upload_avatar.php" method="POST" enctype="multipart/form-data" class="avatar-form">
                <label class="btn-secondary" style="cursor:pointer;">
                    📷 Choose New Picture
                    <input type="file" name="avatar" accept="image/png,image/jpeg,image/webp,image/gif"
                           required style="display:none;">
                </label>
            </form>
            <p class="hint">JPG, PNG, WEBP or GIF · max 2 MB · stored in Supabase Storage</p>
        </div>
    </div>

    <form action="update_profile.php" method="POST" class="auth-form" style="margin-top:2rem;">
        <input type="hidden" name="action" value="name">
        <div class="input-group">
            <label for="display_name">Display Name</label>
            <input type="text" id="display_name" name="display_name"
                   value="<?= h($user['display_name'] ?? '') ?>"
                   maxlength="80" required>
        </div>
        <button type="submit" class="btn-primary">Save Display Name</button>
    </form>

    <form action="update_profile.php" method="POST" class="auth-form" style="margin-top:2rem;">
        <input type="hidden" name="action" value="password">
        <h3 style="font-size:1rem; color:#0a5a3c; margin-bottom:0.6rem;">Change Password</h3>
        <div class="input-group">
            <label for="current_password">Current Password</label>
            <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
        </div>
        <div class="input-group">
            <label for="new_password">New Password</label>
            <input type="password" id="new_password" name="new_password" minlength="8" required autocomplete="new-password">
        </div>
        <div class="input-group">
            <label for="confirm_password">Confirm New Password</label>
            <input type="password" id="confirm_password" name="confirm_password" minlength="8" required autocomplete="new-password">
        </div>
        <button type="submit" class="btn-primary">Update Password</button>
    </form>

    <div class="divider" style="font-size:0.85rem; color:#5b7d70;">
        <div><strong>Username:</strong> @<?= h($user['username']) ?></div>
        <div style="margin-top:0.3rem;"><strong>Member since:</strong> <?= date('M j, Y', strtotime($user['created_at'])) ?></div>
    </div>

    <div style="display:flex; justify-content:space-between; margin-top:2rem; gap:0.6rem; flex-wrap:wrap;">
        <a href="dashboard.php" class="logout-btn">← Back to Vault</a>
        <a href="logout.php" class="logout-btn">⏻ Sign out</a>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
