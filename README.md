# File system related library
[![Latest Stable Version](https://img.shields.io/packagist/v/donbidon/lib-fs.svg?style=flat-square)](https://packagist.org/packages/donbidon/lib-fs)
[![Packagist](https://img.shields.io/packagist/dt/donbidon/lib-fs.svg)](https://packagist.org/packages/donbidon/lib-fs)
![PHP from Packagist](https://img.shields.io/packagist/php-v/donbidon/lib-fs.svg)
[![GitHub license](https://img.shields.io/github/license/donbidon/lib-fs.svg)](https://github.com/donbidon/lib-fs/blob/master/LICENSE)

[![Build Status](https://travis-ci.com/donbidon/lib-fs.svg?branch=master)](https://travis-ci.com/donbidon/lib-fs)
[![Code Coverage](https://codecov.io/gh/donbidon/lib-fs/branch/master/graph/badge.svg)](https://codecov.io/gh/donbidon/lib-fs)
[![GitHub issues](https://img.shields.io/github/issues-raw/donbidon/lib-fs.svg)](https://github.com/donbidon/lib-fs/issues)

[![Donate to liberapay](http://img.shields.io/liberapay/receives/don.bidon.svg?logo=liberapay)](https://liberapay.com/don.bidon/donate)

Look [API documentation](https://donbidon.github.io/docs/packages/lib-fs/).

## Installing
Run `composer require donbidon/lib-fs ~0.2`.

## Usage

### Tools: walking directory recursively
```php
\donbidon\Lib\FileSystem\Tools::walkDir(
    "/path/to/dir",
    function (\SplFileInfo $file): void
    {
        $path = $file->getRealPath();
        echo sprintf(
            "[%s] %s%s", $file->isDir() ? "DIR " : "file",
            $path,
            PHP_EOL
        );
    }

);
```

### Tools: removing directory recursively
```php
\donbidon\Lib\FileSystem\Tools::removeDir("/path/to/dir");
```

### Tools: searching & replacing recursively
```php
\donbidon\Lib\FileSystem\Tools::search(
    "/path/to/top/level",
    0,         // glob flags
    ["*.php"], // File name glob patterns
    ["*"],     // Subdir name glob patterns
    "needle",  // String to search in files, if starts with "/" processes
               // like regular expression
    // Callback
    function (string $path): void
    {
        $contents = file_get_contents($path);
        $contents = preg_replace("/needle/", "replace", $contents);
        file_put_contents($path, $contents);
    }
);
```

### Logging functionality supporting files rotation
```php
$logger = new \donbidon\Lib\FileSystem\Logger([
    'path'    => "/path/to/log",
    // 'maxSize' => int maxSize,   // Logger::DEFAULT_MAX_SIZE by default.
    // 'rotation' => int rotation, // Rotating files number, 0 means no rotation.
    // 'rights'    => int rights,  // If set after writing to log file chmod() will be called.
]);
$logger->log("Foo");
```

## Donate
[Yandex.Money, Visa, MasterCard, Maestro](https://money.yandex.ru/to/41001351141494) or visit [Liberapay](https://liberapay.com/don.bidon/donate).
