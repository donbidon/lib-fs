<?php
/**
 * Tools class unit tests.
 *
 * @copyright <a href="http://donbidon.rf.gd/" target="_blank">donbidon</a>
 * @license   https://opensource.org/licenses/mit-license.php
 */

declare(strict_types=1);

namespace donbidon\Lib\FileSystem;

use SplFileInfo;

/**
 * Tools class unit tests.
 *
 * @todo Cover by tests Tools::search().
 */
class Tools_Test extends \PHPUnit\Framework\TestCase
{
    /**
     * Access rights for temporary directory
     */
    const RIGHTS = 0777;

    /**
     * Temporary directory path
     *
     * @var string
     *
     * @see self::createDirStructure()
     */
    protected $tempPath;

    /**
     * Directory structure
     *
     * @var array
     *
     * @see self::testRecursiveWalkDir()
     * @see self::buildDirStructure()
     */
    protected $dirStructure;

    /**
     * Tests recursive walking by directory.
     *
     * @return void
     *
     * @cover donbidon\Lib\FileSystem\Tools::walk()
     */
    public function testWalk(): void
    {
        $this->dirStructure = [];
        $this->createDirStructure();
        Tools::walkDir($this->tempPath, [$this, 'buildDirStructure']);
        sort($this->dirStructure);
        $this->assertEquals(
            [
                "[D] dir1",
                "[D] dir1/dir11",
                "[D] dir1/dir11/dir111",
                "[D] dir2",
                "[D] dir2/dir22",
                "[D] dir3",
                "[F] dir1/dir11/dir111/deepFile",
                "[F] file",
            ],
            $this->dirStructure
        );
        Tools::removeDir($this->tempPath);
    }

    /**
     * Tests recursive removing of directory.
     *
     * @return void
     *
     * @cover donbidon\Lib\FileSystem\Tools::remove()
     */
    public function testRemove(): void
    {
        $this->createDirStructure();
        Tools::removeDir($this->tempPath);
        $this->assertFalse(is_dir($this->tempPath));
    }

    /**
     * Callback using for building directory structure.
     *
     * @param SplFileInfo $file
     *
     * @return void
     *
     * @see self::testRemove()
     */
    public function buildDirStructure(SplFileInfo $file): void
    {
        $this->dirStructure[] = str_replace(DIRECTORY_SEPARATOR, "/", sprintf(
            "[%s] %s",
            $file->isDir() ? "D" : "F",
            substr($file->getRealPath(), strlen($this->tempPath) + 1)
        ));
    }

    /**
     * Creates directory structure.
     *
     * @return void
     *
     * @internal
     */
    protected function createDirStructure(): void
    {
        $this->tempPath = implode(
            DIRECTORY_SEPARATOR,
            [sys_get_temp_dir(), "donbidon", "tests", "lib-fs", uniqid()]
        );
        $deepPath = implode(
            DIRECTORY_SEPARATOR,
            [$this->tempPath, "dir1", "dir11", "dir111"]
        );
        $umask = umask(0);
        mkdir($deepPath, self::RIGHTS, TRUE);
        mkdir(
            implode(
                DIRECTORY_SEPARATOR,
                [$this->tempPath, "dir2", "dir22"]
            ),
            self::RIGHTS,
            TRUE
        );
        mkdir(
            implode(
                DIRECTORY_SEPARATOR,
                [$this->tempPath, "dir3"]
            ),
            self::RIGHTS,
            TRUE
        );
        umask($umask);
        file_put_contents(
            implode(
                DIRECTORY_SEPARATOR,
                [$this->tempPath, "file"]
            ),
            ""
        );
        file_put_contents(
            implode(
                DIRECTORY_SEPARATOR,
                [$deepPath, "deepFile"]
            ),
            ""
        );
    }
}
