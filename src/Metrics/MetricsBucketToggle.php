<?php

namespace Unleash\Metrics;

class MetricsBucketToggle
{
    public function __construct(
        $feature,
        $success,
        $variant = null
    ) {
        $this->feature = $feature;
        $this->success = $success;
        $this->variant = $variant;
    }

    public function getFeature()
    {
        return $this->feature;
    }

    public function isSuccess()
    {
        return $this->success;
    }

    public function getVariant()
    {
        return $this->variant;
    }
}
