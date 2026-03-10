<?php

namespace AwsDashboard\Services;

use AwsDashboard\Exception\AwsException;

class S3Service
{
    /**
     * List S3 buckets. The AWS CLI returns all buckets (S3 is global),
     * but we filter by region via get-bucket-location if a region filter is provided.
     *
     * @throws AwsException
     */
    public function getBuckets(string $profile, ?string $regionFilter = null): array
    {
        $result = AwsCli::run('s3api', 'list-buckets', $profile, 'us-east-1');

        if (!isset($result['Buckets'])) {
            return [];
        }

        $buckets = [];
        foreach ($result['Buckets'] as $bucket) {
            $name = $bucket['Name'];

            try {
                $bucketRegion = $this->getBucketRegion($profile, $name);
            } catch (AwsException $e) {
                // Skip buckets we cannot inspect (permissions, deleted, etc.)
                if ($e->isAccessDenied() || $e->isNotFound()) {
                    continue;
                }
                throw $e;
            }

            if ($regionFilter !== null && $bucketRegion !== $regionFilter) {
                continue;
            }

            $buckets[] = [
                'Name'         => $name,
                'CreationDate' => $bucket['CreationDate'] ?? '',
                'Region'       => $bucketRegion,
            ];
        }

        usort($buckets, fn($a, $b) => strcasecmp($a['Name'], $b['Name']));
        return $buckets;
    }

    /**
     * Determine a bucket's region.
     *
     * @throws AwsException
     * @throws \InvalidArgumentException
     */
    public function getBucketRegion(string $profile, string $bucketName): string
    {
        if (!$this->isValidBucketName($bucketName)) {
            throw new \InvalidArgumentException("Invalid bucket name: {$bucketName}");
        }

        $result = AwsCli::run('s3api', 'get-bucket-location', $profile, 'us-east-1', [
            '--bucket' => $bucketName,
        ]);

        // LocationConstraint is null for us-east-1
        $location = $result['LocationConstraint'] ?? null;
        return $location ?: 'us-east-1';
    }

    /**
     * List objects in a bucket (optionally with prefix for folder drilling).
     *
     * @throws AwsException
     * @throws \InvalidArgumentException
     */
    public function listObjects(
        string $profile,
        string $region,
        string $bucket,
        string $prefix = '',
        ?string $continuationToken = null
    ): array {
        if (!$this->isValidBucketName($bucket)) {
            throw new \InvalidArgumentException("Invalid bucket name: {$bucket}");
        }

        $extra = [
            '--bucket'    => $bucket,
            '--delimiter' => '/',
            '--max-items' => '1000',
        ];

        if ($prefix !== '') {
            $extra['--prefix'] = $prefix;
        }
        if ($continuationToken !== null) {
            $extra['--starting-token'] = $continuationToken;
        }

        $result = AwsCli::run('s3api', 'list-objects-v2', $profile, $region, $extra);

        $objects = [];
        $totalSize = 0;
        $objectCount = 0;

        if (isset($result['Contents'])) {
            foreach ($result['Contents'] as $obj) {
                $key = $obj['Key'];
                // Skip the prefix itself (folder marker)
                if ($key === $prefix) {
                    continue;
                }
                $size = $obj['Size'] ?? 0;
                $totalSize += $size;
                $objectCount++;
                $objects[] = [
                    'Key'          => $key,
                    'DisplayName'  => basename($key),
                    'LastModified' => $obj['LastModified'] ?? '',
                    'Size'         => $size,
                    'StorageClass' => $obj['StorageClass'] ?? 'STANDARD',
                    'ETag'         => $obj['ETag'] ?? '',
                ];
            }
        }

        $prefixes = [];
        if (isset($result['CommonPrefixes'])) {
            foreach ($result['CommonPrefixes'] as $cp) {
                $prefixes[] = $cp['Prefix'];
            }
        }
        sort($prefixes);

        $nextToken = $result['NextToken'] ?? null;

        return [
            'objects'     => $objects,
            'prefixes'    => $prefixes,
            'nextToken'   => $nextToken,
            'totalSize'   => $totalSize,
            'objectCount' => $objectCount,
        ];
    }

