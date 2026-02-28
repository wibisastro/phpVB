<?php

namespace Gov2lib\Exceptions;

class AuthorizationException extends HttpException
{
    public function __construct(
        string $message = 'Anda tidak memiliki wewenang untuk mengakses halaman ini',
        ?\Throwable $previous = null
    ) {
        parent::__construct(403, $message, $previous);
    }
}
