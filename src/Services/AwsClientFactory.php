<?php

namespace AwsDashboard\Services;

use Aws\Ec2\Ec2Client;
use Aws\S3\S3Client;

/**
 * Factory for creating AWS SDK clients with consistent profile/region handling.
 */
class AwsClientFactory
{
    /**
     * Create an EC2 client for the given profile and region.
     */
    public static function ec2(string $profile, string $region): Ec2Client
    {
        return new Ec2Client([
            'version' => 'latest',
            'region'  => $region,
            'profile' => $profile,
        ]);
    }

    /**
     * Create an S3 client for the given profile and region.
     */
    public static function s3(string $profile, string $region): S3Client
    {
        return new S3Client([
            'version' => 'latest',
            'region'  => $region,
            'profile' => $profile,
        ]);
    }
}
