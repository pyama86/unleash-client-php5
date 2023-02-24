<?php

namespace Unleash\Client\Tests\Strategy;

use PHPUnit\Framework\TestCase;
use Unleash\Configuration\UnleashContext;
use Unleash\Enum\ConstraintOperator;
use Unleash\Exception\MissingArgumentException;
use Unleash\Strategy\IpAddressStrategyHandler;
use Unleash\Strategy\DefaultStrategy;

final class IpAddressStrategyHandlerTest extends TestCase
{
    public function testSupports()
    {
        $instance = new IpAddressStrategyHandler();
        self::assertFalse($instance->supports(new DefaultStrategy('default', [])));
        self::assertFalse($instance->supports(new DefaultStrategy('flexibleRollout', [])));
        self::assertTrue($instance->supports(new DefaultStrategy('remoteAddress', [])));
        self::assertFalse($instance->supports(new DefaultStrategy('userWithId', [])));
        self::assertFalse($instance->supports(new DefaultStrategy('nonexistent', [])));
    }

    public function testIsEnabled()
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.0.1';
        $context = new UnleashContext(null, '127.0.0.1');

        $instance = new IpAddressStrategyHandler();

        self::assertFalse($instance->isEnabled(new DefaultStrategy('remoteAddress', [
            'IPs' => '127.0.0.1',
        ]), new UnleashContext()));
        self::assertTrue($instance->isEnabled(new DefaultStrategy('remoteAddress', [
            'IPs' => '192.168.0.1',
        ]), new UnleashContext()));

        self::assertTrue($instance->isEnabled(new DefaultStrategy('remoteAddress', [
            'IPs' => '127.0.0.1',
        ]), $context));
        self::assertFalse($instance->isEnabled(new DefaultStrategy('remoteAddress', [
            'IPs' => '192.168.0.1',
        ]), $context));

        $strategy = new DefaultStrategy('whatever', [
            'IPs' => '192.168.0.1',
        ], []);
        self::assertTrue($instance->isEnabled(
            $strategy,
            (new UnleashContext())->setCustomProperty('something', 'test')
        ));

        $this->expectException(MissingArgumentException::class);
        $instance->isEnabled(new DefaultStrategy('remoteAddress', []), $context);
    }

    public function testEmptyIpAddresses()
    {
        $context = new UnleashContext(null, '127.0.0.1');
        $instance = new IpAddressStrategyHandler();

        $this->expectException(MissingArgumentException::class);
        $instance->isEnabled(new DefaultStrategy('', [
            'IPs' => '',
        ]), $context);
    }
}
