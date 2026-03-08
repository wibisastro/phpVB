<?php

namespace Gov2lib\Exceptions;

#---coded by claude (seluruh file, 28 Feb 2026)
class ConfigException extends HttpException
{
    public function __construct(
        string $message = 'Configuration error',
        ?\Throwable $previous = null
    ) {
        parent::__construct(500, $message, $previous);
    }
}
