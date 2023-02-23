<?php

namespace Unleash\Bootstrap;

use JsonException;
use Traversable;

class DefaultBootstrapHandler
{
    public function getBootstrapContents($provider)
    {
        $bootstrap = $provider->getBootstrap();
        if ($bootstrap === null) {
            return null;
        }

        if ($bootstrap instanceof Traversable) {
            $bootstrap = iterator_to_array($bootstrap);
        }
        $result = json_encode($bootstrap);
        assert($result !== false);
        return $result;
    }
}
