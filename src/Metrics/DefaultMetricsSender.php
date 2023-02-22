<?php

namespace Unleash\Metrics;

class DefaultMetricsSender
{
    public function __construct(
        $httpClient,
        $config
    ) {
        $this->httpClient = $httpClient;
        $this->config = $config;
    }

    public function sendMetrics($bucket)
    {
        if (!$this->config->isMetricsEnabled() || !$this->config->isFetchingEnabled()) {
            return;
        }

        $this->httpClient->sendMetrics($bucket);
    }
}
