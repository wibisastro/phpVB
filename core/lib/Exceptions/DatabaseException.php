<?php

namespace Gov2lib\Exceptions;

class DatabaseException extends HttpException
{
    public function __construct(
        string $message = 'Database error',
        ?\Throwable $previous = null
    ) {
        parent::__construct(500, $message, $previous);
    }
}
