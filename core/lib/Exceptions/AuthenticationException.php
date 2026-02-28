<?php

namespace Gov2lib\Exceptions;

class AuthenticationException extends HttpException
{
    public function __construct(
        string $message = 'Halaman ini membutuhkan login',
        ?\Throwable $previous = null
    ) {
        parent::__construct(401, $message, $previous);
    }
}
