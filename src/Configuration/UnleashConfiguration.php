<?php

namespace Unleash\Configuration;

use LogicException;

final class UnleashConfiguration
{
    public function __construct(
        $url,
        $appName,
        $instanceId,
        $cache = null,
        $ttl = 30,
        $headers = [],
        $fetchingEnabled = true,
        $staleTtl = 30 * 60,
        $metricsInterval = 30000,
        $autoRegistrationEnabled = true
    ) {
        $this->url = $url;
        $this->appName = $appName;
        $this->instanceId = $instanceId;
        $this->cache = $cache;
        $this->ttl = $ttl;
        $this->headers = $headers;
        $this->fetchingEnabled = $fetchingEnabled;
        $this->staleTtl= $staleTtl;
        $this->metricsInterval = $metricsInterval;
        $this->autoRegistrationEnabled = $autoRegistrationEnabled;
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

    public function setUrl(string $url)
    {
        $this->url = $url;

        return $this;
    }

    public function setAppName(string $appName)
    {
        $this->appName = $appName;

        return $this;
    }

    public function setInstanceId(string $instanceId)
    {
        $this->instanceId = $instanceId;

        return $this;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getDefaultContext()
    {
        return $this->getContextProvider()->getContext();
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
}
