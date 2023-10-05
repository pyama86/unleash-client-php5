<?php

namespace Unleash\Metrics;
use DateTime;
use Unleash\Enum\CacheKey;
class DefaultMetricsHandler
{
    public function __construct(
        $metricsSender,
        $config
    ) {
        $this->metricsSender = $metricsSender;
        $this->config = $config;
    }

    public function handleMetrics($feature, $successful, $variant = null)
    {
        if (!$this->config->isMetricsEnabled()) {
            return;
        }

        $bucket = $this->getOrCreateBucket();
        $bucket->addToggle(new MetricsBucketToggle($feature, $successful, $variant));
        if ($this->shouldSend($bucket)) {
            $this->send($bucket);
        } else {
            $this->store($bucket);
        }
    }

    private function getOrCreateBucket()
    {
        $cache = $this->config->getCache();
        $bucket = null;
        if ($cache->has(CacheKey::METRICS_BUCKET)) {
            $bucket = $cache->get(CacheKey::METRICS_BUCKET);
            assert($bucket instanceof MetricsBucket || $bucket === null);
        }

        if (is_null($bucket)) {
            $bucket = new MetricsBucket(new DateTime());
        }

        return $bucket;
    }

    private function shouldSend($bucket)
    {
        $bucketStartDate = $bucket->getStartDate();
        $nowMilliseconds = (int) (microtime(true) * 1000);
        $startDateMilliseconds = (int) (
            ($bucketStartDate->getTimestamp() + (int) $bucketStartDate->format('v') / 1000) * 1000
        );
        $diff = $nowMilliseconds - $startDateMilliseconds;

        return $diff >= $this->config->getMetricsInterval();
    }

    private function send($bucket)
    {
        $cache = $this->config->getCache();
        try {
            $cache->delete(CacheKey::METRICS_BUCKET);
        } catch (\Exception $e) {
            error_log('Failed to delete metrics bucket: ' . $e->getMessage());
        }
        $bucket->setEndDate(new DateTime());
        try {
            $this->metricsSender->sendMetrics($bucket);
        } catch (\Exception $e) {
            error_log('Failed to send metrics: ' . $e->getMessage());
        }
    }

    private function store($bucket)
    {
        $cache = $this->config->getCache();
        try {
            $cache->set(CacheKey::METRICS_BUCKET, $bucket);
        } catch (\Exception $e) {
            error_log('Failed to store metrics bucket: ' . $e->getMessage());
        }
    }
}
