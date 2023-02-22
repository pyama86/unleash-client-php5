<?php

namespace Unleash\Strategy;

class GradualRolloutSessionIdStrategyHandler extends AbstractStrategyHandler
{
    const SESSION_ID = 'sessionid';
    public function __construct($rolloutStrategyHandler)
    {
        $this->rolloutStrategyHandler = $rolloutStrategyHandler;
    }

    public function isEnabled($strategy, $context)
    {
        $transformedStrategy = new DefaultStrategy(
            $this->getStrategyName(),
            [
                'stickiness' => SESSION_ID,
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
