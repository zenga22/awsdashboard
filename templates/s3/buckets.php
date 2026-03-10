<?php
/**
 * S3 Buckets list page.
 *
 * Variables: $buckets, $currentProfile, $currentRegion, $regions, $allRegions
 */

use AwsDashboard\Services\S3Service;
?>
<h1 class="page-title">S3 Buckets</h1>

<!-- Region tabs -->
<div class="region-tabs">
    <a href="/?page=s3-buckets&profile=<?= urlencode($currentProfile) ?>&region=<?= urlencode($currentRegion) ?>&filter=all"
       class="<?= ($regionFilter ?? '') === 'all' ? 'active' : '' ?>">
        All Regions
    </a>
    <?php foreach ($regions as $r): ?>
        <a href="/?page=s3-buckets&profile=<?= urlencode($currentProfile) ?>&region=<?= urlencode($r) ?>&filter=region"
           class="<?= $r === $currentRegion && ($regionFilter ?? 'region') === 'region' ? 'active' : '' ?>">
            <?= htmlspecialchars($r) ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="stats-grid">
    <div class="stat-card accent-orange">
        <span class="stat-label">Buckets Found</span>
        <span class="stat-value"><?= count($buckets) ?></span>
        <span class="stat-sub">
            <?= ($regionFilter ?? 'region') === 'all' ? 'All regions' : htmlspecialchars($currentRegion) ?>
        </span>
    </div>
</div>

<!-- Buckets table -->
<div class="card">
    <div class="card-header">
        S3 Buckets
        <span style="font-weight: normal; font-size: 12px; color: #687078;">
            (<?= count($buckets) ?> bucket<?= count($buckets) !== 1 ? 's' : '' ?>)
        </span>
    </div>
    <div class="card-body">
        <?php if (empty($buckets)): ?>
            <div class="empty-state">
                <div class="icon">&#128463;</div>
                <p>No S3 buckets found<?= ($regionFilter ?? 'region') !== 'all' ? ' in ' . htmlspecialchars($currentRegion) : '' ?>.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                    <tr>
                        <th data-sort="string">Bucket Name</th>
                        <th data-sort="string">Region</th>
                        <th data-sort="date">Created</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($buckets as $bucket): ?>
                        <tr>
                            <td>
                                <a href="/?page=s3-objects&profile=<?= urlencode($currentProfile) ?>&region=<?= urlencode($bucket['Region']) ?>&bucket=<?= urlencode($bucket['Name']) ?>">
                                    <?= htmlspecialchars($bucket['Name']) ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($bucket['Region']) ?></td>
                            <td><?= htmlspecialchars($bucket['CreationDate']) ?></td>
                            <td>
                                <a href="/?page=s3-objects&profile=<?= urlencode($currentProfile) ?>&region=<?= urlencode($bucket['Region']) ?>&bucket=<?= urlencode($bucket['Name']) ?>"
                                   class="btn btn-outline btn-sm">Browse</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
