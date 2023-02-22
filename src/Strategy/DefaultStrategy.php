<?php

namespace Unleash\Strategy;

class DefaultStrategy
{
    public function __construct(
        $name,
        $parameters = [],
        $constraints = [],
        $segments = [],
        $nonexistentSegments = false
    ) {
        $this->name = $name;
        $this->parameters = $parameters;
        $this->constraints = $constraints;
        $this->segments = $segments;
        $this->nonexistentSegments = $nonexistentSegments;

    }

    public function getName()
    {
        return $this->name;
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    public function getConstraints()
    {
        return $this->constraints;
    }

    public function getSegments()
    {
        return $this->segments;
    }

    public function hasNonexistentSegments()
    {
        return $this->nonexistentSegments;
    }
}
