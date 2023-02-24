<?php

namespace Unleash\Tests\Configuration;

use LogicException;
use PHPUnit\Framework\TestCase;
use Unleash\Configuration\UnleashConfiguration;
use Unleash\Configuration\UnleashContext;
use Symfony\Component\Cache\Simple\NullCache;

final class UnleashConfigurationTest extends TestCase
{
    public function testConstructor()
    {
        $instance = new UnleashConfiguration('https://www.example.com/test', '', '');
        self::assertEquals('https://www.example.com/test/', $instance->getUrl());

        $context = new UnleashContext('147');
        $instance = new UnleashConfiguration(
            'https://www.example.com/test',
            '',
            '',
            null,
            0,
            0,
            false,
            [],
            false,
            $context
        );
        self::assertEquals($context->getCurrentUserId(), $instance->getDefaultContext()->getCurrentUserId());
    }

    public function testSetUrl()
    {
        $instance = new UnleashConfiguration('', '', '');
        $instance->setUrl('https://www.example.com/test');
        self::assertEquals('https://www.example.com/test/', $instance->getUrl());
    }

    public function testGetCache()
    {
        $instance = (new UnleashConfiguration('', '', ''))
            ->setCache($this->getCache());
        self::assertInstanceOf(NullCache::class, $instance->getCache());

        $instance = new UnleashConfiguration('', '', '');
        $this->expectException(LogicException::class);
        $instance->getCache();
    }

    public function testGetDefaultContext()
    {
        $instance = new UnleashConfiguration('', '', '');
        self::assertInstanceOf(UnleashContext::class, $instance->getDefaultContext());
    }

    public function testGetStaleCache()
    {
        // test that stale cache falls back to normal cache adapter
        $cache = $this->getCache();

        $instance = (new UnleashConfiguration('', '', ''))
            ->setCache($cache);

        self::assertSame($cache, $instance->getCache());
        self::assertSame($cache, $instance->getStaleCache());

        $cache1 = $this->getCache();
        $cache2 = $this->getCache();

        $instance = (new UnleashConfiguration('', '', ''))
            ->setCache($cache1)
            ->setStaleCache($cache2)
        ;

        self::assertSame($cache1, $instance->getCache());
        self::assertSame($cache2, $instance->getStaleCache());

        $instance = new UnleashConfiguration('', '', '');
        $this->expectException(LogicException::class);
        $instance->getStaleCache();
    }

    public function getCache()
    {
        return new NullCache();
    }


}
