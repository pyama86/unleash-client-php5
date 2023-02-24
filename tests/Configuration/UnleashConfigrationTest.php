<?php

namespace Unleash\Tests\Configuration;

use LogicException;
use PHPUnit\Framework\TestCase;
use Unleash\Configuration\UnleashConfiguration;
use Unleash\Configuration\UnleashContext;

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

    public function testGetDefaultContext()
    {
        $instance = new UnleashConfiguration('', '', '');
        self::assertEquals('Unleash\Configuration\UnleashContext', get_class($instance->getDefaultContext()));
    }

    public function testGetStaleCache()
    {
        // test that stale cache falls back to normal cache adapter
        $cache = 'dummy';

        $instance = (new UnleashConfiguration('', '', ''))
            ->setCache($cache);

        self::assertSame($cache, $instance->getCache());
        self::assertSame($cache, $instance->getStaleCache());

        $cache1 = 'dummy1';
        $cache2 = 'dummy2';

        $instance = (new UnleashConfiguration('', '', ''))
            ->setCache($cache1)
            ->setStaleCache($cache2)
        ;

        self::assertSame($cache1, $instance->getCache());
        self::assertSame($cache2, $instance->getStaleCache());
    }
}
