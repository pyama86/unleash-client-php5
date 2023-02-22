<?php

namespace Unleash\Feature;

class DefaultFeature
{
    public function __construct(
        $name,
        $enabled,
        $strategies,
        $variants = [],
        $impressionData = false
    ) {
        $this->name = $name;
        $this->enabled = $enabled;
        $this->strategies = $strategies;
        $this->variants = $variants;
        $this->impressionData = $impressionData;
    }

    public function getName()
    {
        return $this->name;
    }

    public function isEnabled()
    {
        return $this->enabled;
    }

    public function getStrategies()
    {
        return $this->strategies;
    }

    public function getVariants()
    {
        return $this->variants;
    }

    public function hasImpressionData()
    {
        return $this->impressionData;
    }
}
