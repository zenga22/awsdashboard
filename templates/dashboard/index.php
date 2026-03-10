<?php
/**
 * Dashboard overview page.
 *
 * Variables: $ec2Summary, $s3BucketCount, $currentProfile, $currentRegion, $allRegions
 */
?>
<h1 class="page-title">Dashboard Overview</h1>

<div class="stats-grid">
    <div class="stat-card accent-blue">
        <span class="stat-label">EC2 Instances (Total)</span>
        <span class="stat-value"><?= $ec2Summary['total'] ?? 0 ?></span>
        <span class="stat-sub"><?= htmlspecialchars($currentRegion) ?></span>
    </div>
    <div class="stat-card accent-green">
        <span class="stat-label">Running Instances</span>
        <span class="stat-value"><?= $ec2Summary['running'] ?? 0 ?></span>
    </div>
    <div class="stat-card accent-red">
        <span class="stat-label">Stopped Instances</span>
        <span class="stat-value"><?= $ec2Summary['stopped'] ?? 0 ?></span>
    </div>
    <div class="stat-card accent-orange">
        <span class="stat-label">S3 Buckets</span>
        <span class="stat-value"><?= $s3BucketCount ?? 0 ?></span>
        <span class="stat-sub">in <?= htmlspecialchars($currentRegion) ?></span>
    </div>
</div>

<!-- Quick links -->
<div class="card">
    <div class="card-header">Quick Navigation</div>
    <div class="card-body">
        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
            <a href="<?= $basePath ?>?page=ec2-instances&profile=<?= urlencode($currentProfile) ?>&region=<?= urlencode($currentRegion) ?>"
               class="btn btn-primary">View EC2 Instances</a>
            <a href="<?= $basePath ?>?page=ec2-reserved&profile=<?= urlencode($currentProfile) ?>&region=<?= urlencode($currentRegion) ?>"
               class="btn btn-outline">View Reserved Instances</a>
            <a href="<?= $basePath ?>?page=s3-buckets&profile=<?= urlencode($currentProfile) ?>&region=<?= urlencode($currentRegion) ?>"
               class="btn btn-primary">Browse S3 Buckets</a>
        </div>
    </div>
</div>

<!-- Region overview -->
<div class="card">
    <div class="card-header">Configured Regions for This Profile</div>
    <div class="card-body">
        <div class="region-tabs">
            <?php foreach ($regions as $r): ?>
                <a href="<?= $basePath ?>?page=dashboard&profile=<?= urlencode($currentProfile) ?>&region=<?= urlencode($r) ?>"
                   class="<?= $r === $currentRegion ? 'active' : '' ?>">
                    <?= htmlspecialchars($r) ?>
                    <br><small><?= htmlspecialchars($allRegions[$r] ?? '') ?></small>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
