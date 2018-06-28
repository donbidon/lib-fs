<?php
/**
 * Logger class unit tests.
 *
 * @copyright <a href="http://donbidon.rf.gd/" target="_blank">donbidon</a>
 * @license   https://opensource.org/licenses/mit-license.php
 */

namespace donbidon\Lib\FileSystem;

use \RecursiveDirectoryIterator;

/**
 * Logger class unit tests.
 */
class LoggerTest extends \donbidon\Lib\PHPUnit\TestCase
{
    /**
     * Log directory access rights
     *
     * @vat int
     */
    const LOG_DIRECTORY_RIGHTS = 0666;

    /**
     * Tests logging without/with rotation.
     *
     * @return void
     * @covers \donbidon\Lib\FileSystem\Logger::log
     * @covers \donbidon\Lib\FileSystem\Logger::setDefaults
     * setDefaults
     */
    public function testLogging()
    {
        $dir     = implode(
            DIRECTORY_SEPARATOR,
            [sys_get_temp_dir(), "donbidon", "tests", "lib-fs", uniqid()]
        );
        $path    = implode(DIRECTORY_SEPARATOR, [$dir, "test.log"]);
        $message = sprintf("Test message%s", PHP_EOL);
        $len     = strlen($message);

        // No rotation {

        $this->recreateLogDirectory($dir);
        $options = ['maxSize' => $len, ];
        $logger = new Logger;

        $logger->log($message, $path, $options);
        $this->assertEquals($message, file_get_contents($path));

        $logger->log($message, $path, $options);
        $this->assertEquals(
            $message . $message,
            file_get_contents($path)
        );

        $logger->log($message, $path, $options);
        $this->assertEquals($message, file_get_contents($path));
        $this->assertEquals(
            ["test.log", ],
            $this->getDirectoryAsArray($dir)
        );

        // }
        // With rotation {

        $this->recreateLogDirectory($dir);
        $logger->setDefaults([
            'path'     => $path,
            'maxSize'  => $len,
            'rotation' => 2,
        ]);

        $logger->log($message, $path, $options);
        $this->assertEquals($message, file_get_contents($path));

        $logger->log($message, $path, $options);
        $this->assertEquals(
            $message . $message,
            file_get_contents($path)
        );

        $logger->log($message, $path, $options);
        $this->assertEquals(
            ["test.log", "test.log.1", ],
            $this->getDirectoryAsArray($dir)
        );
        $this->assertEquals($message, file_get_contents($path));
        $this->assertEquals(
            $message . $message,
            file_get_contents(sprintf("%s.1", $path))
        );

        $message1 = sprintf("TEST MESSAGE%s", PHP_EOL);
        $logger->log($message1, $path, $options);
        $logger->log($message1, $path, $options);
        $this->assertEquals(
            ["test.log", "test.log.1", "test.log.2", ],
            $this->getDirectoryAsArray($dir)
        );
        $this->assertEquals($message1, file_get_contents($path));
        $this->assertEquals(
            $message . $message1,
            file_get_contents(sprintf("%s.1", $path))
        );
        $this->assertEquals(
            $message . $message,
            file_get_contents(sprintf("%s.2", $path))
        );
        $logger->log($message1, $path, $options);
        $logger->log($message1, $path, $options);
        $this->assertEquals(
            ["test.log", "test.log.1", "test.log.2", ],
            $this->getDirectoryAsArray($dir)
        );
        $this->assertEquals($message1, file_get_contents($path));
        $this->assertEquals(
            $message1 . $message1,
            file_get_contents(sprintf("%s.1", $path))
        );
        $this->assertEquals(
            $message . $message1,
            file_get_contents(sprintf("%s.2", $path))
        );

        // }

        Tools::removeDir($dir);
    }

    /**
     * Recreates log directory.
     *
     * @param  string $path
     * @return void
     * @see    self::testLog()
     * @internal
     */
    protected function recreateLogDirectory($path)
    {
        if (file_exists($path)) {
            Tools::removeDir($path);
        }
        mkdir($path, self::LOG_DIRECTORY_RIGHTS, TRUE);
    }

    /**
     * Returns directory files as array )not recursively).
     *
     * @param  string $path
     * @return array
     * @see    self::testLog()
     * @internal
     */
    protected function getDirectoryAsArray($path)
    {
        $files = [];
        $dir = iterator_to_array(new RecursiveDirectoryIterator(
            $path,
            RecursiveDirectoryIterator::SKIP_DOTS
        ));
        foreach ($dir as /** @var \SplFileInfo */ $fileInfo) {
            if ($fileInfo->isFile()) {
                $files[] = $fileInfo->getFilename();
            }
        }

        return $files;
    }
}
