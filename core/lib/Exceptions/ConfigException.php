<?php

namespace Gov2lib\Exceptions;

class ConfigException extends HttpException
{
    public function __construct(
        string $message = 'Configuration error',
        ?\Throwable $previous = null
    ) {
        parent::__construct(500, $message, $previous);
    }
}
