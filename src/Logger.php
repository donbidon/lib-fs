<?php
/**
 * File system related library.
 *
 * Logging functionality supporting files rotation.
 *
 * @copyright <a href="http://donbidon.rf.gd/" target="_blank">donbidon</a>
 * @license   https://opensource.org/licenses/mit-license.php
 */

declare(strict_types=1);

namespace donbidon\Lib\FileSystem;

/**
 * File system related library.
 *
 * Logging functionality supporting files rotation.
 * ```php
 * $logger = new \donbidon\Lib\FileSystem\Logger([
 *     'path'    => "/path/to/log",
 *     // 'maxSize' => int maxSize,   // Logger::DEFAULT_MAX_SIZE by default.
 *     // 'rotation' => int rotation, // Rotating files number, 0 means no rotation.
 *     // 'rights'    => int rights,  // Uf set after writing to log file chmod() will be called.
 * ]);
 * $logger->log("Foo");
 * ```
 */
class Logger
{
    /**
     * Default log file max size, 1 MB
     *
     * @var int
     */
    const DEFAULT_MAX_SIZE = 1048576;

    /**
     * Default options
     *
     * @var array
     */
    protected $defaults;

    /**
     * Constructor.
     *
     * @param array $options  Following pairs of keys and values can be passed:
     * - 'path'     - default path,
     * - 'maxSize'  - max file size,
     * - 'rotation' - number of files to rotate log,
     * - 'rights'   - file mode, chmod() will be skipped if null passed.
     *
     * @return void
     */
    public function __construct(array $options = [
        'path'     => null,
        'maxSize'  => self::DEFAULT_MAX_SIZE,
        'rotation' => 0,
        'rights'    => null,
    ])
    {
        $this->setDefaults($options);
    }

    /**
     * Sets default options.
     *
     * @param array $options
     * @param bool  $override  Flag specifying do override whole defaults
     *
     * @return void
     *
     * @see self::__construct()  $options parameter description
     */
    public function setDefaults(array $options, bool $override = FALSE): void
    {
        $this->defaults = $options +
            ($override ? [] : [
                'path'     => null,
                'maxSize'  => self::DEFAULT_MAX_SIZE,
                'rotation' => 0,
                'rights'    => null,
            ]);
    }

    /**
     * Rotate log files and logs message.
     *
     * @param string $message
     * @param string $path     If null passed will be used from options
     * @param array  $options
     *
     * @return void
     *
     * @throws \RuntimeException  If path taken from args and options are missing.
     * @throws \RuntimeException  If directory from path is invalid.
     *
     * @see self::__construct()  $options parameter description
     */
    public function log(string $message, string $path = null, array $options = []): void
    {
        $options =
            $options +
            (is_null($path) ? [] : ['path' => $path]) +
            $this->defaults;
        $path = $options['path'];
        if (is_null($path)) {
            throw new \RuntimeException("Missing path");
        }
        $realPath = realpath(dirname($path));
        if (FALSE === $realPath) {
            throw new \RuntimeException(sprintf(
                "Invalid directory '%s'",
                $path
            ));
        }
        $path = sprintf(
            "%s/%s",
            $realPath,
            basename($path)
        );
        clearstatcache(true, $path);
        if (file_exists($path) && (filesize($path) > $options['maxSize'])) {
            for ($i = $options['rotation']; $i > 0; --$i) {
                $destPath = sprintf("%s.%d", $path, $i);
                if (file_exists($destPath)) {
                    unlink($destPath);
                }
                $sourcePath = $i > 1 ? sprintf("%s.%d", $path, $i - 1) : $path;
                if (file_exists($sourcePath)) {
                    rename($sourcePath, $destPath);
                }
            }
            if (file_exists($path)) {
                unlink($path);
            }
        }
        file_put_contents($path, $message, FILE_APPEND);
        if (!is_null($options['rights'])) {
            chmod($path, $options['rights']);
        }
    }
}
