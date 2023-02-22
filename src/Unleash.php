<?php
namespace Unleash;
use Unleash\Client\Repository;
use Unleash\Configuration\UnleashContext;
class Unleash
{
    public function __construct(
        $config,
        $httpClient,
        $strategyHandlers,
        $metricsHandler
    ) {
        $this->httpClient = $httpClient;
        $this->config = $config;
        $this->repository = new Repository($httpClient, $config);
        $this->strategyHandlers = $strategyHandlers;
        $this->metricsHandler = $metricsHandler;
        if ($this->config->isAutoRegistrationEnabled()) {
            $this->register();
        }
    }

    public function isEnabled($featureName, $context = null, $default = false)
    {
        if (is_null($context)) {
            $context = new UnleashContext();
        }
        $feature = $this->repository->findFeature($featureName);
        if ($feature === null) {
            return $default;
        }

        if (!$feature->isEnabled()) {
            $this->metricsHandler->handleMetrics($feature, false);
            return false;
        }

        $strategies = $feature->getStrategies();
        if (!is_array($strategies)) {
            $strategies = iterator_to_array($strategies);
        }

        if (!count($strategies)) {
            $this->metricsHandler->handleMetrics($feature, true);

            return true;
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
                    $this->metricsHandler->handleMetrics($feature, true);
                    return true;
                }
            }
        }

        $this->metricsHandler->handleMetrics($feature, false);
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
