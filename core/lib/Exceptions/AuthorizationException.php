<?php

namespace Gov2lib\Exceptions;

#---coded by claude (seluruh file, 28 Feb 2026)
class AuthorizationException extends HttpException
{
    public function __construct(
        string $message = 'Anda tidak memiliki wewenang untuk mengakses halaman ini',
        ?\Throwable $previous = null
    ) {
        parent::__construct(403, $message, $previous);
    }
}
