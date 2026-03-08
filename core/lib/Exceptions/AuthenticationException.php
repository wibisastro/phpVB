<?php

namespace Gov2lib\Exceptions;

#---coded by claude (seluruh file, 28 Feb 2026)
class AuthenticationException extends HttpException
{
    public function __construct(
        string $message = 'Halaman ini membutuhkan login',
        ?\Throwable $previous = null
    ) {
        parent::__construct(401, $message, $previous);
    }
}
