<?php

namespace Unleash\Tests\Strategy;

use PHPUnit\Framework\TestCase;
use Unleash\Configuration\UnleashContext;
use Unleash\Enum\ConstraintOperator;
use Unleash\Enum\Stickiness;
use Unleash\Stickiness\MurmurHashCalculator;
use Unleash\Strategy\GradualRolloutStrategyHandler;
use Unleash\Strategy\DefaultStrategy;

final class GradualRolloutStrategyHandlerTest extends TestCase
{
    private $instance;

    protected function setUp()
    {
        $this->instance = new GradualRolloutStrategyHandler(new MurmurHashCalculator());
    }

    public function testSupports()
    {
        self::assertFalse($this->instance->supports(new DefaultStrategy('default', [])));
        self::assertTrue($this->instance->supports(new DefaultStrategy('flexibleRollout', [])));
        self::assertFalse($this->instance->supports(new DefaultStrategy('remoteAddress', [])));
        self::assertFalse($this->instance->supports(new DefaultStrategy('userWithId', [])));
        self::assertFalse($this->instance->supports(new DefaultStrategy('nonexistent', [])));
    }

    public function testIsEnabled()
    {
        // no exception should be thrown
        $this->instance->isEnabled($this->createStrategy(), new UnleashContext());

        self::assertFalse($this->instance->isEnabled(new DefaultStrategy('flexibleRollout', [
            'groupId' => 'test',
            'rollout' => 5,
        ]), new UnleashContext()));

        self::assertFalse($this->instance->isEnabled(new DefaultStrategy('flexibleRollout', [
            'groupId' => 'test',
            'stickiness' => Stickiness::RANDOM,
        ]), new UnleashContext()));
        self::assertFalse($this->instance->isEnabled(new DefaultStrategy('flexibleRollout', [
            'groupId' => 'test',
            'stickiness' => 'unknown-stickiness',
            'rollout' => 5,
        ]), new UnleashContext()));

        self::assertFalse($this->instance->isEnabled($this->createStrategy(50), new UnleashContext('123')));
        self::assertFalse($this->instance->isEnabled($this->createStrategy(50), new UnleashContext('456')));
        self::assertTrue($this->instance->isEnabled($this->createStrategy(50), new UnleashContext('634')));
        self::assertFalse(
            $this->instance->isEnabled(
                $this->createStrategy(50),
                new UnleashContext('123', null, '634')
            )
        );
        self::assertFalse(
            $this->instance->isEnabled(
                $this->createStrategy(50),
                new UnleashContext('456', null, '634')
            )
        );
        self::assertTrue(
            $this->instance->isEnabled(
                $this->createStrategy(50),
                new UnleashContext('634', null, '123')
            )
        );

        self::assertFalse($this->instance->isEnabled(
            $this->createStrategy(100, Stickiness::USER_ID),
            new UnleashContext()
        ));

        self::assertFalse($this->instance->isEnabled(
            $this->createStrategy(100, Stickiness::USER_ID),
            new UnleashContext(null, null, 'test')
        ));

        self::assertFalse(
            $this->instance->isEnabled(
                $this->createStrategy(50, Stickiness::USER_ID),
                new UnleashContext('456')
            )
        );
        self::assertTrue(
            $this->instance->isEnabled(
                $this->createStrategy(50, Stickiness::USER_ID),
                new UnleashContext('634')
            )
        );

        self::assertFalse($this->instance->isEnabled(
            $this->createStrategy(100, Stickiness::SESSION_ID),
            new UnleashContext()
        ));

        self::assertFalse($this->instance->isEnabled(
            $this->createStrategy(100, Stickiness::SESSION_ID),
            new UnleashContext('test')
        ));

        self::assertFalse(
            $this->instance->isEnabled(
                $this->createStrategy(50, Stickiness::SESSION_ID),
                new UnleashContext(null, null, '456')
            )
        );
        self::assertTrue(
            $this->instance->isEnabled(
                $this->createStrategy(50, Stickiness::SESSION_ID),
                new UnleashContext(null, null, '634')
            )
        );

        $this->instance->isEnabled($this->createStrategy(100, Stickiness::RANDOM), new UnleashContext());
    }

    private function createStrategy(
        $percentage = 100,
        #[ExpectedValues(valuesFromClass: Stickiness::class)]
        $stickiness = Stickiness::E_DEFAULT,
        $constraints = []
    ) {
        return new DefaultStrategy('flexibleRollout', [
            'stickiness' => $stickiness,
            'groupId'=> 'default',
            'rollout' => $percentage
        ], $constraints);
    }
}
