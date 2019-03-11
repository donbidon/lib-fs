<?php
/**
 * File system related library.
 *
 * Tools.
 *
 * @copyright <a href="http://donbidon.rf.gd/" target="_blank">donbidon</a>
 * @license   https://opensource.org/licenses/mit-license.php
 */

declare(strict_types=1);

namespace donbidon\Lib\FileSystem;

use InvalidArgumentException;
use RuntimeException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * File system related library.
 *
 * Tools.
 *
 * <!-- move: index.html -->
 * <ul>
 *     <li><a href="classes/donbidon.Lib.FileSystem.Tools.html">
 * \donbidon\Lib\FileSystem\Tools</a> implements various recursively
 * functionality for files and directories.</li>
 *     <li><a href="classes/donbidon.Lib.FileSystem.Logger.html">
 * \donbidon\Lib\FileSystem\Logger</a> implements logging functionality
 * supporting files rotation.</li>
 * </ul>
 * <!-- /move -->
 *
 * @static
 */
class Tools
{
    /**
     * Used to search string in files
     *
     * @var string
     *
     * @see self::search()
     * @see self::filterFileByContents()
     *
     * @internal
     */
    protected static $needle;

    /**
     * Walks directory recursively.
     *
     * ```php
     * function walker(\SplFileInfo $file)
     * {
     *     $path = $file->getRealPath();
     *     echo sprintf(
     *         "[%s] %s%s", $file->isDir() ? "DIR " : "file",
     *         $path,
     *         PHP_EOL
     *     );
     * }
     *
     * \donbidon\Lib\FileSystem\Tools::walkDir("/path/to/dir", 'walker');
     * ```
     *
     * @param string   $path
     * @param callback $callback
     *
     * @return void
     *
     * @throws InvalidArgumentException  If passed path isn't a directory.
     * @throws RuntimeException  If Passed path cannot be read.
     */
    public static function walkDir(string $path, callable $callback): void
    {
        $realPath = realpath($path);
        if (!is_dir($realPath)) {
            throw new InvalidArgumentException(sprintf(
                "Passed path '%s' isn't a directory", $realPath
            ));
        }
        if (!is_readable($realPath)) {
            throw new \RuntimeException(sprintf(
                "Passed path '%s' cannot be read", $realPath
            ));
        }
        $dir = new RecursiveDirectoryIterator(
            $realPath,
            RecursiveDirectoryIterator::SKIP_DOTS
        );
        $files = iterator_to_array(new RecursiveIteratorIterator(
                $dir,
                RecursiveIteratorIterator::CHILD_FIRST
            )
        );
        array_walk($files, $callback, ['path' => $path]);
    }

    /**
     * Removes directory recursively.
     *
     * @param string $path
     * @param bool   $clearStatCache
     *
     * @return void
     */
    public static function removeDir(string $path, bool $clearStatCache = TRUE): void
    {
        self::walkDir($path, [__CLASS__, 'rmFile']);
        rmdir($path);
        if ($clearStatCache) {
            clearstatcache(true, $path);
        }
    }

    /**
     * Returns false if path ended with
     * "{DIRECTORY_SEPARATOR}.." or "{DIRECTORY_SEPARATOR}.".
     *
     * ```php
     * $dirs = array_filter(
     *     glob("/path/to/.*", GLOB_ONLYDIR),
     *     [\donbidon\Lib\FileSystem\Tools, "filterDots"]
     * );
     * ```
     *
     * @param string $path
     *
     * @return bool
     */
    public static function filterDots(string $path): bool
    {
        $result = !(
            DIRECTORY_SEPARATOR . ".." == substr($path, -3) ||
            DIRECTORY_SEPARATOR . "." == substr($path, -2)
        );

        return $result;
    }

    /**
     * Searches for files/directories using PHP glob function & files contents.
     *
     * ```php
     * // Search for all directories recursively:
     * $dirs = \donbidon\Lib\FileSystem\Tools::search(
     *     "/path/to/top/level",
     *     GLOB_ONLYDIR,
     *     ["*", ".*"],
     *     ["*", ".*"]
     * );
     *
     * // Replace contents of files:
     * \donbidon\Lib\FileSystem\Tools::search(
     *     "/path/to/top/level",
     *     0,         // glob flags
     *     ["*.php"], // File name glob patterns
     *     ["*"],     // Subdir name glob patterns
     *     "needle",  // String to search in files, if starts with "/" processes
     *                // like regular expression
     *     // Callback
     *     function ($path)
     *     {
     *         $contents = file_get_contents($path);
     *         $contents = preg_replace("/needle/", "replace", $contents);
     *         file_put_contents($path, $contents);
     *     }
     * );
     * ```
     *
     * @param string   $dir        Path to top level directory
     * @param int      $flags      Flags (glob)
     * @param array    $patterns   File name patterns (glob)
     * @param array    $recursive  Subdir name patterns (glob)
     * @param string $needle       String to search in files, if starts with "/"
     *        processes like regular expression
     * @param callback $callback   If passed empty array will be returned and
     * callback will be called on every found file/directory
     *
     * @return array
     */
    public static function search(
        string   $dir,
        int      $flags = 0,
        array    $patterns = [],
        array    $recursive = [],
        bool     $needle = null,
        callable $callback = null
    ): array
    {
        $return = [];
        foreach ($patterns as $pattern) {
            $path = implode(DIRECTORY_SEPARATOR, [$dir, $pattern]);
            $result = glob($path, $flags);
            $result = array_filter($result, [__CLASS__, "filterDots"]);
            if (!is_null($needle)) {
                static::$needle = $needle;
                $result = array_filter($result, [__CLASS__, "filterFileByContents"]);
                static::$needle = null;
            }
            if (is_null($callback)) {
                $return = array_merge($return, $result);
            } else {
                foreach ($result as $path) {
                    call_user_func($callback, $path);
                }
            }
            $dirs = [];
            foreach ($recursive as $durPattern) {
                $dirs = array_merge(
                    $dirs,
                    glob(implode(
                        DIRECTORY_SEPARATOR, [$dir, $durPattern]),
                        GLOB_ONLYDIR
                    )
                );
            }
            $dirs = array_filter($dirs, [__CLASS__, "filterDots"]);
            foreach ($dirs as $subdir) {
                $return = array_merge(
                    $return,
                    self::search(
                        $subdir,
                        $flags,
                        $patterns,
                        $recursive,
                        $needle,
                        $callback
                    )
                );
            }
        }

        return $return;
    }

    /**
     * Callback using for removing directory.
     *
     * Removes file or directory.
     *
     * @param SplFileInfo $file
     *
     * @return void
     *
     * @see self::remove()
     *
     * @internal
     */
    protected static function rmFile(SplFileInfo $file): void
    {
        $path = $file->getRealPath();
        $file->isDir() ? rmdir($path) : unlink($path);
    }

    /**
     * Filters files by search string.
     *
     * @param string $path
     *
     * @return bool
     *
     * @internal
     */
    protected static function filterFileByContents(string $path): bool
    {
        $contents = file_get_contents($path);
        $result =
            "/" == substr(static::$needle, 0, 1)
                ? (bool)preg_match(static::$needle, $contents)
                : FALSE !== strpos($contents, static::$needle);

        return $result;
    }
}
