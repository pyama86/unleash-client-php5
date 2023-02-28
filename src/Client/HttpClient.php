<?php

namespace Unleash\Client;

use Exception;
use GuzzleHttp\Client as Client;
use Unleash\Enum\CacheKey;

class HttpClient
{
    const SDK_VERSION = '0.0.1';
    protected $httpClient;
    protected $config;
    public function __construct(
        $config,
        $httpClient = null
    ) {
        $this->config = $config;
        $this->httpClient = !is_null($httpClient) ? $httpClient : $this->createHttpClient();
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
        } else {
            throw new HttpResponseException("Invalid status code: '{$response->getStatusCode()}'");
        }

        return [];
    }
    public function sendMetrics($bucket)
    {
        try {
            $payload = [
                'appName' => $this->config->getAppName(),
                'instanceId' => $this->config->getInstanceId(),
                'bucket' => $bucket->jsonSerialize(),
            ];

            $response = $this->httpClient->post('client/metrics', [
                'json' => $payload
            ]);
        } catch (Exception $e) {
            return false;
        }
        $result = $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
        $this->storeCache($result);
        return $result;
    }
    public function register($strategyHandlers)
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
                'strategies' => array_map(function ($strategyHandler) {
                    return $strategyHandler->getStrategyName();
                }, $strategyHandlers),
                'started' => date("c"),
                'interval' => $this->config->getMetricsInterval
            ];

            $response = $this->httpClient->post('client/register', [
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
        if (!$cache->has(CacheKey::REGISTRATION)) {
            return false;
        }

        return (bool) $cache->get(CacheKey::REGISTRATION);
    }

    private function storeCache($result)
    {
        $this->config->getCache()->set(CacheKey::REGISTRATION, $result, $this->config->getTtl());
    }

    protected function createHttpClient()
    {
        return new Client([
            'base_url' => $this->config->getUrl(),
            'defaults' => [
                'verify' => !is_null($_ENV["DISABLE_SSL_VERIFY"]) ? !$_ENV["DISABLE_SSL_VERIFY"] : true,
                'headers' => array_merge([
                    "UNLEASH-APPNAME" => $this->config->getAppName(),
                    "UNLEASH-INSTANCEID" => $this->config->getInstanceId(),
                    'Content-Type' => 'application/json'
                ], $this->config->getHeaders())
            ]
        ]);
    }
}
