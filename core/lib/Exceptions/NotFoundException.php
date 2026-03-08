<?php

namespace Gov2lib\Exceptions;

#---coded by claude (seluruh file, 28 Feb 2026)
class NotFoundException extends HttpException
{
    public function __construct(
        string $message = 'Resource tidak ditemukan',
        ?\Throwable $previous = null
    ) {
        parent::__construct(404, $message, $previous);
    }
}
