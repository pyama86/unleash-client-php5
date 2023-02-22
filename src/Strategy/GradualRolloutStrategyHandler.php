<?php

namespace Unleash\Strategy;

class GradualRolloutStrategyHandler extends AbstractStrategyHandler
{
    const USER_ID = 'userid';
    const SESSION_ID = 'sessionid';
    const RANDOM = 'random';

    public function __construct(
        $stickinessCalculator
    ) {
        $this->stickinessCalculator = $stickinessCalculator;
    }

    public function isEnabled($strategy, $context)
    {
        if (!$stickiness = $this->findParameter('stickiness', $strategy)) {
            return false;
        }
        $groupId = $this->findParameter('groupId', $strategy) ? $this->findParameter('groupId', $strategy) : '';
        if (!$rollout = $this->findParameter('rollout', $strategy)) {
            return false;
        }

        switch(strtolower($stickiness)) {
        case 'default':
            $id = $context->getCurrentUserId() ? $context->getCurrentUserId() : $context->getSessionId() ? $context->getSessionId() : rand(1, 100000);
            break;
        case RANDOM:
           $id = rand(1, 100000);
            break;
        case USER_ID: 
           $id = $context->getCurrentUserId();
            break;
        case SESSION_ID:
           $id = $context->getSessionId();
            break;
        default:
           $id = $context->findContextValue($stickiness);
            break;
        };
        if ($id === null) {
            return false;
        }

        $normalized = $this->stickinessCalculator->calculate((string) $id, $groupId);

        $enabled = $normalized <= (int) $rollout;

        if (!$enabled) {
            return false;
        }

        return true;
    }

    public function getStrategyName()
    {
        return 'flexibleRollout';
    }
}
