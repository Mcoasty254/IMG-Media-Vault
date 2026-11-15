<?php
require_once 'config.php';
requireLogin();

$userId  = $_SESSION['user_id'];
$allowed = ['video', 'audio', 'image', 'document'];

$filter = $_GET['cat'] ?? 'all';
if (!in_array($filter, array_merge(['all'], $allowed), true)) {
    $filter = 'all';
}
$q = trim($_GET['q'] ?? '');

$query = 'user_id=eq.' . rawurlencode($userId) . '&select=*&order=created_at.desc';
if ($filter !== 'all') {
    $query .= '&category=eq.' . rawurlencode($filter);
}
if ($q !== '') {
    $query .= '&or=(title.ilike.*' . rawurlencode($q) . '*,url.ilike.*' . rawurlencode($q) . '*)';
}

try {
    $media = supabase()->get('media', $query);
} catch (Throwable $e) {
    $media = [];
    $_SESSION['flash_error'] = 'Could not load media from Supabase.';
}

$totalFiles = count($media);
$totalBytes = array_sum(array_map(static fn ($m) => (int) ($m['file_size'] ?? 0), $media));

$pageTitle = 'Dashboard';
include 'includes/header.php';
?>
<div class="vault-card">
    <h1><span>🗄️</span> IMG Media <span class="logo-accent">Vault</span></h1>
    <div class="subhead">your secure media library<br>images · videos · audio · documents</div>

    <?php if (isset($_SESSION['flash_success'])): ?>
        <div class="message success"><?= h($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="message error"><?= h($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
    <?php endif; ?>

    <h2 style="display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;">
        <span>📁 Your Vault</span>
        <a href="profile.php" class="user-badge user-badge-avatar">
            <img src="<?= h($_SESSION['avatar'] ?? 'assets/default-avatar.svg') ?>" alt="avatar"
                 class="avatar-xs" onerror="this.src='assets/default-avatar.svg'">
            <?= h($_SESSION['display_name'] ?? $_SESSION['username'] ?? 'Account') ?>
        </a>
    </h2>

    <div class="stats">
        <span class="stat"><strong><?= $totalFiles ?></strong> item<?= $totalFiles === 1 ? '' : 's' ?></span>
        <span class="stat"><strong><?= number_format($totalBytes / 1048576, 1) ?></strong> MB in files</span>
    </div>

    <div class="filter-bar">
        <a href="dashboard.php" class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>">All</a>
        <a href="dashboard.php?cat=video" class="filter-btn <?= $filter === 'video' ? 'active' : '' ?>">🎬 Video</a>
        <a href="dashboard.php?cat=audio" class="filter-btn <?= $filter === 'audio' ? 'active' : '' ?>">🎧 Audio</a>
        <a href="dashboard.php?cat=image" class="filter-btn <?= $filter === 'image' ? 'active' : '' ?>">🖼️ Image</a>
        <a href="dashboard.php?cat=document" class="filter-btn <?= $filter === 'document' ? 'active' : '' ?>">📄 Document</a>
    </div>

    <form action="dashboard.php" method="GET" class="search-bar">
        <input type="text" name="q" placeholder="Search your vault…" value="<?= h($q) ?>">
        <?php if ($filter !== 'all'): ?>
            <input type="hidden" name="cat" value="<?= h($filter) ?>">
        <?php endif; ?>
        <button type="submit" class="btn-secondary">Search</button>
    </form>

    <form action="add_media.php" method="POST" enctype="multipart/form-data" class="add-form">
        <input type="text" name="title" placeholder="Title" required maxlength="150">
        <select name="category" required>
            <option value="">Category…</option>
            <option value="video">🎬 Video</option>
            <option value="audio">🎧 Audio</option>
            <option value="image">🖼️ Image</option>
            <option value="document">📄 Document</option>
        </select>
        <input type="url" name="url" placeholder="https://… (or upload a file below)">
        <input type="file" name="file" accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.txt,.csv,.xls,.xlsx,.zip">
        <button type="submit" class="btn-primary">+ Add</button>
    </form>

    <?php if (empty($media)): ?>
        <div class="media-link-area">
            <?= $q !== '' ? 'No results for “' . h($q) . '”.' : 'Your vault is empty. Add a file or link above ☝️' ?>
        </div>
    <?php else: ?>
        <div class="media-list">
            <?php foreach ($media as $item): ?>
                <?php $isImage = $item['category'] === 'image'; ?>
                <div class="media-item">
                    <div class="media-meta">
                        <?php if ($isImage): ?>
                            <img src="<?= h($item['url']) ?>" alt="" class="media-thumb"
                                 onerror="this.replaceWith(Object.assign(document.createElement('span'),{className:'media-icon',textContent:'🖼️'}))">
                        <?php else: ?>
                            <span class="media-icon"><?= ['video'=>'🎬','audio'=>'🎧','document'=>'📄'][$item['category']] ?? '📁' ?></span>
                        <?php endif; ?>
                        <div>
                            <strong><?= h($item['title']) ?></strong>
                            <a href="<?= h($item['url']) ?>" target="_blank" rel="noopener noreferrer">
                                <?= $item['storage_path'] ? 'Open file ↗' : h($item['url']) ?>
                            </a>
                            <div style="font-size:0.72rem; color:#5b7d70; margin-top:0.2rem;">
                                <?= h(date('M j, Y', strtotime($item['created_at']))) ?>
                                <?php if (!empty($item['file_size'])): ?>
                                    · <?= number_format($item['file_size'] / 1024, 0) ?> KB
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="media-actions">
                        <a href="<?= h($item['url']) ?>" target="_blank" rel="noopener noreferrer"
                           class="logout-btn" title="Download">↓</a>
                        <form action="delete_media.php" method="POST">
                            <input type="hidden" name="id" value="<?= h($item['id']) ?>">
                            <button type="submit" class="logout-btn">✕ Delete</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div style="display:flex; justify-content:space-between; margin-top:1.8rem; gap:0.6rem; flex-wrap:wrap;">
        <a href="profile.php" class="logout-btn">👤 Profile</a>
        <a href="logout.php" class="logout-btn">⏻ Sign out</a>
    </div>
</div>
<?php include 'includes/footer.php'; ?>