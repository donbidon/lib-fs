<?php
/**
 * Tools class unit tests.
 *
 * @copyright <a href="http://donbidon.rf.gd/" target="_blank">donbidon</a>
 * @license   https://opensource.org/licenses/mit-license.php
 */

namespace donbidon\Lib\FileSystem;

use SplFileInfo;

/**
 * Tools class unit tests.
 *
 * @todo Cover by tests Tools::search().
 */
class ToolsTest extends \donbidon\Lib\PHPUnit\TestCase
{
    /**
     * Access rights for temporary directory
     */
    const RIGHTS = 0666;

    /**
     * Temporary directory path
     *
     * @var string
     * @see self::createDirStructure()
     */
    protected $tempPath;

    /**
     * Directory structure
     *
     * @var array
     * @see self::testRecursiveWalkDir()
     * @see self::buildDirStructure()
     */
    protected $dirStructure;

    /**
     * Tests recursive walking by directory.
     *
     * @return void
     * @covers \donbidon\Lib\FileSystem\Tools::walk
     */
    public function testWalk()
    {
        $this->dirStructure = [];
        $this->createDirStructure();
        Tools::walkDir($this->tempPath, [$this, 'buildDirStructure']);
        $this->assertEquals(
            [
                "[F] dir1/dir11/dir111/deepFile",
                "[D] dir1/dir11/dir111",
                "[D] dir1/dir11",
                "[D] dir1",
                "[D] dir2/dir22",
                "[D] dir2",
                "[D] dir3",
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
     * @covers \donbidon\Lib\FileSystem\Tools::remove
     */
    public function testRemove()
    {
        $this->createDirStructure();
        Tools::removeDir($this->tempPath);
        $this->assertFalse(is_dir($this->tempPath));
    }

    /**
     * Callback using for building directory structure.
     *
     * @param  SplFileInfo $file
     * @return void
     * @see    self::testRemove()
     */
    public function buildDirStructure(SplFileInfo $file)
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
     * @internal
     */
    protected function createDirStructure()
    {
        $this->tempPath = implode(
            DIRECTORY_SEPARATOR,
            [sys_get_temp_dir(), "donbidon", "tests", "lib-fs", uniqid()]
        );
        $deepPath = implode(
            DIRECTORY_SEPARATOR,
            [$this->tempPath, "dir1", "dir11", "dir111"]
        );
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
