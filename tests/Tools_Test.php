<?php
/**
 * Tools class unit tests.
 *
 * @copyright <a href="http://donbidon.rf.gd/" target="_blank">donbidon</a>
 * @license   https://opensource.org/licenses/mit-license.php
 */

declare(strict_types=1);

namespace donbidon\Lib\FileSystem;

use AssertionError;
use InvalidArgumentException;
use SplFileInfo;

/**
 * Tools class unit tests.
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
     * Tests exception when passed wrong path.
     *
     * @return void
     *
     * @cover donbidon\Lib\FileSystem\Tools::walkDir()
     */
    public function testInvalidDir(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Passed path \"./tests/invalid/path\" (false) isn't a directory"
        );
        Tools::walkDir("./tests/invalid/path", [$this, "buildDirStructure"]);
    }

    /**
     * Tests recursive walking by directory.
     *
     * @return void
     *
     * @cover donbidon\Lib\FileSystem\Tools::walkDir()
     */
    public function testWalking(): void
    {
        $this->dirStructure = [];
        $this->createDirStructure();
        Tools::walkDir($this->tempPath, [$this, "buildDirStructure"]);
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
    }

    /**
     * Tests recursive removing of directory.
     *
     * @return void
     *
     * @cover donbidon\Lib\FileSystem\Tools::removeDir()
     */
    public function testRemoving(): void
    {
        $this->createDirStructure();
        Tools::removeDir($this->tempPath);
        $this->assertFalse(is_dir($this->tempPath));
    }

    /**
     * Tests recursive searching passing default args.
     *
     * @return void
     *
     * @cover donbidon\Lib\FileSystem\Tools::search()
     */
    public function testSearchingPassingEmptyArgs(): void
    {
        $this->assertEquals(
            [],
            Tools::search("")
        );
    }

    /**
     * Tests recursive searching.
     *
     * @return void
     *
     * @cover donbidon\Lib\FileSystem\Tools::search()
     */
    public function testSearching(): void
    {
        $this->createDirStructure();

        $expected = ["dir1", "dir2", "dir3", "file", ];
        $actual = array_map(
            [$this, "cutTempPath"],
            Tools::search($this->tempPath, 0, ["*"])
        );
        sort($actual);
        $this->assertEquals($expected, $actual);

        array_pop($expected);
        $actual = array_map(
            [$this, "cutTempPath"],
            Tools::search($this->tempPath, GLOB_ONLYDIR, ["*"])
        );
        sort($actual);
        $this->assertEquals($expected, $actual);

        $expected = [
            "dir1",
            implode(DIRECTORY_SEPARATOR, ["dir1", "dir11", ]),
            implode(DIRECTORY_SEPARATOR, ["dir1", "dir11", "dir111", ]),
            implode(DIRECTORY_SEPARATOR, ["dir1", "dir11", "dir111", "deepFile", ]),
            "dir2",
            implode(DIRECTORY_SEPARATOR, ["dir2", "dir22", ]),
            "dir3",
            "file",
        ];
        $actual = array_map(
            [$this, "cutTempPath"],
            Tools::search($this->tempPath, 0, ["*"], ["*"])
        );
        sort($actual);
        // fwrite(STDERR, "\n" . var_export($actual, true) . "\n");###
        $this->assertEquals($expected, $actual);

        $expected = [
            "dir1",
            implode(DIRECTORY_SEPARATOR, ["dir1", "dir11", ]),
            implode(DIRECTORY_SEPARATOR, ["dir1", "dir11", "dir111", ]),
            implode(DIRECTORY_SEPARATOR, ["dir1", "dir11", "dir111", "deepFile", ]),
            "dir2",
            "dir3",
        ];
        $actual = array_map(
            [$this, "cutTempPath"],
            Tools::search($this->tempPath, 0, ["d*"], ["dir1*"])
        );
        sort($actual);
        $this->assertEquals($expected, $actual);

        $expected = [
            implode(DIRECTORY_SEPARATOR, ["dir1", "dir11", "dir111", "deepFile", ]),
            "file",
        ];
        $actual = array_map(
            [$this, "cutTempPath"],
            Tools::search(
                $this->tempPath,
                0,
                ["*"],
                ["*"],
                "/cont/i"
            )
        );
        sort($actual);
        $this->assertEquals($expected, $actual);

        $expected = [
            "file",
        ];
        $actual = array_map(
            [$this, "cutTempPath"],
            Tools::search(
                $this->tempPath,
                0,
                ["*"],
                ["*"],
                "CONT"
            )
        );
        sort($actual);
        // fwrite(STDERR, "\n" . var_export($actual, true) . "\n");###
        $this->assertEquals($expected, $actual);
    }

    /**
     * Tests recursive searching using callback.
     *
     * @return void
     *
     * @cover donbidon\Lib\FileSystem\Tools::search()
     */
    public function testSearchingCallback(): void
    {
        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage(
            "Path: file, needle: CONT"
        );
        $this->createDirStructure();
        Tools::search(
            $this->tempPath,
            0,
            ["*"],
            ["*"],
            "CONT",
            function (string $path, array $args): void
            {
                throw new AssertionError(sprintf(
                    "Path: %s, needle: %s",
                    $this->cutTempPath($path),
                    $args["needle"]
                ));
            }
        );
    }

    /**
     * Callback using for building directory structure.
     *
     * @param SplFileInfo $file
     *
     * @return void
     *
     * @see self::testRemove()
     *
     * @internal
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
     * {@inheritdoc}
     *
     * @return void
     */
    protected function tearDown(): void
    {
        if (!is_null($this->tempPath) && is_dir($this->tempPath)) {
            Tools::removeDir($this->tempPath);
        }

        parent::tearDown();
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
            "someCONTEnt"
        );
        file_put_contents(
            implode(
                DIRECTORY_SEPARATOR,
                [$deepPath, "deepFile"]
            ),
            "contraception"
        );
    }

    /**
     * Callback cutting $this->tempPath.

     * @param string $path
     *
     * @return string
     *
     * @see self::testSearching()
     * @see self::testSearchingCallback()
     *
     * @internal
     */
    protected function cutTempPath(string $path): string
    {
        return substr($path, strlen($this->tempPath) + 1);
    }
}
