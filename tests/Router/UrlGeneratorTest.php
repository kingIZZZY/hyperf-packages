<?php

declare(strict_types=1);

namespace SwooleTW\Hyperf\Tests\Router;

use Hyperf\Context\ApplicationContext;
use Hyperf\Context\Context;
use Hyperf\Context\RequestContext;
use Hyperf\Contract\ContainerInterface;
use Hyperf\HttpMessage\Server\Request as ServerRequest;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Request;
use Hyperf\HttpServer\Router\DispatcherFactory as HyperfDispatcherFactory;
use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use SwooleTW\Hyperf\Router\DispatcherFactory;
use SwooleTW\Hyperf\Router\NamedRouteCollector;
use SwooleTW\Hyperf\Router\UrlGenerator;
use SwooleTW\Hyperf\Tests\Router\Stub\UrlRoutableStub;

/**
 * @internal
 * @coversNothing
 */
class UrlGeneratorTest extends TestCase
{
    /**
     * @var ContainerInterface|MockInterface
     */
    private ContainerInterface $container;

    /**
     * @var MockInterface|NamedRouteCollector
     */
    private NamedRouteCollector $router;

    protected function setUp(): void
    {
        $this->mockContainer();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testRoute()
    {
        $this->mockRouter();

        $this->router
            ->shouldReceive('getNamedRoutes')
            ->andReturn([
                'foo' => ['/foo'],
                'bar' => ['/foo/', ['bar', '[^/]+']],
                'baz' => ['/foo/', ['bar', '[^/]+'], '/baz'],
            ]);

        $urlGenerator = new UrlGenerator($this->container);

        $this->assertEquals('/foo', $urlGenerator->route('foo'));
        $this->assertEquals('/foo?bar=1', $urlGenerator->route('foo', ['bar' => 1]));
        $this->assertEquals('/foo?bar=1&baz=2', $urlGenerator->route('foo', ['bar' => 1, 'baz' => 2]));
        $this->assertEquals('/foo', $urlGenerator->route('bar'));
        $this->assertEquals('/foo/1', $urlGenerator->route('bar', ['bar' => 1]));
        $this->assertEquals('/foo/1?baz=2', $urlGenerator->route('bar', ['bar' => 1, 'baz' => 2]));
        $this->assertEquals('/foo/1/baz', $urlGenerator->route('baz', ['bar' => 1]));
        $this->assertEquals('/foo/1/baz?baz=2', $urlGenerator->route('baz', ['bar' => 1, 'baz' => 2]));
    }

    public function testRouteWithNotDefined()
    {
        $this->mockRouter();

        $this->router
            ->shouldReceive('getNamedRoutes')
            ->andReturn([]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Route [foo] not defined.');

        $urlGenerator = new UrlGenerator($this->container);
        $urlGenerator->route('foo');
    }

    public function testTo()
    {
        $this->mockRequest();

        $urlGenerator = new UrlGenerator($this->container);

        $this->assertEquals('http://example.com/foo', $urlGenerator->to('foo'));
    }

    public function testToWithValidUrl()
    {
        $this->mockRequest();

        $urlGenerator = new UrlGenerator($this->container);

        $this->assertEquals('http://example.com', $urlGenerator->to('http://example.com'));
        $this->assertEquals('https://example.com', $urlGenerator->to('https://example.com'));
        $this->assertEquals('//example.com', $urlGenerator->to('//example.com'));
        $this->assertEquals('mailto:hello@example.com', $urlGenerator->to('mailto:hello@example.com'));
        $this->assertEquals('tel:1234567890', $urlGenerator->to('tel:1234567890'));
        $this->assertEquals('sms:1234567890', $urlGenerator->to('sms:1234567890'));
        $this->assertEquals('#foo', $urlGenerator->to('#foo'));
        $this->assertEquals('ftp://example.com', $urlGenerator->to('ftp://example.com'));
    }

    public function testToWithExtra()
    {
        $this->mockRequest();

        $urlGenerator = new UrlGenerator($this->container);

        $this->assertEquals('http://example.com/foo/bar/baz', $urlGenerator->to('foo', ['bar', 'baz']));
        $this->assertEquals('http://example.com/foo/%3F/%3D', $urlGenerator->to('foo', ['?', '=']));
        $this->assertEquals('http://example.com/foo/1', $urlGenerator->to('foo', [new UrlRoutableStub()]));
    }

    public function testToWithSecure()
    {
        $this->mockRequest();

        $urlGenerator = new UrlGenerator($this->container);

        $this->assertEquals('https://example.com/foo', $urlGenerator->to('foo', secure: true));
    }

    public function testToWithRootUrlCache()
    {
        $this->mockRequest();

        $urlGenerator = new UrlGenerator($this->container);

        $this->assertEquals('http://example.com/foo', $urlGenerator->to('foo'));
        $this->assertEquals('http://example.com', Context::get('request.root')->toString());
    }

    public function testSecure()
    {
        $this->mockRequest();

        $urlGenerator = new UrlGenerator($this->container);

        $this->assertEquals('https://example.com/foo', $urlGenerator->secure('foo'));
        $this->assertEquals('https://example.com/foo/bar', $urlGenerator->secure('foo', ['bar']));
    }

    private function mockContainer()
    {
        ! defined('BASE_PATH') && define('BASE_PATH', __DIR__);

        $this->container = Mockery::mock(ContainerInterface::class);

        ApplicationContext::setContainer($this->container);
    }

    private function mockRouter()
    {
        /** @var DispatcherFactory|MockInterface */
        $factory = Mockery::mock(DispatcherFactory::class);

        /** @var MockInterface|NamedRouteCollector */
        $router = Mockery::mock(NamedRouteCollector::class);

        $this->container
            ->shouldReceive('get')
            ->with(HyperfDispatcherFactory::class)
            ->andReturn($factory);

        $factory
            ->shouldReceive('getRouter')
            ->with('http')
            ->andReturn($router);

        $this->router = $router;
    }

    private function mockRequest()
    {
        $this->container->shouldReceive('get')
            ->with(RequestInterface::class)
            ->andReturn(new Request());

        RequestContext::set(new ServerRequest('GET', 'http://example.com/foo?bar=baz#boom'));
    }
}