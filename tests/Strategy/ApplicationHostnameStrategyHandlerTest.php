<?php

namespace Unleash\Client\Tests\Strategy;

use PHPUnit\Framework\TestCase;
use Unleash\Configuration\UnleashContext;
use Unleash\Enum\ConstraintOperator;
use Unleash\Strategy\ApplicationHostnameStrategyHandler;
use Unleash\Strategy\DefaultStrategy;

final class ApplicationHostnameStrategyHandlerTest extends TestCase
{
    /**
     * @var ApplicationHostnameStrategyHandler
     */
    private $instance;

    protected function setUp()
    {
        $this->instance = new ApplicationHostnameStrategyHandler();
    }

    public function testSupports()
    {
        self::assertFalse($this->instance->supports(new DefaultStrategy('default', [])));
        self::assertFalse($this->instance->supports(new DefaultStrategy('flexibleRollout', [])));
        self::assertFalse($this->instance->supports(new DefaultStrategy('remoteAddress', [])));
        self::assertFalse($this->instance->supports(new DefaultStrategy('userWithId', [])));
        self::assertFalse($this->instance->supports(new DefaultStrategy('nonexistent', [])));
        self::assertTrue($this->instance->supports(new DefaultStrategy('applicationHostname', [])));
    }

    public function testIsEnabled()
    {
        self::assertFalse($this->instance->isEnabled(
            new DefaultStrategy('applicationHostname', []),
            new UnleashContext()
        ));

        self::assertFalse($this->instance->isEnabled(
            new DefaultStrategy('applicationHostname', [
                'hostNames' => 'test1,test2',
            ]),
            new UnleashContext()
        ));

        self::assertTrue($this->instance->isEnabled(
            new DefaultStrategy('applicationHostname', [
                'hostNames' => 'test1,test2',
            ]),
            (new UnleashContext())->setHostname('test1')
        ));
        self::assertTrue($this->instance->isEnabled(
            new DefaultStrategy('applicationHostname', [
                'hostNames' => 'test1,test2',
            ]),
            (new UnleashContext())->setHostname('test2')
        ));

        self::assertFalse($this->instance->isEnabled(
            new DefaultStrategy('applicationHostname', [
                'hostNames' => 'test1,test2',
            ]),
            (new UnleashContext())->setHostname('test3')
        ));

        $currentHostname = (new UnleashContext())->getHostname();
        self::assertTrue(
            $this->instance->isEnabled(
                new DefaultStrategy('applicationHostname', [
                    'hostNames' => $currentHostname,
                ]),
                new UnleashContext()
            )
        );
    }
}
