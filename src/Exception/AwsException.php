<?php

namespace AwsDashboard\Exception;

use Aws\Exception\AwsException as SdkException;

/**
 * Represents a failed AWS SDK call with structured error details.
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

    /**
     * Create from an AWS SDK exception with full context.
     */
    public static function fromSdkException(
        string $service,
        string $command,
        SdkException $e
    ): self {
        $errorCode = $e->getAwsErrorCode() ?? 'UnknownError';
        $message   = $e->getAwsErrorMessage() ?? $e->getMessage();
        $rawOutput = $e->getMessage();

        return new self($service, $command, $errorCode, $message, $rawOutput, $e);
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

    public function isAccessDenied(): bool
    {
        return in_array($this->errorCode, ['AccessDenied', 'AccessDeniedException', 'UnauthorizedAccess'], true);
    }

    public function isNotFound(): bool
    {
        return in_array($this->errorCode, ['404', 'NoSuchBucket', 'NoSuchKey', 'InvalidInstanceID.NotFound', 'NotFound'], true);
    }

    public function isCredentialError(): bool
    {
        return in_array($this->errorCode, ['NoCredentialsError', 'ExpiredTokenException', 'ProfileNotFound', 'CredentialsException'], true);
    }

    public function isConnectionError(): bool
    {
        return in_array($this->errorCode, ['EndpointConnectionError', 'NetworkingException'], true);
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
