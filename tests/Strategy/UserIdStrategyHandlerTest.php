<?php

namespace Unleash\Client\Tests\Strategy;

use PHPUnit\Framework\TestCase;
use Unleash\Configuration\UnleashContext;
use Unleash\Enum\ConstraintOperator;
use Unleash\Strategy\UserIdStrategyHandler;
use Unleash\Strategy\DefaultStrategy;

final class UserIdStrategyHandlerTest extends TestCase
{
    public function testSupports()
    {
        $instance = new UserIdStrategyHandler();
        self::assertFalse($instance->supports(new DefaultStrategy('default', [])));
        self::assertFalse($instance->supports(new DefaultStrategy('flexibleRollout', [])));
        self::assertFalse($instance->supports(new DefaultStrategy('remoteAddress', [])));
        self::assertTrue($instance->supports(new DefaultStrategy('userWithId', [])));
        self::assertFalse($instance->supports(new DefaultStrategy('nonexistent', [])));
    }

    public function testIsEnabled()
    {
        $instance = new UserIdStrategyHandler();
        $context = new UnleashContext('123');

        self::assertFalse($instance->isEnabled(new DefaultStrategy('userWithId', [
            'userIds' => '123',
        ]), new UnleashContext()));

        self::assertFalse($instance->isEnabled(new DefaultStrategy('userWithId', [
            'userIds' => '',
        ]), new UnleashContext()));

        self::assertTrue($instance->isEnabled(new DefaultStrategy('userWithId', [
            'userIds' => '123,456',
        ]), $context));
        self::assertFalse($instance->isEnabled(new DefaultStrategy('userWithId', [
            'userIds' => '789',
        ]), $context));

        $strategy = new DefaultStrategy('whatever', [
            'userIds' => '123',
        ], []);

        self::assertTrue($instance->isEnabled(
            $strategy,
            (new UnleashContext('123'))->setCustomProperty('something', 'test')
        ));
    }
}
