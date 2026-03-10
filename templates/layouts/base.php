<?php
/**
 * Base layout template.
 *
 * Variables expected:
 *  $pageTitle   - string
 *  $activeNav   - string (nav item key)
 *  $breadcrumbs - array of ['label' => '...', 'url' => '...' | null]
 *  $profiles    - array from ProfileManager
 *  $currentProfile - string
 *  $currentRegion  - string
 *  $regions     - array of region codes for current profile
 *  $allRegions  - array of code => label
 *  $content     - callable that outputs page body
 */

$pageTitle      = $pageTitle ?? 'AWS Dashboard';
$activeNav      = $activeNav ?? 'dashboard';
$breadcrumbs    = $breadcrumbs ?? [];
$currentProfile = $currentProfile ?? 'default';
$currentRegion  = $currentRegion ?? 'us-east-1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> – AWS Dashboard</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<div class="app-wrapper">

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <span class="aws-logo">AWS</span> <span>Dashboard</span>
        </div>
        <ul class="sidebar-nav">
            <li>
                <a href="/?page=dashboard&profile=<?= urlencode($currentProfile) ?>&region=<?= urlencode($currentRegion) ?>"
                   class="<?= $activeNav === 'dashboard' ? 'active' : '' ?>">
                    &#9632; <span>Dashboard</span>
                </a>
            </li>

            <li class="nav-section">EC2</li>
            <li>
                <a href="/?page=ec2-instances&profile=<?= urlencode($currentProfile) ?>&region=<?= urlencode($currentRegion) ?>"
                   class="<?= $activeNav === 'ec2-instances' ? 'active' : '' ?>">
                    &#9654; <span>Instances</span>
                </a>
            </li>
            <li>
                <a href="/?page=ec2-reserved&profile=<?= urlencode($currentProfile) ?>&region=<?= urlencode($currentRegion) ?>"
                   class="<?= $activeNav === 'ec2-reserved' ? 'active' : '' ?>">
                    &#9654; <span>Reserved Instances</span>
                </a>
            </li>

            <li class="nav-section">S3</li>
            <li>
                <a href="/?page=s3-buckets&profile=<?= urlencode($currentProfile) ?>&region=<?= urlencode($currentRegion) ?>"
                   class="<?= $activeNav === 's3-buckets' ? 'active' : '' ?>">
                    &#9654; <span>Buckets</span>
                </a>
            </li>
        </ul>
    </aside>

    <!-- Main content -->
    <div class="main-content">

        <!-- Top bar -->
        <header class="topbar">
            <ul class="breadcrumb">
                <li><a href="/?page=dashboard&profile=<?= urlencode($currentProfile) ?>&region=<?= urlencode($currentRegion) ?>">Home</a></li>
                <?php foreach ($breadcrumbs as $crumb): ?>
                    <li>
                        <?php if ($crumb['url']): ?>
                            <a href="<?= htmlspecialchars($crumb['url']) ?>"><?= htmlspecialchars($crumb['label']) ?></a>
                        <?php else: ?>
                            <?= htmlspecialchars($crumb['label']) ?>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="profile-selector" style="display: flex; gap: 10px; align-items: center;">
                <form method="get" id="profile-form" style="display: flex; gap: 8px;">
                    <input type="hidden" name="page" value="<?= htmlspecialchars($_GET['page'] ?? 'dashboard') ?>">

                    <label style="font-size: 12px; color: #687078; display: flex; align-items: center; gap: 4px;">
                        Profile:
                        <select name="profile" class="auto-submit">
                            <?php foreach ($profiles as $p): ?>
                                <option value="<?= htmlspecialchars($p['name']) ?>"
                                    <?= $p['name'] === $currentProfile ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['label']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label style="font-size: 12px; color: #687078; display: flex; align-items: center; gap: 4px;">
                        Region:
                        <select name="region" class="auto-submit">
                            <?php foreach ($regions as $r): ?>
                                <option value="<?= htmlspecialchars($r) ?>"
                                    <?= $r === $currentRegion ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($r) ?>
                                    (<?= htmlspecialchars($allRegions[$r] ?? $r) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </form>
            </div>
        </header>

        <!-- Page body -->
        <div class="page-body">
            <?php
            if (isset($flashSuccess)) {
                echo '<div class="alert alert-success">' . htmlspecialchars($flashSuccess) . '</div>';
            }
            if (isset($flashError)) {
                echo '<div class="alert alert-danger">' . htmlspecialchars($flashError) . '</div>';
            }

            if (is_callable($content)) {
                $content();
            }
            ?>
        </div>

        <footer class="footer">
            AWS Dashboard &copy; <?= date('Y') ?> &middot; Profile: <strong><?= htmlspecialchars($currentProfile) ?></strong> &middot; Region: <strong><?= htmlspecialchars($currentRegion) ?></strong>
        </footer>
    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal-overlay" id="confirm-modal">
    <div class="modal-box">
        <h3 class="modal-title">Confirm</h3>
        <p class="modal-message">Are you sure?</p>
        <div class="modal-actions">
            <button class="btn btn-outline" onclick="closeModal()">Cancel</button>
            <button class="btn btn-danger btn-confirm">Confirm</button>
        </div>
    </div>
</div>

<script src="/js/dashboard.js"></script>
</body>
</html>
