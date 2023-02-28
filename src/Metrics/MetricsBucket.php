<?php

namespace Unleash\Metrics;

use LogicException;

class MetricsBucket
{
    private $toggles = [];

    public function __construct(
        $startDate,
        $endDate = null
    ) {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function addToggle($toggle)
    {
        $this->toggles[] = $toggle;

        return $this;
    }

    public function getStartDate()
    {
        return $this->startDate;
    }

    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function jsonSerialize()
    {
        $togglesArray = [];

        if ($this->endDate === null) {
            throw new LogicException('Cannot serialize incomplete bucket');
        }

        foreach ($this->toggles as $toggle) {
            $featureName = $toggle->getFeature()->getName();
            if (!isset($togglesArray[$featureName])) {
                $togglesArray[$featureName] = [
                    'yes' => 0,
                    'no' => 0,
                ];
            }

            $updateField = !is_null($toggle->isSuccess()) ? 'yes' : 'no';
            ++$togglesArray[$featureName][$updateField];

            if ($toggle->getVariant() !== null) {
                $variant = $toggle->getVariant();
                !is_null($togglesArray[$featureName]['variants'][$variant->getName()]) ? $togglesArray[$featureName]['variants'][$variant->getName()] : 0;
                ++$togglesArray[$featureName]['variants'][$variant->getName()];
            }
        }

        return [
            'start' => $this->startDate->format('c'),
            'stop' => $this->endDate->format('c'),
            'toggles' => $togglesArray,
        ];
    }
}
