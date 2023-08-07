<?php

namespace SwooleTW\Hyperf\Tests\Cache;

use SwooleTW\Hyperf\Tests\Cache\Stub\InteractsWithRedis;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use SwooleTW\Hyperf\Cache\RedisStore;
use SwooleTW\Hyperf\Cache\Repository;

class RedisCacheIntegrationTest extends TestCase
{
    use InteractsWithRedis;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRedis();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->tearDownRedis();
        m::close();
    }

    public function testRedisCacheAddTwice()
    {
        $store = new RedisStore($this->factory);
        $repository = new Repository($store);
        $this->assertTrue($repository->add('k', 'v', 3600));
        $this->assertFalse($repository->add('k', 'v', 3600));
        $this->assertGreaterThan(3500, $this->redis->ttl('k'));
    }

    /**
     * Breaking change.
     */
    public function testRedisCacheAddFalse()
    {
        $store = new RedisStore($this->factory);
        $repository = new Repository($store);
        $repository->forever('k', false);
        $this->assertFalse($repository->add('k', 'v', 60));
        $this->assertEquals(-1, $this->redis->ttl('k'));
    }

    /**
     * Breaking change.
     */
    public function testRedisCacheAddNull()
    {
        $store = new RedisStore($this->factory);
        $repository = new Repository($store);
        $repository->forever('k', null);
        $this->assertFalse($repository->add('k', 'v', 60));
    }
}
