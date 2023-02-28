<?php

namespace Unleash\Strategy;
use Unleash\Enum\Stickiness;

class GradualRolloutStrategyHandler extends AbstractStrategyHandler
{
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
        $groupId = !is_null($this->findParameter('groupId', $strategy)) ? $this->findParameter('groupId', $strategy) : '';
        if (!$rollout = $this->findParameter('rollout', $strategy)) {
            return false;
        }
        switch(strtolower($stickiness)) {
        case Stickiness::E_DEFAULT:
            $id = !is_null($context->getCurrentUserId()) ? $context->getCurrentUserId() : ($context->getSessionId() ? $context->getSessionId() : rand(1, 100000));
            break;
        case Stickiness::RANDOM:
           $id = rand(1, 100000);
            break;
        case Stickiness::USER_ID:
           $id = $context->getCurrentUserId();
            break;
        case Stickiness::SESSION_ID:
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
