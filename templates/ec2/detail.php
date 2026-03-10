<?php
/**
 * EC2 Instance detail page.
 *
 * Variables: $instance, $currentProfile, $currentRegion
 */
?>
<h1 class="page-title">Instance: <?= htmlspecialchars($instance['Name'] ?: $instance['InstanceId']) ?></h1>

<div style="margin-bottom: 16px;">
    <span class="badge badge-<?= htmlspecialchars($instance['State']) ?>" style="font-size: 14px; padding: 5px 14px;">
        <?= htmlspecialchars($instance['State']) ?>
    </span>

    <?php if ($instance['State'] === 'running'): ?>
        <form method="post" id="reboot-detail-form" style="display: inline; margin-left: 12px;">
            <input type="hidden" name="action" value="reboot">
            <input type="hidden" name="instance_id" value="<?= htmlspecialchars($instance['InstanceId']) ?>">
            <input type="hidden" name="profile" value="<?= htmlspecialchars($currentProfile) ?>">
            <input type="hidden" name="region" value="<?= htmlspecialchars($currentRegion) ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
            <button type="button" class="btn btn-warning btn-sm"
                    onclick="confirmAction('Reboot Instance', 'Are you sure you want to reboot <?= htmlspecialchars($instance['InstanceId']) ?>?', 'reboot-detail-form')">
                Reboot Instance
            </button>
        </form>
    <?php endif; ?>
</div>

<!-- Instance details -->
<div class="card">
    <div class="card-header">Instance Details</div>
    <div class="card-body">
        <ul class="detail-list">
            <li><span class="dl-label">Instance ID</span>     <span class="dl-value"><code><?= htmlspecialchars($instance['InstanceId']) ?></code></span></li>
            <li><span class="dl-label">Name</span>            <span class="dl-value"><?= htmlspecialchars($instance['Name'] ?: '-') ?></span></li>
            <li><span class="dl-label">Instance Type</span>   <span class="dl-value"><?= htmlspecialchars($instance['InstanceType']) ?></span></li>
            <li><span class="dl-label">State</span>           <span class="dl-value"><?= htmlspecialchars($instance['State']) ?></span></li>
            <li><span class="dl-label">AMI ID</span>          <span class="dl-value"><code><?= htmlspecialchars($instance['AmiId']) ?></code></span></li>
            <li><span class="dl-label">Architecture</span>    <span class="dl-value"><?= htmlspecialchars($instance['Architecture']) ?></span></li>
            <li><span class="dl-label">Platform</span>        <span class="dl-value"><?= htmlspecialchars($instance['Platform']) ?></span></li>
            <li><span class="dl-label">Key Name</span>        <span class="dl-value"><?= htmlspecialchars($instance['KeyName']) ?></span></li>
            <li><span class="dl-label">Launch Time</span>     <span class="dl-value"><?= htmlspecialchars($instance['LaunchTime']) ?></span></li>
        </ul>
    </div>
</div>

<!-- Networking -->
<div class="card">
    <div class="card-header">Networking</div>
    <div class="card-body">
        <ul class="detail-list">
            <li><span class="dl-label">Public IP</span>       <span class="dl-value"><?= htmlspecialchars($instance['PublicIpAddress']) ?></span></li>
            <li><span class="dl-label">Private IP</span>      <span class="dl-value"><?= htmlspecialchars($instance['PrivateIpAddress']) ?></span></li>
            <li><span class="dl-label">Public DNS</span>      <span class="dl-value"><?= htmlspecialchars($instance['PublicDnsName']) ?></span></li>
            <li><span class="dl-label">Private DNS</span>     <span class="dl-value"><?= htmlspecialchars($instance['PrivateDnsName']) ?></span></li>
            <li><span class="dl-label">VPC ID</span>          <span class="dl-value"><code><?= htmlspecialchars($instance['VpcId']) ?></code></span></li>
            <li><span class="dl-label">Subnet ID</span>       <span class="dl-value"><code><?= htmlspecialchars($instance['SubnetId']) ?></code></span></li>
            <li><span class="dl-label">Security Groups</span>
                <span class="dl-value">
                    <?php if (!empty($instance['SecurityGroups'])): ?>
                        <?= htmlspecialchars(implode(', ', $instance['SecurityGroups'])) ?>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </span>
            </li>
        </ul>
    </div>
</div>

<!-- Storage & Monitoring -->
<div class="card">
    <div class="card-header">Storage &amp; Monitoring</div>
    <div class="card-body">
        <ul class="detail-list">
            <li><span class="dl-label">Root Device Type</span> <span class="dl-value"><?= htmlspecialchars($instance['RootDeviceType']) ?></span></li>
            <li><span class="dl-label">Root Device Name</span> <span class="dl-value"><?= htmlspecialchars($instance['RootDeviceName']) ?></span></li>
            <li><span class="dl-label">EBS Optimized</span>    <span class="dl-value"><?= htmlspecialchars($instance['EbsOptimized']) ?></span></li>
            <li><span class="dl-label">Monitoring</span>       <span class="dl-value"><?= htmlspecialchars($instance['Monitoring']) ?></span></li>
            <li><span class="dl-label">Hypervisor</span>       <span class="dl-value"><?= htmlspecialchars($instance['Hypervisor']) ?></span></li>
        </ul>
    </div>
</div>

<!-- Tags -->
<?php if (!empty($instance['Tags'])): ?>
<div class="card">
    <div class="card-header">Tags (<?= count($instance['Tags']) ?>)</div>
    <div class="card-body">
        <table class="data-table meta-table">
            <thead><tr><th>Key</th><th>Value</th></tr></thead>
            <tbody>
            <?php foreach ($instance['Tags'] as $key => $value): ?>
                <tr>
                    <td><?= htmlspecialchars($key) ?></td>
                    <td><?= htmlspecialchars($value) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
