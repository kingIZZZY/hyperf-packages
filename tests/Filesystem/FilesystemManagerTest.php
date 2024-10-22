<?php

declare(strict_types=1);

namespace SwooleTW\Hyperf\Tests\Filesystem;

use Hyperf\Config\Config;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\ContainerInterface;
use Hyperf\Di\Container;
use Hyperf\Di\Definition\DefinitionSource;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;
use PHPUnit\Framework\TestCase;
use SwooleTW\Hyperf\Filesystem\Contracts\Filesystem;
use SwooleTW\Hyperf\Filesystem\FilesystemManager;
use SwooleTW\Hyperf\Filesystem\FilesystemPoolProxy;

/**
 * @internal
 * @coversNothing
 */
class FilesystemManagerTest extends TestCase
{
    public function testExceptionThrownOnUnsupportedDriver()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Disk [local] does not have a configured driver.');

        $container = $this->getContainer([
            'disks' => [
                'local' => [],
            ],
        ]);
        $filesystem = new FilesystemManager($container);

        $filesystem->disk('local');
    }

    public function testCanBuildOnDemandDisk()
    {
        $filesystem = new FilesystemManager($this->getContainer());

        $this->assertInstanceOf(Filesystem::class, $filesystem->build('my-custom-path'));

        $this->assertInstanceOf(Filesystem::class, $filesystem->build([
            'driver' => 'local',
            'root' => 'my-custom-path',
            'url' => 'my-custom-url',
            'visibility' => 'public',
        ]));

        rmdir(__DIR__ . '/../../my-custom-path');
    }

    public function testCanBuildReadOnlyDisks()
    {
        $filesystem = new FilesystemManager($this->getContainer());

        $disk = $filesystem->build([
            'driver' => 'local',
            'read-only' => true,
            'root' => 'my-custom-path',
            'url' => 'my-custom-url',
            'visibility' => 'public',
        ]);

        file_put_contents(__DIR__ . '/../../my-custom-path/path.txt', 'contents');

        // read operations work
        $this->assertEquals('contents', $disk->get('path.txt'));
        $this->assertEquals(['path.txt'], $disk->files());

        // write operations fail
        $this->assertFalse($disk->put('path.txt', 'contents'));
        $this->assertFalse($disk->delete('path.txt'));
        $this->assertFalse($disk->deleteDirectory('directory'));
        $this->assertFalse($disk->prepend('path.txt', 'data'));
        $this->assertFalse($disk->append('path.txt', 'data'));
        $handle = fopen('php://memory', 'rw');
        fwrite($handle, 'content');
        $this->assertFalse($disk->writeStream('path.txt', $handle));
        fclose($handle);

        unlink(__DIR__ . '/../../my-custom-path/path.txt');
        rmdir(__DIR__ . '/../../my-custom-path');
    }

    public function testCanBuildScopedDisks()
    {
        try {
            $container = $this->getContainer([
                'disks' => [
                    'local' => [
                        'driver' => 'local',
                        'root' => 'to-be-scoped',
                    ],
                ],
            ]);
            $filesystem = new FilesystemManager($container);

            $local = $filesystem->disk('local');
            $scoped = $filesystem->build([
                'driver' => 'scoped',
                'disk' => 'local',
                'prefix' => 'path-prefix',
            ]);

            $scoped->put('dirname/filename.txt', 'file content');
            $this->assertEquals('file content', $local->get('path-prefix/dirname/filename.txt'));
            $local->deleteDirectory('path-prefix');
        } finally {
            rmdir(__DIR__ . '/../../to-be-scoped');
        }
    }

    #[RequiresOperatingSystem('Linux|Darwin')]
    public function testCanBuildScopedDisksWithVisibility()
    {
        try {
            $container = $this->getContainer([
                'disks' => [
                    'local' => [
                        'driver' => 'local',
                        'root' => 'to-be-scoped',
                        'visibility' => 'public',
                    ],
                ],
            ]);
            $filesystem = new FilesystemManager($container);

            $scoped = $filesystem->build([
                'driver' => 'scoped',
                'disk' => 'local',
                'prefix' => 'path-prefix',
                'visibility' => 'private',
            ]);

            $scoped->put('dirname/filename.txt', 'file content');

            $this->assertEquals('private', $scoped->getVisibility('dirname/filename.txt'));
        } finally {
            unlink(__DIR__ . '/../../to-be-scoped/path-prefix/dirname/filename.txt');
            rmdir(__DIR__ . '/../../to-be-scoped/path-prefix/dirname');
            rmdir(__DIR__ . '/../../to-be-scoped/path-prefix');
            rmdir(__DIR__ . '/../../to-be-scoped');
        }
    }

    public function testCanBuildInlineScopedDisks()
    {
        try {
            $filesystem = new FilesystemManager($this->getContainer());

            $scoped = $filesystem->build([
                'driver' => 'scoped',
                'disk' => [
                    'driver' => 'local',
                    'root' => 'to-be-scoped',
                ],
                'prefix' => 'path-prefix',
            ]);

            $scoped->put('dirname/filename.txt', 'file content');
            $this->assertTrue(is_dir(__DIR__ . '/../../to-be-scoped/path-prefix'));
            $this->assertEquals(file_get_contents(__DIR__ . '/../../to-be-scoped/path-prefix/dirname/filename.txt'), 'file content');
        } finally {
            unlink(__DIR__ . '/../../to-be-scoped/path-prefix/dirname/filename.txt');
            rmdir(__DIR__ . '/../../to-be-scoped/path-prefix/dirname');
            rmdir(__DIR__ . '/../../to-be-scoped/path-prefix');
            rmdir(__DIR__ . '/../../to-be-scoped');
        }
    }

    public function testPoolableDriver()
    {
        $container = $this->getContainer([
            'disks' => [
                'local' => [
                    'driver' => 'local',
                ],
            ],
        ]);
        $filesystem = (new FilesystemManager($container))
            ->addPoolable('local');

        ApplicationContext::setContainer($container);

        $this->assertInstanceOf(FilesystemPoolProxy::class, $filesystem->disk('local'));
    }

    protected function getContainer(array $config = []): ContainerInterface
    {
        $config = new Config(['filesystems' => $config]);

        return new Container(
            new DefinitionSource([
                ConfigInterface::class => fn () => $config,
            ])
        );
    }
}