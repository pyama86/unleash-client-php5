<?php

namespace Unleash\Bootstrap;

use JsonSerializable;
use Traversable;

class JsonSerializableBootstrapProvider
{
    public function __construct(
        $data
    ) {
        $this->data = $data;
    }

    public function getBootstrap()
    {
        return $this->data;
    }
}
