<?php

namespace Unleash\Configuration;
use Unleash\Bootstrap\DefaultBootstrapHandler;
use Unleash\Bootstrap\EmptyBootstrapProvider;

use LogicException;

class UnleashConfiguration
{
    public function __construct(
        $url,
        $appName,
        $instanceId,
        $cache = null,
        $ttl = 30,
        $metricsInterval = 30000,
        $metricsEnabled = true,
        $headers = [],
        $autoRegistrationEnabled = true,
        $defaultContext = null,
        $fetchingEnabled = true,
        $staleTtl = 30 * 60,
        $bootstrapHandler = null,
        $bootstrapProvider = null,
        $staleCache = null,
        $connectTimeout = 10,
    ) {
        $this->url = $url;
        $this->appName = $appName;
        $this->instanceId = $instanceId;
        $this->cache = $cache;
        $this->ttl = $ttl;
        $this->metricsInterval = $metricsInterval;
        $this->metricsEnabled = $metricsEnabled;
        $this->headers = $headers;
        $this->fetchingEnabled = $fetchingEnabled;
        $this->staleTtl = $staleTtl;
        $this->autoRegistrationEnabled = $autoRegistrationEnabled;
        $this->bootstrapHandler = $bootstrapHandler;
        $this->bootstrapProvider = $bootstrapProvider;
        $this->staleCache = $staleCache;
        if ($defaultContext === null) {
            $defaultContext = new UnleashContext();
        }
        $this->setDefaultContext($defaultContext);
        $this->connectTimeout = $connectTimeout;
    }

    public function getCache()
    {
        if ($this->cache === null) {
            throw new LogicException('Cache handler is not set');
        }

        return $this->cache;
    }

    private function endsWith($haystack, $needle) {
        return (strlen($haystack) > strlen($needle)) ? (substr($haystack, -strlen($needle)) == $needle) : false;
    }

    public function getUrl()
    {
        $url = $this->url;
        if (!$this->endsWith($url, '/')) {
            $url .= '/';
        }

        return $url;
    }

    public function getAppName()
    {
        return $this->appName;
    }

    public function getInstanceId()
    {
        return $this->instanceId;
    }

    public function getTtl()
    {
        return $this->ttl;
    }

    public function setCache($cache)
    {
        $this->cache = $cache;

        return $this;
    }

    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    public function setAppName($appName)
    {
        $this->appName = $appName;

        return $this;
    }

    public function setInstanceId($instanceId)
    {
        $this->instanceId = $instanceId;

        return $this;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function isFetchingEnabled()
    {
        return $this->fetchingEnabled;
    }

    public function setTtl($ttl)
    {
        $this->ttl = $ttl;
        return $this;
    }

    public function setStaleTtl($staleTtl)
    {
        $this->staleTtl = $staleTtl;

        return $this;
    }

    public function getStaleTtl()
    {
        return $this->staleTtl;
    }

    public function setHeaders($headers)
    {
        $this->headers = $headers;

        return $this;
    }

    public function setAutoRegistrationEnabled($autoRegistrationEnabled)
    {
        $this->autoRegistrationEnabled = $autoRegistrationEnabled;

        return $this;
    }

    public function getMetricsInterval()
    {
        return $this->metricsInterval;
    }


    public function setFetchingEnabled($fetchingEnabled)
    {
        $this->fetchingEnabled = $fetchingEnabled;

        return $this;
    }

    public function isAutoRegistrationEnabled()
    {
        return $this->autoRegistrationEnabled;
    }

    public function isMetricsEnabled()
    {
        return $this->metricsEnabled;
    }

    public function setMetricsEnabled($metricsEnabled)
    {
        $this->metricsEnabled = $metricsEnabled;

        return $this;
    }

    public function setMetricsInterval($metricsInterval)
    {
        $this->metricsInterval = $metricsInterval;

        return $this;
    }

    public function getBootstrapHandler()
    {
        if (is_null($this->bootstrapHandler)) {
            $this->bootstrapHandler = new DefaultBootstrapHandler();
        }
        return $this->bootstrapHandler;
    }

    public function setBootstrapHandler($bootstrapHandler)
    {
        $this->bootstrapHandler = $bootstrapHandler;
        return $this;
    }

    public function getBootstrapProvider()
    {
        if (is_null($this->bootstrapProvider)) {
            $this->bootstrapProvider = new EmptyBootstrapProvider();
        }
        return $this->bootstrapProvider;
    }

    public function setBootstrapProvider($bootstrapProvider)
    {
        $this->bootstrapProvider = $bootstrapProvider;

        return $this;
    }


    public function setStaleCache($cache)
    {
        $this->staleCache = $cache;
        return $this;
    }

    public function getStaleCache()
    {
        return !is_null($this->staleCache) ? $this->staleCache : $this->getCache();
    }

    public function getDefaultContext()
    {
        return $this->defaultContext;
    }

    public function setDefaultContext($defaultContext)
    {
        $this->defaultContext = $defaultContext;
        return $this;
    }

    public function getConnectTimeout()
    {
        return $this->connectTimeout;
    }

    public function setConnectTimeout($timeout)
    {
        $this->connectTimeout = $timeout;
        return $this;
    }

}
