<?php

declare(strict_types=1);

namespace Waffle\Enum;

/**
 * Enum representing the different types of parameter bags in an HTTP request.
 */
enum HttpBag: string
{
    case QUERY = 'query';
    case REQUEST = 'request';
    case SERVER = 'server';
    case FILES = 'files';
    case COOKIES = 'cookies';
    case SESSION = 'session';
    case ENV = 'env';
}
