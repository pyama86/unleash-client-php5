<?php

namespace Unleash\Strategy;

use Unleash\Configuration\Context;

final class ApplicationHostnameStrategyHandler extends AbstractStrategyHandler
{
    public function getStrategyName()
    {
        return 'applicationHostname';
    }

    public function isEnabled($strategy, $context)
    {
        if (!$hostnames = $this->findParameter('hostNames', $strategy)) {
            return false;
        }

        $hostnames = array_map('trim', explode(',', $hostnames));
        $enabled = in_array($context->getHostname(), $hostnames, true);

        if (!$enabled) {
            return false;
        }

        return true;
    }
}
