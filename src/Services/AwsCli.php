<?php

namespace AwsDashboard\Services;

use AwsDashboard\Exception\AwsException;

/**
 * Thin wrapper around the AWS CLI.
 *
 * All AWS interactions go through this class so that profile and region
 * handling is consistent. On failure, an AwsException is thrown with
 * structured error details parsed from the CLI output.
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
     * @return array            Decoded JSON
     *
     * @throws AwsException on any CLI failure
     */
    public static function run(
        string $service,
        string $command,
        string $profile,
        string $region,
        array $extra = []
    ): array {
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
            throw new AwsException(
                $service,
                $command,
                'ExecutionError',
                'AWS CLI command returned no output (process may have failed to start).'
            );
        }

        $trimmed = trim($output);

        // Detect CLI error messages before attempting JSON decode
        if (str_contains($trimmed, 'An error occurred')
            || str_contains($trimmed, 'Could not connect')
            || str_contains($trimmed, 'Unable to locate credentials')
            || str_contains($trimmed, 'config profile')
        ) {
            throw AwsException::fromCliOutput($service, $command, $trimmed);
        }

        $decoded = json_decode($trimmed, true);

        if (!is_array($decoded)) {
            // Some commands return empty output on success (e.g. reboot-instances)
            if ($trimmed === '') {
                return [];
            }
            throw new AwsException(
                $service,
                $command,
                'InvalidResponse',
                'AWS CLI returned non-JSON output.',
                $trimmed
            );
        }

        return $decoded;
    }

    /**
     * Execute a raw AWS CLI command string and return raw output.
     *
     * @throws AwsException on failure
     */
    public static function rawCommand(string $cmd): string
    {
        $output = shell_exec($cmd . ' 2>&1');

        if ($output === null) {
            throw new AwsException('cli', 'raw', 'ExecutionError', 'Command returned no output.');
        }

        $trimmed = trim($output);

        if (str_contains($trimmed, 'An error occurred')
            || str_contains($trimmed, 'Could not connect')
            || str_contains($trimmed, 'Unable to locate credentials')
        ) {
            throw AwsException::fromCliOutput('cli', 'raw', $trimmed);
        }

        return $trimmed;
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
