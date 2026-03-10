<?php

namespace AwsDashboard\Exception;

/**
 * Represents a failed AWS CLI call with structured error details.
 */
class AwsException extends \RuntimeException
{
    private string $awsService;
    private string $awsCommand;
    private string $errorCode;
    private string $rawOutput;

    public function __construct(
        string $awsService,
        string $awsCommand,
        string $errorCode,
        string $message,
        string $rawOutput = '',
        ?\Throwable $previous = null
    ) {
        $this->awsService = $awsService;
        $this->awsCommand = $awsCommand;
        $this->errorCode  = $errorCode;
        $this->rawOutput  = $rawOutput;

        $fullMessage = sprintf('[%s] %s %s: %s', $errorCode, $awsService, $awsCommand, $message);
        parent::__construct($fullMessage, 0, $previous);
    }

    public function getAwsService(): string
    {
        return $this->awsService;
    }

    public function getAwsCommand(): string
    {
        return $this->awsCommand;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getRawOutput(): string
    {
        return $this->rawOutput;
    }

    /**
     * Parse AWS CLI error output into an AwsException.
     */
    public static function fromCliOutput(
        string $service,
        string $command,
        string $output
    ): self {
        $errorCode = 'UnknownError';
        $message   = trim($output);

        // AWS CLI errors follow: "An error occurred (ErrorCode) when calling ..."
        if (preg_match('/An error occurred \(([^)]+)\) when calling the (\S+) operation: (.+)/s', $output, $m)) {
            $errorCode = $m[1];
            $message   = trim($m[3]);
        } elseif (preg_match('/Could not connect to the endpoint URL/', $output)) {
            $errorCode = 'EndpointConnectionError';
        } elseif (preg_match('/Unable to locate credentials/', $output)) {
            $errorCode = 'NoCredentialsError';
        } elseif (preg_match('/The config profile \((\S+)\) could not be found/', $output, $m)) {
            $errorCode = 'ProfileNotFound';
            $message   = "Profile '{$m[1]}' not found.";
        }

        return new self($service, $command, $errorCode, $message, $output);
    }

    public function isAccessDenied(): bool
    {
        return in_array($this->errorCode, ['AccessDenied', 'AccessDeniedException', 'UnauthorizedAccess'], true);
    }

    public function isNotFound(): bool
    {
        return in_array($this->errorCode, ['404', 'NoSuchBucket', 'NoSuchKey', 'InvalidInstanceID.NotFound'], true);
    }

    public function isCredentialError(): bool
    {
        return in_array($this->errorCode, ['NoCredentialsError', 'ExpiredTokenException', 'ProfileNotFound'], true);
    }

    public function isConnectionError(): bool
    {
        return $this->errorCode === 'EndpointConnectionError';
    }

    /**
     * Return a user-friendly summary suitable for flash messages.
     */
    public function getUserMessage(): string
    {
        if ($this->isCredentialError()) {
            return 'AWS credentials are missing or expired. Check your profile configuration.';
        }
        if ($this->isAccessDenied()) {
            return 'Access denied. Your AWS credentials lack permission for this action.';
        }
        if ($this->isConnectionError()) {
            return 'Could not connect to AWS. Check your network and region settings.';
        }
        if ($this->isNotFound()) {
            return 'The requested AWS resource was not found.';
        }

        return 'AWS error: ' . $this->errorCode;
    }
}
