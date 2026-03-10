<?php
/**
 * EC2 Instances list page.
 *
 * Variables: $instances, $summary, $currentProfile, $currentRegion, $regions, $allRegions
 */

use AwsDashboard\Services\S3Service;
?>
<h1 class="page-title">EC2 Instances</h1>

<!-- Region tabs -->
<div class="region-tabs">
    <?php foreach ($regions as $r): ?>
        <a href="/?page=ec2-instances&profile=<?= urlencode($currentProfile) ?>&region=<?= urlencode($r) ?>"
           class="<?= $r === $currentRegion ? 'active' : '' ?>">
            <?= htmlspecialchars($r) ?>
        </a>
    <?php endforeach; ?>
</div>

<!-- Summary stats -->
<div class="stats-grid">
    <div class="stat-card accent-blue">
        <span class="stat-label">Total Instances</span>
        <span class="stat-value"><?= $summary['total'] ?></span>
    </div>
    <div class="stat-card accent-green">
        <span class="stat-label">Running</span>
        <span class="stat-value"><?= $summary['running'] ?></span>
    </div>
    <div class="stat-card accent-red">
        <span class="stat-label">Stopped</span>
        <span class="stat-value"><?= $summary['stopped'] ?></span>
    </div>
    <div class="stat-card accent-orange">
        <span class="stat-label">Other</span>
        <span class="stat-value"><?= $summary['terminated'] + $summary['other'] ?></span>
    </div>
</div>

<!-- Instances table -->
<div class="card">
    <div class="card-header">
        Instances in <?= htmlspecialchars($currentRegion) ?>
        <span style="font-weight: normal; font-size: 12px; color: #687078;">
            (<?= count($instances) ?> instance<?= count($instances) !== 1 ? 's' : '' ?>)
        </span>
    </div>
    <div class="card-body">
        <?php if (empty($instances)): ?>
            <div class="empty-state">
                <div class="icon">&#9729;</div>
                <p>No EC2 instances found in <?= htmlspecialchars($currentRegion) ?>.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                    <tr>
                        <th data-sort="string">Name</th>
                        <th data-sort="string">Instance ID</th>
                        <th data-sort="string">Type</th>
                        <th data-sort="string">State</th>
                        <th data-sort="string">Public IP</th>
                        <th data-sort="string">Private IP</th>
                        <th data-sort="date">Launch Time</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($instances as $inst): ?>
                        <tr>
                            <td>
                                <a href="/?page=ec2-detail&profile=<?= urlencode($currentProfile) ?>&region=<?= urlencode($currentRegion) ?>&instance=<?= urlencode($inst['InstanceId']) ?>">
                                    <?= htmlspecialchars($inst['Name'] ?: '(no name)') ?>
                                </a>
                            </td>
                            <td><code><?= htmlspecialchars($inst['InstanceId']) ?></code></td>
                            <td><?= htmlspecialchars($inst['InstanceType']) ?></td>
                            <td>
                                <span class="badge badge-<?= htmlspecialchars($inst['State']) ?>">
                                    <?= htmlspecialchars($inst['State']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($inst['PublicIpAddress']) ?></td>
                            <td><?= htmlspecialchars($inst['PrivateIpAddress']) ?></td>
                            <td><?= htmlspecialchars($inst['LaunchTime']) ?></td>
                            <td>
                                <?php if ($inst['State'] === 'running'): ?>
                                    <form method="post" id="reboot-form-<?= htmlspecialchars($inst['InstanceId']) ?>" style="display:inline;">
                                        <input type="hidden" name="action" value="reboot">
                                        <input type="hidden" name="instance_id" value="<?= htmlspecialchars($inst['InstanceId']) ?>">
                                        <input type="hidden" name="profile" value="<?= htmlspecialchars($currentProfile) ?>">
                                        <input type="hidden" name="region" value="<?= htmlspecialchars($currentRegion) ?>">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                                        <button type="button" class="btn btn-warning btn-sm"
                                                onclick="confirmAction('Reboot Instance', 'Are you sure you want to reboot <?= htmlspecialchars($inst['InstanceId']) ?> (<?= htmlspecialchars($inst['Name']) ?>)?', 'reboot-form-<?= htmlspecialchars($inst['InstanceId']) ?>')">
                                            Reboot
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span style="color: #879596; font-size: 12px;">N/A</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
