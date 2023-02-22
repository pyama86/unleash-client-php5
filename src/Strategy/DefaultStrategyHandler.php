<?php

namespace Unleash\Strategy;


class DefaultStrategyHandler extends AbstractStrategyHandler

{
    public function isEnabled($strategy, $context)
    {
        return true;
    }

    public function getStrategyName()
    {
        return 'default';
    }
}
