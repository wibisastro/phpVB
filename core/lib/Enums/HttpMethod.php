<?php

namespace Gov2lib\Enums;

#---coded by claude (seluruh file, 28 Feb 2026)
enum HttpMethod: string
{
    case GET = 'GET';
    case POST = 'POST';
    case PUT = 'PUT';
    case PATCH = 'PATCH';
    case DELETE = 'DELETE';
    case OPTIONS = 'OPTIONS';
}
