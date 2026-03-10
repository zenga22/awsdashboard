<?php
/**
 * EC2 Reserved Instances page.
 *
 * Variables: $reservedInstances, $currentProfile, $currentRegion, $regions, $allRegions
 */
?>
<h1 class="page-title">Reserved Instances</h1>

<!-- Region tabs -->
<div class="region-tabs">
    <?php foreach ($regions as $r): ?>
        <a href="<?= $basePath ?>?page=ec2-reserved&profile=<?= urlencode($currentProfile) ?>&region=<?= urlencode($r) ?>"
           class="<?= $r === $currentRegion ? 'active' : '' ?>">
            <?= htmlspecialchars($r) ?>
        </a>
    <?php endforeach; ?>
</div>

<!-- Summary -->
<?php
$activeCount = 0;
$retiredCount = 0;
$totalCount = count($reservedInstances);
foreach ($reservedInstances as $ri) {
    if ($ri['State'] === 'active') $activeCount++;
    if ($ri['State'] === 'retired') $retiredCount++;
}
?>
<div class="stats-grid">
    <div class="stat-card accent-blue">
        <span class="stat-label">Total Reserved</span>
        <span class="stat-value"><?= $totalCount ?></span>
    </div>
    <div class="stat-card accent-green">
        <span class="stat-label">Active</span>
        <span class="stat-value"><?= $activeCount ?></span>
    </div>
    <div class="stat-card accent-red">
        <span class="stat-label">Retired</span>
        <span class="stat-value"><?= $retiredCount ?></span>
    </div>
</div>

<!-- Reserved instances table -->
<div class="card">
    <div class="card-header">
        Reserved Instances in <?= htmlspecialchars($currentRegion) ?>
    </div>
    <div class="card-body">
        <?php if (empty($reservedInstances)): ?>
            <div class="empty-state">
                <div class="icon">&#9729;</div>
                <p>No reserved instances found in <?= htmlspecialchars($currentRegion) ?>.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                    <tr>
                        <th data-sort="string">RI ID</th>
                        <th data-sort="string">Instance Type</th>
                        <th data-sort="string">State</th>
                        <th data-sort="number">Count</th>
                        <th data-sort="string">Offering Type</th>
                        <th data-sort="string">Offering Class</th>
                        <th data-sort="string">Product</th>
                        <th data-sort="string">Scope</th>
                        <th data-sort="date">Start</th>
                        <th data-sort="date">End</th>
                        <th data-sort="number">Fixed Price</th>
                        <th data-sort="number">Usage Price</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($reservedInstances as $ri): ?>
                        <tr>
                            <td><code style="font-size: 11px;"><?= htmlspecialchars($ri['ReservedInstancesId']) ?></code></td>
                            <td><?= htmlspecialchars($ri['InstanceType']) ?></td>
                            <td>
                                <span class="badge badge-<?= htmlspecialchars($ri['State']) ?>">
                                    <?= htmlspecialchars($ri['State']) ?>
                                </span>
                            </td>
                            <td><?= (int) $ri['InstanceCount'] ?></td>
                            <td><?= htmlspecialchars($ri['OfferingType']) ?></td>
                            <td><?= htmlspecialchars($ri['OfferingClass']) ?></td>
                            <td><?= htmlspecialchars($ri['ProductDescription']) ?></td>
                            <td><?= htmlspecialchars($ri['Scope']) ?></td>
                            <td><?= htmlspecialchars($ri['Start']) ?></td>
                            <td><?= htmlspecialchars($ri['End']) ?></td>
                            <td>$<?= number_format((float) $ri['FixedPrice'], 2) ?> <?= htmlspecialchars($ri['CurrencyCode']) ?></td>
                            <td>$<?= number_format((float) $ri['UsagePrice'], 4) ?>/hr</td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
