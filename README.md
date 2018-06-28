# File system related library

Look [API documentation](https://donbidon.github.io/docs/packages/lib-fs/).

## Installing
Run `composer require donbidon/lib-fs 0.1.0` or add following code to your "composer.json" file:
```json
    "require": {
        "donbidon/lib-fs": "0.1.0"
    }
```
and run `composer update`.

## Usage

### Tools

#### Walking directory recursively
```php
function walker(\SplFileInfo $file)
{
    $path = $file->getRealPath();
    echo sprintf(
        "[%s] %s%s", $file->isDir() ? "DIR " : "file",
        $path,
        PHP_EOL
    );
}

\donbidon\Lib\FileSystem\Tools::walkDir("/path/to/dir", 'walker');
```

#### Removing directory recursively
```php
\donbidon\Lib\FileSystem\Tools::removeDir("/path/to/dir");
```

#### Search and repkace recursively
```php
\donbidon\Lib\FileSystem\Tools::search(
    "/path/to/top/level",
    0,         // glob flags
    ["*.php"], // File name glob patterns
    ["*"],     // Subdir name glob patterns
    "needle",  // String to search in files, if starts with "/" processes
               // like regular expression
    // Callback
    function ($path)
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
