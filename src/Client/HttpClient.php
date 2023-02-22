<?php

namespace Unleash\Client;

use Exception;
use GuzzleHttp\Client as Client;

class HttpClient
{
    const CACHE_REGISTRATION = 'unleash.client.metrics.registration';
    const SDK_VERSION = '0.0.1';
    protected $httpClient;
    protected $config;
    public function __construct(
        $config
    ) {
        $this->config = $config;
        $this->httpClient = $this->createHttpClient();
        $this->sdkName = 'unleash-client-php5';
        $this->sdkVersion = SDK_VERSION;
    }



    public function fetchFeatures()
    {
        $response = $this->httpClient->get('client/features');
        if (
            $response->getStatusCode() >= 200 &&
            $response->getStatusCode() < 300
        ) {
            return $response->getBody()->getContents();
        }

        return [];
    }
    public function register()
    {
        if (!$this->config->isFetchingEnabled()) {
            return false;
        }
        if ($this->hasValidCacheRegistration()) {
            return true;
        }

        try {
            $payload = [
                'appName' => $this->config->getAppName(),
                'instanceId' => $this->config->getInstanceId(),
                'sdkVersion' => $this->sdkName . ':' . $this->sdkVersion,
                'strategies' => [],
                'started' => date("c"),
                'interval' => $this->config->getMetricsInterval
            ];

            $response = $this->httpClient->request('POST', 'client/register', [
                'json' => $payload
            ]);

        } catch (Exception $e) {
            return false;
        }

        $result = $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
        $this->storeCache($result);
        return $result;
    }

    private function hasValidCacheRegistration()
    {
        $cache = $this->config->getCache();
        if (!$cache->has(CACHE_REGISTRATION)) {
            return false;
        }

        return (bool) $cache->get(CACHE_REGISTRATION);
    }

    private function storeCache($result)
    {
        $this->config->getCache()->set(CACHE_REGISTRATION, $result, $this->config->getTtl());
    }

    protected function createHttpClient()
    {
        return new Client([
            'base_uri' => $this->config->getUrl(),
            'verify' => $_ENV["DISABLE_SSL_VERIFY"] ? !$_ENV["DISABLE_SSL_VERIFY"] : true,
            'headers' => array_merge([
                "UNLEASH-APPNAME" => $this->config->getAppName(),
                "UNLEASH-INSTANCEID" => $this->config->getInstanceId()
            ], $this->config->getHeaders())
        ]);
    }
}