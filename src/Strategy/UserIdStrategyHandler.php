<?php
namespace Unleash\Strategy;

final class UserIdStrategyHandler extends AbstractStrategyHandler
{
    public function isEnabled($strategy, $context)
    {
        if (!$userIds = $this->findParameter('userIds', $strategy)) {
            return false;
        }
        if ($context->getCurrentUserId() === null) {
            return false;
        }

        $userIds = array_map('trim', explode(',', $userIds));

        $enabled = in_array($context->getCurrentUserId(), $userIds, true);

        if (!$enabled) {
            return false;
        }
        return true;
    }

    public function getStrategyName()
    {
        return 'userWithId';
    }
}
