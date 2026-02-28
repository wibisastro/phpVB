<?php

namespace Gov2lib\Exceptions;

class HttpException extends \RuntimeException
{
    public function __construct(
        private readonly int $statusCode,
        string $message = '',
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Convert to legacy "Code:Message" format for backward compatibility.
     */
    public function toLegacyFormat(): string
    {
        $className = (new \ReflectionClass($this))->getShortName();
        $code = str_replace('Exception', '', $className);
        return "{$code}:{$this->getMessage()}";
    }
}
