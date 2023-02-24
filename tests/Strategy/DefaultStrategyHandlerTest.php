<?php

namespace Unleash\Client\Tests\Strategy;

use PHPUnit\Framework\TestCase;
use Unleash\Configuration\UnleashContext;
use Unleash\Enum\ConstraintOperator;
use Unleash\Strategy\DefaultStrategyHandler;
use Unleash\Strategy\DefaultStrategy;

final class DefaultStrategyHandlerTest extends TestCase
{
    public function testSupports()
    {
        $instance = new DefaultStrategyHandler();
        self::assertTrue($instance->supports(new DefaultStrategy('default', [])));
        self::assertFalse($instance->supports(new DefaultStrategy('flexibleRollout', [])));
        self::assertFalse($instance->supports(new DefaultStrategy('remoteAddress', [])));
        self::assertFalse($instance->supports(new DefaultStrategy('userWithId', [])));
        self::assertFalse($instance->supports(new DefaultStrategy('nonexistent', [])));
    }

    public function testIsEnabled()
    {
        $instance = new DefaultStrategyHandler();
        self::assertTrue($instance->isEnabled(new DefaultStrategy('whatever', []), new UnleashContext()));

        self::assertTrue($instance->isEnabled(
            $strategy,
            (new UnleashContext())->setCustomProperty('something', 'test')
        ));
    }
}
