<?php

/**
 * AWS Dashboard – Main Entry Point / Router
 *
 * All requests are routed through this file.
 * Run with: php -S localhost:8080 -t public/
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use AwsDashboard\Config\ProfileManager;
use AwsDashboard\Services\AwsCli;
use AwsDashboard\Services\Ec2Service;
use AwsDashboard\Services\S3Service;

// ---------------------------------------------------------------------------
// Session & CSRF
// ---------------------------------------------------------------------------
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

function verifyCsrf(): bool
{
    return isset($_POST['csrf_token'], $_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

// ---------------------------------------------------------------------------
// Config
// ---------------------------------------------------------------------------
$profileManager = new ProfileManager();
$profiles       = $profileManager->getProfiles();
$allRegions     = $profileManager->getAllRegions();

// Determine current profile and region from query string
$currentProfile = $_GET['profile'] ?? $_POST['profile'] ?? 'default';
if (!$profileManager->validateProfile($currentProfile)) {
    $currentProfile = $profiles[0]['name'] ?? 'default';
}

$regions = $profileManager->getRegionsForProfile($currentProfile);
$currentRegion = $_GET['region'] ?? $_POST['region'] ?? ($regions[0] ?? 'us-east-1');
if (!in_array($currentRegion, $regions, true)) {
    $currentRegion = $regions[0] ?? 'us-east-1';
}

// ---------------------------------------------------------------------------
// Services
// ---------------------------------------------------------------------------
$ec2 = new Ec2Service();
$s3  = new S3Service();

// ---------------------------------------------------------------------------
// Flash messages
// ---------------------------------------------------------------------------
$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError   = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// ---------------------------------------------------------------------------
// Handle POST actions (reboot, delete, etc.)
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!verifyCsrf()) {
        $_SESSION['flash_error'] = 'Invalid CSRF token. Please try again.';
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    switch ($action) {
        case 'reboot':
            $instanceId = $_POST['instance_id'] ?? '';
            $result = $ec2->rebootInstance($currentProfile, $currentRegion, $instanceId);
            if ($result['success']) {
                $_SESSION['flash_success'] = $result['message'];
            } else {
                $_SESSION['flash_error'] = $result['message'];
            }
            // Redirect back
            $returnPage = isset($_POST['return_page']) ? $_POST['return_page'] : 'ec2-instances';
            header('Location: /?page=' . urlencode($returnPage) .
                   '&profile=' . urlencode($currentProfile) .
                   '&region=' . urlencode($currentRegion));
            exit;

        case 'delete_object':
            $bucket = $_POST['bucket'] ?? '';
            $key    = $_POST['key'] ?? '';
            $result = $s3->deleteObject($currentProfile, $currentRegion, $bucket, $key);
            if ($result['success']) {
                $_SESSION['flash_success'] = $result['message'];
            } else {
                $_SESSION['flash_error'] = $result['message'];
            }
            // Redirect to parent folder
            $parentPrefix = dirname($key);
            $parentPrefix = ($parentPrefix === '.' || $parentPrefix === '') ? '' : $parentPrefix . '/';
            header('Location: /?page=s3-objects' .
                   '&profile=' . urlencode($currentProfile) .
                   '&region=' . urlencode($currentRegion) .
                   '&bucket=' . urlencode($bucket) .
                   '&prefix=' . urlencode($parentPrefix));
            exit;
    }

    // Unknown action – redirect home
    header('Location: /?page=dashboard&profile=' . urlencode($currentProfile) . '&region=' . urlencode($currentRegion));
    exit;
}

// ---------------------------------------------------------------------------
// Routing (GET)
// ---------------------------------------------------------------------------
$page = $_GET['page'] ?? 'dashboard';

// Common template vars
$activeNav   = $page;
$breadcrumbs = [];
$pageTitle   = 'Dashboard';

// Prepare page-specific data and render
switch ($page) {

    // -----------------------------------------------------------------------
    // Dashboard
    // -----------------------------------------------------------------------
    case 'dashboard':
        $pageTitle   = 'Dashboard';
        $activeNav   = 'dashboard';
        $breadcrumbs = [['label' => 'Dashboard', 'url' => null]];

        $ec2Summary   = $ec2->getInstanceSummary($currentProfile, $currentRegion);
        $s3Buckets    = $s3->getBuckets($currentProfile, $currentRegion);
        $s3BucketCount = count($s3Buckets);

        $content = function () use (
            $ec2Summary, $s3BucketCount, $currentProfile, $currentRegion, $regions, $allRegions
        ) {
            require __DIR__ . '/../templates/dashboard/index.php';
        };
        break;

    // -----------------------------------------------------------------------
    // EC2 Instances
    // -----------------------------------------------------------------------
    case 'ec2-instances':
        $pageTitle   = 'EC2 Instances';
        $activeNav   = 'ec2-instances';
        $breadcrumbs = [
            ['label' => 'EC2', 'url' => null],
            ['label' => 'Instances', 'url' => null],
        ];

        $instances = $ec2->getInstances($currentProfile, $currentRegion);
        $summary   = $ec2->getInstanceSummary($currentProfile, $currentRegion);

        $content = function () use (
            $instances, $summary, $currentProfile, $currentRegion, $regions, $allRegions, $csrfToken
        ) {
            require __DIR__ . '/../templates/ec2/instances.php';
        };
        break;

    // -----------------------------------------------------------------------
    // EC2 Instance Detail
    // -----------------------------------------------------------------------
    case 'ec2-detail':
        $instanceId = $_GET['instance'] ?? '';
        $instance   = $ec2->getInstance($currentProfile, $currentRegion, $instanceId);

        if (!$instance) {
            $_SESSION['flash_error'] = 'Instance not found.';
            header('Location: /?page=ec2-instances&profile=' . urlencode($currentProfile) . '&region=' . urlencode($currentRegion));
            exit;
        }

        $pageTitle   = 'Instance: ' . ($instance['Name'] ?: $instance['InstanceId']);
        $activeNav   = 'ec2-instances';
        $breadcrumbs = [
            ['label' => 'EC2',       'url' => '/?page=ec2-instances&profile=' . urlencode($currentProfile) . '&region=' . urlencode($currentRegion)],
            ['label' => 'Instances', 'url' => '/?page=ec2-instances&profile=' . urlencode($currentProfile) . '&region=' . urlencode($currentRegion)],
            ['label' => $instance['InstanceId'], 'url' => null],
        ];

        $content = function () use ($instance, $currentProfile, $currentRegion, $csrfToken) {
            require __DIR__ . '/../templates/ec2/detail.php';
        };
        break;

    // -----------------------------------------------------------------------
    // EC2 Reserved Instances
    // -----------------------------------------------------------------------
    case 'ec2-reserved':
        $pageTitle   = 'Reserved Instances';
        $activeNav   = 'ec2-reserved';
        $breadcrumbs = [
            ['label' => 'EC2', 'url' => null],
            ['label' => 'Reserved Instances', 'url' => null],
        ];

        $reservedInstances = $ec2->getReservedInstances($currentProfile, $currentRegion);

        $content = function () use (
            $reservedInstances, $currentProfile, $currentRegion, $regions, $allRegions
        ) {
            require __DIR__ . '/../templates/ec2/reserved.php';
        };
        break;

    // -----------------------------------------------------------------------
    // S3 Buckets
    // -----------------------------------------------------------------------
    case 's3-buckets':
        $pageTitle    = 'S3 Buckets';
        $activeNav    = 's3-buckets';
        $regionFilter = $_GET['filter'] ?? 'region';
        $breadcrumbs  = [
            ['label' => 'S3', 'url' => null],
            ['label' => 'Buckets', 'url' => null],
        ];

        if ($regionFilter === 'all') {
            $buckets = $s3->getBuckets($currentProfile);
        } else {
            $buckets = $s3->getBuckets($currentProfile, $currentRegion);
        }

        $content = function () use (
            $buckets, $currentProfile, $currentRegion, $regions, $allRegions, $regionFilter
        ) {
            require __DIR__ . '/../templates/s3/buckets.php';
        };
        break;

    // -----------------------------------------------------------------------
    // S3 Bucket Browser (objects)
    // -----------------------------------------------------------------------
    case 's3-objects':
        $bucket = $_GET['bucket'] ?? '';
        $prefix = $_GET['prefix'] ?? '';
        $token  = $_GET['token'] ?? null;

        $pageTitle = 'S3: ' . $bucket;
        $activeNav = 's3-buckets';
        $breadcrumbs = [
            ['label' => 'S3',      'url' => '/?page=s3-buckets&profile=' . urlencode($currentProfile) . '&region=' . urlencode($currentRegion)],
            ['label' => 'Buckets', 'url' => '/?page=s3-buckets&profile=' . urlencode($currentProfile) . '&region=' . urlencode($currentRegion)],
            ['label' => $bucket,   'url' => null],
        ];

        $objectData    = $s3->listObjects($currentProfile, $currentRegion, $bucket, $prefix, $token);
        $bucketSummary = $s3->getBucketSizeSummary($currentProfile, $currentRegion, $bucket);

        $content = function () use (
            $bucket, $prefix, $objectData, $bucketSummary, $currentProfile, $currentRegion
        ) {
            require __DIR__ . '/../templates/s3/objects.php';
        };
        break;

    // -----------------------------------------------------------------------
    // S3 Object Detail
    // -----------------------------------------------------------------------
    case 's3-detail':
        $bucket = $_GET['bucket'] ?? '';
        $key    = $_GET['key'] ?? '';

        $pageTitle = 'Object: ' . basename($key);
        $activeNav = 's3-buckets';
        $breadcrumbs = [
            ['label' => 'S3',      'url' => '/?page=s3-buckets&profile=' . urlencode($currentProfile) . '&region=' . urlencode($currentRegion)],
            ['label' => $bucket,   'url' => '/?page=s3-objects&profile=' . urlencode($currentProfile) . '&region=' . urlencode($currentRegion) . '&bucket=' . urlencode($bucket)],
            ['label' => basename($key), 'url' => null],
        ];

        $objectDetails = $s3->getObjectDetails($currentProfile, $currentRegion, $bucket, $key);
        $downloadUrl   = $s3->getDownloadUrl($currentProfile, $currentRegion, $bucket, $key);

        $content = function () use (
            $bucket, $key, $objectDetails, $downloadUrl, $currentProfile, $currentRegion, $csrfToken
        ) {
            require __DIR__ . '/../templates/s3/detail.php';
        };
        break;

    // -----------------------------------------------------------------------
    // 404
    // -----------------------------------------------------------------------
    default:
        $pageTitle   = 'Not Found';
        $breadcrumbs = [['label' => '404', 'url' => null]];
        $content = function () {
            echo '<div class="empty-state"><div class="icon">&#9888;</div>';
            echo '<p>Page not found.</p>';
            echo '<a href="/?page=dashboard" class="btn btn-primary" style="margin-top:16px;">Go to Dashboard</a></div>';
        };
        break;
}

// ---------------------------------------------------------------------------
// Render
// ---------------------------------------------------------------------------
require __DIR__ . '/../templates/layouts/base.php';
