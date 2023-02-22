<?php

namespace Unleash\Strategy;
abstract class AbstractStrategyHandler
{
    public function supports($strategy)
    {
        return $strategy->getName() === $this->getStrategyName();
    }

    protected function findParameter($parameter, $strategy)
    {
        $parameters = $strategy->getParameters();

        return $parameters[$parameter];
    }
}
