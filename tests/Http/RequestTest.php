<?php

declare(strict_types=1);

namespace SwooleTW\Hyperf\Tests\Http;

use Carbon\Carbon;
use Hyperf\Collection\Collection;
use Hyperf\Context\Context;
use Hyperf\HttpMessage\Upload\UploadedFile;
use Hyperf\HttpMessage\Uri\Uri;
use Hyperf\Stringable\Stringable;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use SwooleTW\Hyperf\Http\Request;
use Swow\Psr7\Message\ServerRequestPlusInterface;

/**
 * @internal
 * @coversNothing
 */
class RequestTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        Context::set(ServerRequestInterface::class, null);
        Context::set('http.request.parsedData', null);
    }

    public function testAllFiles()
    {
        $psrRequest = Mockery::mock(ServerRequestPlusInterface::class);
        $psrRequest->shouldReceive('getUploadedFiles')->andReturn([
            'file' => new UploadedFile('/tmp/tmp_name', 32, 0),
        ]);
        Context::set(ServerRequestInterface::class, $psrRequest);
        $request = new Request();

        $this->assertEquals(['file' => new UploadedFile('/tmp/tmp_name', 32, 0)], $request->allFiles());
    }

    public function testAnyFilled()
    {
        $psrRequest = Mockery::mock(ServerRequestPlusInterface::class);
        $psrRequest->shouldReceive('getParsedBody')->andReturn(['name' => 'John', 'email' => '']);
        $psrRequest->shouldReceive('getQueryParams')->andReturn([]);
        Context::set(ServerRequestInterface::class, $psrRequest);
        $request = new Request();

        $this->assertTrue($request->anyFilled(['name', 'email']));
        $this->assertFalse($request->anyFilled(['age', 'email']));
    }

    public function testBoolean()
    {
        $psrRequest = Mockery::mock(ServerRequestPlusInterface::class);
        $psrRequest->shouldReceive('getParsedBody')->andReturn(['active' => '1', 'inactive' => '0']);
        $psrRequest->shouldReceive('getQueryParams')->andReturn([]);
        Context::set(ServerRequestInterface::class, $psrRequest);
        $request = new Request();

        $this->assertTrue($request->boolean('active'));
        $this->assertFalse($request->boolean('inactive'));
        $this->assertFalse($request->boolean('nonexistent', false));
    }

    public function testCollect()
    {
        $psrRequest = Mockery::mock(ServerRequestPlusInterface::class);
        $psrRequest->shouldReceive('getParsedBody')->andReturn(['name' => 'John', 'age' => 30]);
        $psrRequest->shouldReceive('getQueryParams')->andReturn([]);
        Context::set(ServerRequestInterface::class, $psrRequest);
        $request = new Request();

        $collection = $request->collect();
        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertEquals(['name' => 'John', 'age' => 30], $collection->all());

        $nameCollection = $request->collect('name');
        $this->assertEquals(['John'], $nameCollection->all());
    }

    public function testDate()
    {
        $psrRequest = Mockery::mock(ServerRequestPlusInterface::class);
        $psrRequest->shouldReceive('getParsedBody')->andReturn(['created_at' => '2023-05-15']);
        $psrRequest->shouldReceive('getQueryParams')->andReturn([]);
        Context::set(ServerRequestInterface::class, $psrRequest);
        $request = new Request();

        $date = $request->date('created_at');
        $this->assertInstanceOf(Carbon::class, $date);
        $this->assertEquals('2023-05-15', $date->toDateString());

        $formattedDate = $request->date('created_at', 'Y-m-d');
        $this->assertEquals('2023-05-15', $formattedDate->format('Y-m-d'));
    }

    public function testEnum()
    {
        $psrRequest = Mockery::mock(ServerRequestPlusInterface::class);
        $psrRequest->shouldReceive('getParsedBody')->andReturn(['status' => 'active']);
        $psrRequest->shouldReceive('getQueryParams')->andReturn([]);
        Context::set(ServerRequestInterface::class, $psrRequest);
        $request = new Request();

        $enum = $request->enum('status', StatusEnum::class);
        $this->assertInstanceOf(StatusEnum::class, $enum);
        $this->assertEquals(StatusEnum::ACTIVE, $enum);
    }

    public function testExcept()
    {
        $psrRequest = Mockery::mock(ServerRequestPlusInterface::class);
        $psrRequest->shouldReceive('getParsedBody')->andReturn(['name' => 'John', 'age' => 30, 'email' => 'john@example.com']);
        $psrRequest->shouldReceive('getQueryParams')->andReturn([]);
        Context::set(ServerRequestInterface::class, $psrRequest);
        $request = new Request();

        $result = $request->except(['age']);
        $this->assertEquals(['name' => 'John', 'email' => 'john@example.com'], $result);
    }

    public function testExists()
    {
        $psrRequest = Mockery::mock(ServerRequestPlusInterface::class);
        $psrRequest->shouldReceive('getParsedBody')->andReturn(['name' => 'John']);
        $psrRequest->shouldReceive('getQueryParams')->andReturn([]);
        Context::set(ServerRequestInterface::class, $psrRequest);
        $request = new Request();

        $this->assertTrue($request->exists('name'));
        $this->assertFalse($request->exists('age'));
    }

    public function testFilled()
    {
        $psrRequest = Mockery::mock(ServerRequestPlusInterface::class);
        $psrRequest->shouldReceive('getParsedBody')->andReturn(['name' => 'John', 'email' => '']);
        $psrRequest->shouldReceive('getQueryParams')->andReturn([]);
        Context::set(ServerRequestInterface::class, $psrRequest);
        $request = new Request();

        $this->assertTrue($request->filled('name'));
        $this->assertFalse($request->filled('email'));
        $this->assertFalse($request->filled('age'));
    }

    public function testFloat()
    {
        $psrRequest = Mockery::mock(ServerRequestPlusInterface::class);
        $psrRequest->shouldReceive('getParsedBody')->andReturn(['price' => '10.5']);
        $psrRequest->shouldReceive('getQueryParams')->andReturn([]);
        Context::set(ServerRequestInterface::class, $psrRequest);
        $request = new Request();

        $this->assertEquals(10.5, $request->float('price'));
        $this->assertEquals(0.0, $request->float('nonexistent'));
    }

    public function testString()
    {
        $psrRequest = Mockery::mock(ServerRequestPlusInterface::class);
        $psrRequest->shouldReceive('getParsedBody')->andReturn(['name' => 'John']);
        $psrRequest->shouldReceive('getQueryParams')->andReturn([]);
        Context::set(ServerRequestInterface::class, $psrRequest);
        $request = new Request();

        $result = $request->string('name');
        $this->assertInstanceOf(Stringable::class, $result);
        $this->assertEquals('John', $result->toString());
    }

    public function testHasAny()
    {
        $psrRequest = Mockery::mock(ServerRequestPlusInterface::class);
        $psrRequest->shouldReceive('getParsedBody')->andReturn(['name' => 'John', 'age' => 30]);
        $psrRequest->shouldReceive('getQueryParams')->andReturn([]);
        Context::set(ServerRequestInterface::class, $psrRequest);
        $request = new Request();

        $this->assertTrue($request->hasAny(['name', 'email']));
        $this->assertFalse($request->hasAny(['email', 'phone']));
    }

    public function testGetHost()
    {
        $psrRequest = Mockery::mock(ServerRequestPlusInterface::class);
        $psrRequest->shouldReceive('getHeader')->with('HOST')->andReturn(['example.com:8080']);
        Context::set(ServerRequestInterface::class, $psrRequest);
        $request = new Request();

        $this->assertEquals('example.com', $request->getHost());
    }

    public function testGetHttpHost()
    {
        $psrRequest = Mockery::mock(ServerRequestPlusInterface::class);
        $psrRequest->shouldReceive('getHeader')->with('HOST')->andReturn(['example.com:8080']);
        Context::set(ServerRequestInterface::class, $psrRequest);
        $request = new Request();

        $this->assertEquals('example.com:8080', $request->getHttpHost());
    }

    public function testGetPort()
    {
        $psrRequest = Mockery::mock(ServerRequestPlusInterface::class);
        $psrRequest->shouldReceive('getHeader')->with('HOST')->andReturn(['example.com:8080']);
        Context::set(ServerRequestInterface::class, $psrRequest);
        $request = new Request();

        $this->assertEquals(8080, $request->getPort());
    }

    public function testGetScheme()
    {
        $psrRequest = Mockery::mock(ServerRequestPlusInterface::class);
        $psrRequest->shouldReceive('getServerParams')
            ->with('HTTPS')
            ->andReturn(['on']);
        Context::set(ServerRequestInterface::class, $psrRequest);
        $request = new Request();

        $this->assertEquals('https', $request->getScheme());
    }

    public function testIsSecure()
    {
        $psrRequest = Mockery::mock(ServerRequestPlusInterface::class);
        $psrRequest->shouldReceive('getServerParams')
            ->with('HTTPS')
            ->andReturn(['on']);
        Context::set(ServerRequestInterface::class, $psrRequest);
        $request = new Request();

        $this->assertTrue($request->isSecure());
    }

    public function testInteger()
    {
        $psrRequest = Mockery::mock(ServerRequestPlusInterface::class);
        $psrRequest->shouldReceive('getParsedBody')->andReturn(['age' => '30']);
        $psrRequest->shouldReceive('getQueryParams')->andReturn([]);
        Context::set(ServerRequestInterface::class, $psrRequest);
        $request = new Request();

        $this->assertEquals(30, $request->integer('age'));
        $this->assertEquals(0, $request->integer('nonexistent'));
    }

    public function testIsEmptyString()
    {
        $psrRequest = Mockery::mock(ServerRequestPlusInterface::class);
        $psrRequest->shouldReceive('getParsedBody')->andReturn(['name' => '', 'age' => '30']);
        $psrRequest->shouldReceive('getQueryParams')->andReturn([]);
        Context::set(ServerRequestInterface::class, $psrRequest);
        $request = new Request();

        $this->assertTrue($request->isEmptyString('name'));
        $this->assertFalse($request->isEmptyString('age'));
    }

    public function testIsJson()
    {
        $psrRequest = Mockery::mock(ServerRequestPlusInterface::class);
        $psrRequest->shouldReceive('hasHeader')->with('CONTENT_TYPE')->andReturn(true);
        $psrRequest->shouldReceive('getHeaderLine')->with('CONTENT_TYPE')->andReturn('application/json');
        Context::set(ServerRequestInterface::class, $psrRequest);
        $request = new Request();

        $this->assertTrue($request->isJson());
    }

    public function testIsNotFilled()
    {
        $psrRequest = Mockery::mock(ServerRequestPlusInterface::class);
        $psrRequest->shouldReceive('getParsedBody')->andReturn(['name' => '', 'age' => '30']);
        $psrRequest->shouldReceive('getQueryParams')->andReturn([]);
        Context::set(ServerRequestInterface::class, $psrRequest);
        $request = new Request();

        $this->assertTrue($request->isNotFilled('name'));
        $this->assertFalse($request->isNotFilled('age'));
    }

    public function testKeys()
    {
        $psrRequest = Mockery::mock(ServerRequestPlusInterface::class);
        $psrRequest->shouldReceive('getParsedBody')->andReturn(['name' => 'John', 'age' => 30]);
        $psrRequest->shouldReceive('getQueryParams')->andReturn([]);
        $psrRequest->shouldReceive('getUploadedFiles')->andReturn([]);
        Context::set(ServerRequestInterface::class, $psrRequest);
        $request = new Request();

        $this->assertEquals(['name', 'age'], $request->keys());
    }

    public function testMerge()
    {
        Context::set('http.request.parsedData', ['name' => 'John']);
        $request = new Request();

        $newRequest = $request->merge(['age' => 30]);
        $this->assertEquals(['name' => 'John', 'age' => 30], $newRequest->all());
    }

    public function testReplace()
    {
        Context::set('http.request.parsedData', ['name' => 'John', 'age' => 30]);
        $request = new Request();

        $newRequest = $request->replace(['name' => 'Foo']);
        $this->assertEquals(['name' => 'Foo', 'age' => 30], $newRequest->all());
    }

    public function testMergeIfMissing()
    {
        $psrRequest = Mockery::mock(ServerRequestPlusInterface::class);
        $psrRequest->shouldReceive('getParsedBody')->andReturn(['name' => 'John']);
        $psrRequest->shouldReceive('getQueryParams')->andReturn([]);
        Context::set(ServerRequestInterface::class, $psrRequest);
        $request = new Request();

        $newRequest = $request->mergeIfMissing(['name' => 'Jane', 'age' => 30]);
        $this->assertEquals(['name' => 'John', 'age' => 30], $newRequest->all());
    }

    public function testMissing()
    {
        $psrRequest = Mockery::mock(ServerRequestPlusInterface::class);
        $psrRequest->shouldReceive('getParsedBody')->andReturn(['name' => 'John']);
        $psrRequest->shouldReceive('getQueryParams')->andReturn([]);
        Context::set(ServerRequestInterface::class, $psrRequest);
        $request = new Request();

        $this->assertTrue($request->missing('age'));
        $this->assertFalse($request->missing('name'));
    }

    public function testOnly()
    {
        $psrRequest = Mockery::mock(ServerRequestPlusInterface::class);
        $psrRequest->shouldReceive('getParsedBody')->andReturn(['name' => 'John', 'age' => 30, 'email' => 'john@example.com']);
        $psrRequest->shouldReceive('getQueryParams')->andReturn([]);
        Context::set(ServerRequestInterface::class, $psrRequest);
        $request = new Request();

        $result = $request->only(['name', 'age']);
        $this->assertEquals(['name' => 'John', 'age' => 30], $result);
    }

    public function testSchemeAndHttpHost()
    {
        $psrRequest = Mockery::mock(ServerRequestPlusInterface::class);
        $psrRequest->shouldReceive('getServerParams')->with('HTTPS')->andReturn(['on']);
        $psrRequest->shouldReceive('getHeader')->with('HOST')->andReturn(['example.com:8080']);
        Context::set(ServerRequestInterface::class, $psrRequest);
        $request = new Request();

        $this->assertSame('https://example.com:8080', $request->schemeAndHttpHost());
    }

    public function testExpectsJson()
    {
        $psrRequest = Mockery::mock(ServerRequestPlusInterface::class);
        $psrRequest->shouldReceive('hasHeader')->with('X-Requested-With')->andReturn(true);
        $psrRequest->shouldReceive('hasHeader')->with('X-PJAX')->andReturn(false);
        $psrRequest->shouldReceive('hasHeader')->with('Accept')->andReturn(false);
        $psrRequest->shouldReceive('getHeaderLine')->with('X-Requested-With')->andReturn('XMLHttpRequest');
        $psrRequest->shouldReceive('getHeaderLine')->with('Accept')->andReturn('application/json');
        Context::set(ServerRequestInterface::class, $psrRequest);
        $request = new Request();

        $this->assertTrue($request->expectsJson());
    }

    public function testWantsJson()
    {
        $psrRequest = Mockery::mock(ServerRequestPlusInterface::class);
        $psrRequest->shouldReceive('hasHeader')->with('Accept')->andReturn(true);
        $psrRequest->shouldReceive('getHeaderLine')->with('Accept')->andReturn('application/json');
        Context::set(ServerRequestInterface::class, $psrRequest);
        $request = new Request();

        $this->assertTrue($request->wantsJson());
    }

    public function testAccepts()
    {
        $psrRequest = Mockery::mock(ServerRequestPlusInterface::class);
        $psrRequest->shouldReceive('hasHeader')->with('Accept')->andReturn(true);
        $psrRequest->shouldReceive('getHeaderLine')->with('Accept')->andReturn('application/json');
        Context::set(ServerRequestInterface::class, $psrRequest);
        $request = new Request();

        $this->assertTrue($request->accepts('application/json'));
    }

    public function testPrefers()
    {
        $psrRequest = Mockery::mock(ServerRequestPlusInterface::class);
        $psrRequest->shouldReceive('hasHeader')->with('Accept')->andReturn(true);
        $psrRequest->shouldReceive('getHeaderLine')->with('Accept')->andReturn('application/json,text/html');
        Context::set(ServerRequestInterface::class, $psrRequest);
        $request = new Request();

        $this->assertSame('application/json', $request->prefers(['application/json', 'text/html']));
    }

    public function testAcceptsAnyContentType()
    {
        $psrRequest = Mockery::mock(ServerRequestPlusInterface::class);
        $psrRequest->shouldReceive('hasHeader')->with('Accept')->andReturn(true);
        $psrRequest->shouldReceive('getHeaderLine')->with('Accept')->andReturn('*/*');
        Context::set(ServerRequestInterface::class, $psrRequest);
        $request = new Request();

        $this->assertTrue($request->acceptsAnyContentType());
    }

    public function testAcceptsJson()
    {
        $psrRequest = Mockery::mock(ServerRequestPlusInterface::class);
        $psrRequest->shouldReceive('hasHeader')->with('Accept')->andReturn(true);
        $psrRequest->shouldReceive('getHeaderLine')->with('Accept')->andReturn('application/json');
        Context::set(ServerRequestInterface::class, $psrRequest);
        $request = new Request();

        $this->assertTrue($request->acceptsJson());
    }

    public function testAcceptsHtml()
    {
        $psrRequest = Mockery::mock(ServerRequestPlusInterface::class);
        $psrRequest->shouldReceive('hasHeader')->with('Accept')->andReturn(true);
        $psrRequest->shouldReceive('getHeaderLine')->with('Accept')->andReturn('text/html');
        Context::set(ServerRequestInterface::class, $psrRequest);
        $request = new Request();

        $this->assertTrue($request->acceptsHtml());
    }

    public function testWhenFilled()
    {
        $psrRequest = Mockery::mock(ServerRequestPlusInterface::class);
        $psrRequest->shouldReceive('getQueryParams')->andReturn([]);
        $psrRequest->shouldReceive('getParsedBody')->andReturn(['key' => 'value']);
        Context::set(ServerRequestInterface::class, $psrRequest);
        $request = new Request();

        $result = $request->whenFilled('key', function ($value) {
            return $value;
        });

        $this->assertSame('value', $result);

        $result = $request->whenFilled('foo', function ($value) {
            return $value;
        }, fn () => 'default');

        $this->assertSame('default', $result);
    }

    public function testWhenHas()
    {
        $psrRequest = Mockery::mock(ServerRequestPlusInterface::class);
        $psrRequest->shouldReceive('getQueryParams')->andReturn([]);
        $psrRequest->shouldReceive('getParsedBody')->andReturn(['key' => 'value']);
        Context::set(ServerRequestInterface::class, $psrRequest);
        $request = new Request();

        $result = $request->whenHas('key', function ($value) {
            return $value;
        });

        $this->assertSame('value', $result);

        $result = $request->whenHas('foo', function ($value) {
            return $value;
        }, fn () => 'default');

        $this->assertSame('default', $result);
    }

    public function testGetClientIp()
    {
        $psrRequest = Mockery::mock(ServerRequestPlusInterface::class);
        $psrRequest->shouldReceive('getHeaderLine')->with('x-real-ip')->andReturn('127.0.0.1');
        Context::set(ServerRequestInterface::class, $psrRequest);
        $request = new Request();

        $this->assertSame('127.0.0.1', $request->getClientIp());
    }

    public function testIp()
    {
        $psrRequest = Mockery::mock(ServerRequestPlusInterface::class);
        $psrRequest->shouldReceive('getHeaderLine')->with('x-real-ip')->andReturn('127.0.0.1');
        Context::set(ServerRequestInterface::class, $psrRequest);
        $request = new Request();

        $this->assertSame('127.0.0.1', $request->ip());
    }

    public function testFullUrlWithQuery()
    {
        $psrRequest = Mockery::mock(ServerRequestPlusInterface::class);
        $psrRequest->shouldReceive('getQueryParams')->andReturn(['key' => 'value']);
        $psrRequest->shouldReceive('getServerParams')->andReturn([]);
        $psrRequest->shouldReceive('getUri')->andReturn(
            new Uri('http://localhost/path')
        );

        Context::set(ServerRequestInterface::class, $psrRequest);
        $request = new Request();

        $this->assertSame('http://localhost/path?key=value&newkey=newvalue', $request->fullUrlWithQuery(['newkey' => 'newvalue']));
    }

    public function testFullUrlWithoutQuery()
    {
        $psrRequest = Mockery::mock(ServerRequestPlusInterface::class);
        $psrRequest->shouldReceive('getQueryParams')->andReturn(['key' => 'value', 'foo' => 'bar']);
        $psrRequest->shouldReceive('getServerParams')->andReturn([]);
        $psrRequest->shouldReceive('getUri')->andReturn(
            new Uri('http://localhost/path')
        );
        Context::set(ServerRequestInterface::class, $psrRequest);
        $request = new Request();

        $this->assertSame('http://localhost/path?key=value', $request->fullUrlWithoutQuery(['foo']));
    }

    public function testMethod()
    {
        $psrRequest = Mockery::mock(ServerRequestPlusInterface::class);
        $psrRequest->shouldReceive('getMethod')->andReturn('GET');
        Context::set(ServerRequestInterface::class, $psrRequest);
        $request = new Request();

        $this->assertSame('GET', $request->method());
    }

    public function testBearerToken()
    {
        $psrRequest = Mockery::mock(ServerRequestPlusInterface::class);
        $psrRequest->shouldReceive('hasHeader')->with('Authorization')->andReturn(true);
        $psrRequest->shouldReceive('getHeaderLine')->with('Authorization')->andReturn('Bearer token');
        Context::set(ServerRequestInterface::class, $psrRequest);
        $request = new Request();

        $this->assertSame('token', $request->bearerToken());
    }

    public function testGetAcceptableContentTypes()
    {
        $psrRequest = Mockery::mock(ServerRequestPlusInterface::class);
        $psrRequest->shouldReceive('hasHeader')->with('Accept')->andReturn(true);
        $psrRequest->shouldReceive('getHeaderLine')->with('Accept')->andReturn('application/json,text/html');
        Context::set(ServerRequestInterface::class, $psrRequest);
        $request = new Request();

        $this->assertSame(['application/json', 'text/html'], $request->getAcceptableContentTypes());
    }

    public function testGetMimeType()
    {
        $psrRequest = Mockery::mock(ServerRequestPlusInterface::class);
        Context::set(ServerRequestInterface::class, $psrRequest);
        $request = new Request();

        $this->assertSame('application/json', $request->getMimeType('json'));
    }

    public function testGetMimeTypes()
    {
        $psrRequest = Mockery::mock(ServerRequestPlusInterface::class);
        Context::set(ServerRequestInterface::class, $psrRequest);
        $request = new Request();

        $this->assertSame(['application/json', 'application/x-json'], $request->getMimeTypes('json'));
    }

    public function testIsXmlHttpRequest()
    {
        $psrRequest = Mockery::mock(ServerRequestPlusInterface::class);
        $psrRequest->shouldReceive('hasHeader')->with('X-Requested-With')->andReturn(true);
        $psrRequest->shouldReceive('getHeaderLine')->with('X-Requested-With')->andReturn('XMLHttpRequest');
        Context::set(ServerRequestInterface::class, $psrRequest);
        $request = new Request();

        $this->assertTrue($request->isXmlHttpRequest());
    }

    public function testAjax()
    {
        $psrRequest = Mockery::mock(ServerRequestPlusInterface::class);
        $psrRequest->shouldReceive('hasHeader')->with('X-Requested-With')->andReturn(true);
        $psrRequest->shouldReceive('getHeaderLine')->with('X-Requested-With')->andReturn('XMLHttpRequest');
        Context::set(ServerRequestInterface::class, $psrRequest);
        $request = new Request();

        $this->assertTrue($request->ajax());
    }

    public function testPjax()
    {
        $psrRequest = Mockery::mock(ServerRequestPlusInterface::class);
        $psrRequest->shouldReceive('hasHeader')->with('X-PJAX')->andReturn(true);
        $psrRequest->shouldReceive('getHeaderLine')->with('X-PJAX')->andReturn('true');
        Context::set(ServerRequestInterface::class, $psrRequest);
        $request = new Request();

        $this->assertTrue($request->pjax());
    }

    public function testGetPsr7Request()
    {
        $psrRequest = Mockery::mock(ServerRequestPlusInterface::class);
        Context::set(ServerRequestInterface::class, $psrRequest);
        $request = new Request();

        $this->assertSame($psrRequest, $request->getPsr7Request());
    }
}

enum StatusEnum: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}