<?php

namespace Unleash\Strategy;

use Unleash\Configuration\Context;
use Unleash\Enum\Stickiness;

final class GradualRolloutRandomStrategyHandler extends AbstractStrategyHandler
{
    public function __construct($rolloutStrategyHandler)
    {
        $this->rolloutStrategyHandler = $rolloutStrategyHandler;
    }

    public function isEnabled($strategy, $context)
    {
        $transformedStrategy = new DefaultStrategy(
            $this->getStrategyName(),
            [
                'stickiness' => Stickiness::RANDOM,
                'groupId' => !is_null($strategy->getParameters()['groupId']) ? $strategy->getParameters()['groupId'] : '',
                'rollout' => $strategy->getParameters()['percentage'],
            ]
        );

        return $this->rolloutStrategyHandler->isEnabled($transformedStrategy, $context);
    }

    public function getStrategyName()
    {
        return 'gradualRolloutRandom';
    }
}
