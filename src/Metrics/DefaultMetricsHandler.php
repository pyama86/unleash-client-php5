<?php

namespace Unleash\Metrics;
use DateTimeImmutable;
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
            $bucket = new MetricsBucket(new DateTimeImmutable());
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
        $bucket->setEndDate(new DateTimeImmutable());
        $this->metricsSender->sendMetrics($bucket);
        $cache = $this->config->getCache();
        if ($cache->has(CacheKey::METRICS_BUCKET)) {
            $cache->delete(CacheKey::METRICS_BUCKET);
        }
    }

    private function store($bucket)
    {
        $cache = $this->config->getCache();
        $cache->set(CacheKey::METRICS_BUCKET, $bucket);
    }
}
