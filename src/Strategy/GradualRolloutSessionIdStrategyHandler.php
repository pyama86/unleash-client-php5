<?php

namespace Unleash\Strategy;
use Unleash\Enum\Stickiness;

class GradualRolloutSessionIdStrategyHandler extends AbstractStrategyHandler
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
                'stickiness' => Stickiness::SESSION_ID,
                'groupId' => $strategy->getParameters()['groupId'],
                'rollout' => $strategy->getParameters()['percentage'],
            ]
        );

        return $this->rolloutStrategyHandler->isEnabled($transformedStrategy, $context);
    }

    public function getStrategyName()
    {
        return 'gradualRolloutSessionId';
    }
}
