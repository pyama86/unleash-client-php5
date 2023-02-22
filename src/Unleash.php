<?php
namespace Unleash;
use Unleash\Client\Repository;
class Unleash
{
    public function __construct(
        $config,
        $httpClient,
        $strategyHandlers
    ) {
        $this->httpClient = $httpClient;
        $this->config = $config;
        $this->repository = new Repository($httpClient, $config);
        $this->strategyHandlers = $strategyHandlers;
        if ($this->config->isAutoRegistrationEnabled()) {
            $this->register();
        }
    }

    public function isEnabled($featureName, $context = null, $default = false)
    {
        $feature = $this->repository->findFeature($featureName);
        if ($feature === null) {
            return $default;
        }

        if (!$feature->isEnabled()) {
            return false;
        }

        $strategies = $feature->getStrategies();
        if (!is_array($strategies)) {
            $strategies = iterator_to_array($strategies);
        }

        $handlersFound = false;
        foreach ($strategies as $strategy) {
            $handlers = $this->findStrategyHandlers($strategy);
            if (!count($handlers)) {
                continue;
            }
            $handlersFound = true;
            foreach ($handlers as $handler) {
                if ($handler->isEnabled($strategy, $context)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function findStrategyHandlers($strategy)
    {
        $handlers = [];
        foreach ($this->strategyHandlers as $strategyHandler) {
            if ($strategyHandler->supports($strategy)) {
                $handlers[] = $strategyHandler;
            }
        }

        return $handlers;
    }
    public function register()
    {
        return $this->httpClient->register();
    }
}
