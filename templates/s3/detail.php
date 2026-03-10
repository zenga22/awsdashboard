<?php
/**
 * S3 Object detail page.
 *
 * Variables: $bucket, $key, $objectDetails, $downloadUrl, $currentProfile, $currentRegion
 */

use AwsDashboard\Services\S3Service;
?>
<h1 class="page-title">Object Details</h1>

<!-- Path breadcrumb -->
<div class="path-breadcrumb">
    <a href="<?= $basePath ?>?page=s3-objects&profile=<?= urlencode($currentProfile) ?>&region=<?= urlencode($currentRegion) ?>&bucket=<?= urlencode($bucket) ?>">
        <?= htmlspecialchars($bucket) ?>
    </a>
    <?php
    $keyParts = explode('/', $key);
    $fileName = array_pop($keyParts);
    $accumulated = '';
    foreach ($keyParts as $part) {
        $accumulated .= $part . '/';
        echo '<span class="sep">/</span>';
        echo '<a href="' . $basePath . '?page=s3-objects&profile=' . urlencode($currentProfile) .
             '&region=' . urlencode($currentRegion) .
             '&bucket=' . urlencode($bucket) .
             '&prefix=' . urlencode($accumulated) . '">' .
             htmlspecialchars($part) . '</a>';
    }
    ?>
    <span class="sep">/</span>
    <strong><?= htmlspecialchars($fileName) ?></strong>
</div>

<!-- Actions -->
<div style="margin-bottom: 20px; display: flex; gap: 10px;">
    <?php if ($downloadUrl): ?>
        <a href="<?= htmlspecialchars($downloadUrl) ?>" class="btn btn-primary" target="_blank" rel="noopener">
            &#8615; Download
        </a>
    <?php endif; ?>

    <form method="post" id="delete-object-form" style="display: inline;">
        <input type="hidden" name="action" value="delete_object">
        <input type="hidden" name="profile" value="<?= htmlspecialchars($currentProfile) ?>">
        <input type="hidden" name="region" value="<?= htmlspecialchars($currentRegion) ?>">
        <input type="hidden" name="bucket" value="<?= htmlspecialchars($bucket) ?>">
        <input type="hidden" name="key" value="<?= htmlspecialchars($key) ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
        <button type="button" class="btn btn-danger"
                onclick="confirmAction('Delete Object', 'Are you sure you want to delete <?= htmlspecialchars(addslashes($fileName)) ?>? This action cannot be undone.', 'delete-object-form')">
            &#10005; Delete
        </button>
    </form>

    <a href="<?= $basePath ?>?page=s3-objects&profile=<?= urlencode($currentProfile) ?>&region=<?= urlencode($currentRegion) ?>&bucket=<?= urlencode($bucket) ?>&prefix=<?= urlencode($accumulated ?? '') ?>"
       class="btn btn-outline">&#8592; Back to Folder</a>
</div>

<?php if ($objectDetails === null): ?>
    <div class="alert alert-danger">Unable to retrieve object details. The object may not exist or you may lack permissions.</div>
<?php else: ?>

<!-- Object properties -->
<div class="card">
    <div class="card-header">Object Properties</div>
    <div class="card-body">
        <ul class="detail-list">
            <li><span class="dl-label">Key</span>             <span class="dl-value"><code><?= htmlspecialchars($key) ?></code></span></li>
            <li><span class="dl-label">Bucket</span>          <span class="dl-value"><?= htmlspecialchars($bucket) ?></span></li>
            <li><span class="dl-label">Region</span>          <span class="dl-value"><?= htmlspecialchars($currentRegion) ?></span></li>
            <li><span class="dl-label">Size</span>            <span class="dl-value"><?= S3Service::formatSize($objectDetails['ContentLength']) ?> (<?= number_format($objectDetails['ContentLength']) ?> bytes)</span></li>
            <li><span class="dl-label">Content Type</span>    <span class="dl-value"><?= htmlspecialchars($objectDetails['ContentType']) ?></span></li>
            <li><span class="dl-label">Last Modified</span>   <span class="dl-value"><?= htmlspecialchars($objectDetails['LastModified']) ?></span></li>
            <li><span class="dl-label">ETag</span>            <span class="dl-value"><code><?= htmlspecialchars($objectDetails['ETag']) ?></code></span></li>
            <li><span class="dl-label">Storage Class</span>   <span class="dl-value"><?= htmlspecialchars($objectDetails['StorageClass']) ?></span></li>
            <li><span class="dl-label">Server-Side Encryption</span> <span class="dl-value"><?= htmlspecialchars($objectDetails['ServerSideEncryption']) ?></span></li>
            <li><span class="dl-label">Version ID</span>      <span class="dl-value"><?= htmlspecialchars($objectDetails['VersionId']) ?></span></li>
            <li><span class="dl-label">Cache Control</span>   <span class="dl-value"><?= htmlspecialchars($objectDetails['CacheControl']) ?></span></li>
            <li><span class="dl-label">Content Encoding</span><span class="dl-value"><?= htmlspecialchars($objectDetails['ContentEncoding']) ?></span></li>
            <li><span class="dl-label">Content Language</span><span class="dl-value"><?= htmlspecialchars($objectDetails['ContentLanguage']) ?></span></li>
            <li><span class="dl-label">Expires</span>         <span class="dl-value"><?= htmlspecialchars($objectDetails['Expires']) ?></span></li>
        </ul>
    </div>
</div>

<!-- User-defined metadata -->
<div class="card">
    <div class="card-header">User-Defined Metadata</div>
    <div class="card-body">
        <?php if (empty($objectDetails['Metadata'])): ?>
            <p style="color: #687078;">No user-defined metadata on this object.</p>
        <?php else: ?>
            <table class="data-table meta-table">
                <thead><tr><th>Key</th><th>Value</th></tr></thead>
                <tbody>
                <?php foreach ($objectDetails['Metadata'] as $mk => $mv): ?>
                    <tr>
                        <td><?= htmlspecialchars($mk) ?></td>
                        <td><?= htmlspecialchars($mv) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