    /**
     * Get detailed object metadata.
     *
     * @throws AwsException
     * @throws \InvalidArgumentException
     */
    public function getObjectDetails(string $profile, string $region, string $bucket, string $key): ?array
    {
        if (!$this->isValidBucketName($bucket)) {
            throw new \InvalidArgumentException("Invalid bucket name: {$bucket}");
        }

        try {
            $result = AwsCli::run('s3api', 'head-object', $profile, $region, [
                '--bucket' => $bucket,
                '--key'    => $key,
            ]);
        } catch (AwsException $e) {
            if ($e->isNotFound()) {
                return null;
            }
            throw $e;
        }

        return [
            'ContentType'     => $result['ContentType'] ?? '-',
            'ContentLength'   => $result['ContentLength'] ?? 0,
            'LastModified'    => $result['LastModified'] ?? '-',
            'ETag'            => $result['ETag'] ?? '-',
            'StorageClass'    => $result['StorageClass'] ?? 'STANDARD',
            'ServerSideEncryption' => $result['ServerSideEncryption'] ?? '-',
            'VersionId'       => $result['VersionId'] ?? '-',
            'CacheControl'    => $result['CacheControl'] ?? '-',
            'ContentEncoding' => $result['ContentEncoding'] ?? '-',
            'ContentLanguage' => $result['ContentLanguage'] ?? '-',
            'Expires'         => $result['Expires'] ?? '-',
            'Metadata'        => $result['Metadata'] ?? [],
        ];
    }

    /**
     * Generate a pre-signed download URL for an object (valid 1 hour).
     *
     * @throws AwsException
     * @throws \InvalidArgumentException
     */
    public function getDownloadUrl(string $profile, string $region, string $bucket, string $key): string
    {
        if (!$this->isValidBucketName($bucket)) {
            throw new \InvalidArgumentException("Invalid bucket name: {$bucket}");
        }

        $s3Uri = 's3://' . $bucket . '/' . $key;
        $cmd = sprintf(
            'aws s3 presign %s --profile %s --region %s --expires-in 3600',
            escapeshellarg($s3Uri),
            escapeshellarg($profile),
            escapeshellarg($region)
        );

        $output = AwsCli::rawCommand($cmd);

        if (!str_starts_with($output, 'http')) {
            throw new AwsException('s3', 'presign', 'PresignError', 'Failed to generate pre-signed URL.', $output);
        }

        return $output;
    }

    /**
     * Delete an object from S3.
     *
     * @return array{success: bool, message: string}
     */
    public function deleteObject(string $profile, string $region, string $bucket, string $key): array
    {
        if (!$this->isValidBucketName($bucket)) {
            return ['success' => false, 'message' => 'Invalid bucket name.'];
        }

        try {
            AwsCli::run('s3api', 'delete-object', $profile, $region, [
                '--bucket' => $bucket,
                '--key'    => $key,
            ]);
        } catch (AwsException $e) {
            return ['success' => false, 'message' => $e->getUserMessage()];
        }

        // Verify the delete worked
        try {
            $check = $this->getObjectDetails($profile, $region, $bucket, $key);
            if ($check !== null) {
                return ['success' => false, 'message' => 'Object still exists after delete. Check bucket versioning.'];
            }
        } catch (AwsException) {
            // head-object failing means the object is gone — success
        }

        return ['success' => true, 'message' => "Object '{$key}' deleted successfully."];
    }

    /**
     * Get bucket-level space usage summary.
     *
     * @throws AwsException
     * @throws \InvalidArgumentException
     */
    public function getBucketSizeSummary(string $profile, string $region, string $bucket): array
    {
        if (!$this->isValidBucketName($bucket)) {
            throw new \InvalidArgumentException("Invalid bucket name: {$bucket}");
        }

        $extra = [
            '--bucket' => $bucket,
            '--query'  => '[sum(Contents[].Size), length(Contents[])]',
        ];

        $result = AwsCli::run('s3api', 'list-objects-v2', $profile, $region, $extra);

        if (is_array($result) && count($result) === 2) {
            return [
                'totalSize'   => $result[0] ?? 0,
                'objectCount' => $result[1] ?? 0,
            ];
        }

        return ['totalSize' => 0, 'objectCount' => 0];
    }

    /**
     * Format a byte size into human-readable form.
     */
    public static function formatSize(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $i = (int) floor(log($bytes, 1024));
        $i = min($i, count($units) - 1);
        return round($bytes / (1024 ** $i), 2) . ' ' . $units[$i];
    }

    private function isValidBucketName(string $name): bool
    {
        return (bool) preg_match('/^[a-z0-9][a-z0-9.\-]{1,61}[a-z0-9]$/', $name);
    }
}
