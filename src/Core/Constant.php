<?php

declare(strict_types=1);

namespace Waffle\Core;

final class Constant
{
    // Define App constants
    public const string APP_ENV = 'APP_ENV';
    public const string APP_DEBUG = 'APP_DEBUG';

    // Define defaults environments
    public const string ENV_DEFAULT = self::ENV_PROD;
    public const string ENV_DEV = 'dev';
    public const string ENV_TEST = 'test';
    public const string ENV_STAGING = 'staging';
    public const string ENV_PROD = 'prod';
    public const string ENV_EXCEPTION = 'exception';

    // Define globals constants
    public const string FAILSAFE_DEFAULT = self::DISABLED;
    public const string ENABLED = 'enabled';
    public const string DISABLED = 'disabled';
    public const string EMPTY_STRING = '';
    public const string CURRENT_DIR = '.';
    public const string PREVIOUS_DIR = '..';
    public const string QUESTIONMARK = '?';
    public const string ARRAY_SEPARATOR_ALL = ' & ';
    public const array DEFAULT_DATA = [
        'message' => 'Welcome to Waffle!',
    ];
    public const string DEFAULT = 'default';
    public const string PHPEXT = '.php';
    public const string CLASSNAME = 'classname';
    public const string WEB = 'web';
    public const string METHOD = 'method';
    public const string METHOD_GET = 'GET';
    public const string METHOD_POST = 'POST';
    public const string ARGUMENTS = 'arguments';
    public const string PATH = 'path';
    public const string NAME = 'name';
    public const string REQUEST_URI = 'REQUEST_URI';
    public const string TYPE_STRING = 'string';
    public const string TYPE_INT = 'int';
    public const string TYPE_VOID = 'void';
    public const string TYPE_MIXED = 'mixed';
    public const string DS_STORE = '.DS_Store';

    // Define security constants
    public const int SECURITY_LEVEL0 = 0;
    public const int SECURITY_LEVEL1 = 1;
    public const int SECURITY_LEVEL2 = 2;
    public const int SECURITY_LEVEL3 = 3;
    public const int SECURITY_LEVEL4 = 4;
    public const int SECURITY_LEVEL5 = 5;
    public const int SECURITY_LEVEL6 = 6;
    public const int SECURITY_LEVEL7 = 7;
    public const int SECURITY_LEVEL8 = 8;
    public const int SECURITY_LEVEL9 = 9;
    public const int SECURITY_LEVEL10 = 10;
}
