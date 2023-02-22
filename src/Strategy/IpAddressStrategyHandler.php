<?php

namespace Unleash\Strategy;

use Unleash\Helper\NetworkCalculator;
final class IpAddressStrategyHandler extends AbstractStrategyHandler
{
    public function isEnabled($strategy, $context)
    {
        if (!$ipAddresses = $this->findParameter('IPs', $strategy)) {
            throw new Exception("The remote server did not return 'IPs' config");
        }
        $ipAddresses = array_map('trim', explode(',', $ipAddresses));

        $enabled = false;
        $currentIpAddress = $context->getIpAddress();
        if ($currentIpAddress !== null) {
            foreach ($ipAddresses as $ipAddress) {
                try {
                    $calculator = NetworkCalculator::fromString($ipAddress);
                } catch (InvalidIpAddressException $e) {
                    continue;
                }
                if ($calculator->isInRange($currentIpAddress)) {
                    $enabled = true;
                    break;
                }
            }
        }

        if (!$enabled) {
            return false;
        }

        return true;
    }

    public function getStrategyName()
    {
        return 'remoteAddress';
    }
}
