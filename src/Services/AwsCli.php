<?php

namespace AwsDashboard\Services;

/**
 * Thin wrapper around the AWS CLI.
 * All AWS interactions go through this class so that profile and region
 * handling is consistent, and all output is JSON-decoded in one place.
 */
class AwsCli
{
    /**
     * Execute an AWS CLI command and return decoded JSON output.
     *
     * @param string $service   e.g. "ec2", "s3api"
     * @param string $command   e.g. "describe-instances"
     * @param string $profile   AWS profile name
     * @param string $region    AWS region code
     * @param array  $extra     Additional CLI arguments
     * @return array|null       Decoded JSON or null on failure
     */
    public static function run(
        string $service,
        string $command,
        string $profile,
        string $region,
        array $extra = []
    ): ?array {
        $parts = [
            'aws',
            escapeshellarg($service),
            escapeshellarg($command),
            '--profile', escapeshellarg($profile),
            '--region',  escapeshellarg($region),
            '--output',  'json',
        ];

        foreach ($extra as $key => $value) {
            if (is_int($key)) {
                // Positional flag like '--no-paginate'
                $parts[] = $value;
            } else {
                $parts[] = $key;
                $parts[] = escapeshellarg($value);
            }
        }

        $cmd = implode(' ', $parts) . ' 2>&1';
        $output = shell_exec($cmd);

        if ($output === null) {
            return null;
        }

        $decoded = json_decode($output, true);
        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Execute a raw AWS CLI command string and return raw output.
     */
    public static function rawCommand(string $cmd): ?string
    {
        return shell_exec($cmd . ' 2>&1');
    }

    /**
     * Check whether the AWS CLI is available on this system.
     */
    public static function isAvailable(): bool
    {
        $output = shell_exec('aws --version 2>&1');
        return $output !== null && str_contains($output, 'aws-cli');
    }
}
