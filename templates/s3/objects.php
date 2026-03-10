<?php
/**
 * S3 Bucket object browser page.
 *
 * Variables: $bucket, $prefix, $objectData, $bucketSummary, $currentProfile, $currentRegion
 */

use AwsDashboard\Services\S3Service;
?>
<h1 class="page-title">Bucket: <?= htmlspecialchars($bucket) ?></h1>

<!-- Bucket summary -->
<div class="stats-grid">
    <div class="stat-card accent-orange">
        <span class="stat-label">Total Bucket Size</span>
        <span class="stat-value"><?= S3Service::formatSize($bucketSummary['totalSize'] ?? 0) ?></span>
    </div>
    <div class="stat-card accent-blue">
        <span class="stat-label">Total Objects</span>
        <span class="stat-value"><?= number_format($bucketSummary['objectCount'] ?? 0) ?></span>
    </div>
    <div class="stat-card accent-green">
        <span class="stat-label">Current View Size</span>
        <span class="stat-value"><?= S3Service::formatSize($objectData['totalSize'] ?? 0) ?></span>
        <span class="stat-sub"><?= $objectData['objectCount'] ?? 0 ?> object(s) in this folder</span>
    </div>
</div>

<!-- Path breadcrumb -->
<div class="path-breadcrumb">
    <a href="/?page=s3-objects&profile=<?= urlencode($currentProfile) ?>&region=<?= urlencode($currentRegion) ?>&bucket=<?= urlencode($bucket) ?>">
        <?= htmlspecialchars($bucket) ?>
    </a>
    <?php
    if ($prefix !== '') {
        $parts = explode('/', trim($prefix, '/'));
        $accumulated = '';
        foreach ($parts as $part) {
            $accumulated .= $part . '/';
            echo '<span class="sep">/</span>';
            echo '<a href="/?page=s3-objects&profile=' . urlencode($currentProfile) .
                 '&region=' . urlencode($currentRegion) .
                 '&bucket=' . urlencode($bucket) .
                 '&prefix=' . urlencode($accumulated) . '">' .
                 htmlspecialchars($part) . '</a>';
        }
    }
    ?>
</div>

<!-- Objects table -->
<div class="card">
    <div class="card-header">
        Contents
        <?php if ($prefix !== ''): ?>
            <a href="/?page=s3-objects&profile=<?= urlencode($currentProfile) ?>&region=<?= urlencode($currentRegion) ?>&bucket=<?= urlencode($bucket) ?>&prefix=<?= urlencode(dirname($prefix) === '.' ? '' : dirname($prefix) . '/') ?>"
               class="btn btn-outline btn-sm">&#8593; Up</a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if (empty($objectData['prefixes']) && empty($objectData['objects'])): ?>
            <div class="empty-state">
                <div class="icon">&#128463;</div>
                <p>This folder is empty.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                    <tr>
                        <th data-sort="string">Name</th>
                        <th data-sort="string">Type</th>
                        <th data-sort="number">Size</th>
                        <th data-sort="date">Last Modified</th>
                        <th data-sort="string">Storage Class</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <!-- Folders (Common Prefixes) -->
                    <?php foreach ($objectData['prefixes'] as $folderPrefix): ?>
                        <?php $folderName = basename(rtrim($folderPrefix, '/')); ?>
                        <tr>
                            <td>
                                &#128193;
                                <a href="/?page=s3-objects&profile=<?= urlencode($currentProfile) ?>&region=<?= urlencode($currentRegion) ?>&bucket=<?= urlencode($bucket) ?>&prefix=<?= urlencode($folderPrefix) ?>">
                                    <?= htmlspecialchars($folderName) ?>/
                                </a>
                            </td>
                            <td>Folder</td>
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                        </tr>
                    <?php endforeach; ?>

                    <!-- Objects -->
                    <?php foreach ($objectData['objects'] as $obj): ?>
                        <tr>
                            <td>
                                &#128196;
                                <a href="/?page=s3-detail&profile=<?= urlencode($currentProfile) ?>&region=<?= urlencode($currentRegion) ?>&bucket=<?= urlencode($bucket) ?>&key=<?= urlencode($obj['Key']) ?>">
                                    <?= htmlspecialchars($obj['DisplayName']) ?>
                                </a>
                            </td>
                            <td>Object</td>
                            <td><?= S3Service::formatSize($obj['Size']) ?></td>
                            <td><?= htmlspecialchars($obj['LastModified']) ?></td>
                            <td><?= htmlspecialchars($obj['StorageClass']) ?></td>
                            <td>
                                <a href="/?page=s3-detail&profile=<?= urlencode($currentProfile) ?>&region=<?= urlencode($currentRegion) ?>&bucket=<?= urlencode($bucket) ?>&key=<?= urlencode($obj['Key']) ?>"
                                   class="btn btn-outline btn-sm">Details</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($objectData['nextToken']): ?>
                <div style="margin-top: 16px; text-align: center;">
                    <a href="/?page=s3-objects&profile=<?= urlencode($currentProfile) ?>&region=<?= urlencode($currentRegion) ?>&bucket=<?= urlencode($bucket) ?>&prefix=<?= urlencode($prefix) ?>&token=<?= urlencode($objectData['nextToken']) ?>"
                       class="btn btn-outline">Load More</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
