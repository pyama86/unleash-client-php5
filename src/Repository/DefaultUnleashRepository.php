<?php

namespace Unleash\Repository;

use Exception;
use LogicException;
use Unleash\Client\CacheKey;
use Unleash\Feature\DefaultFeature;
use Unleash\Strategy\DefaultStrategy;
use Unleash\Exception\HttpResponseException;

class DefaultUnleashRepository
{
    public function __construct(
        $httpClient,
        $config
    ) {
        $this->httpClient = $httpClient;
        $this->config = $config;
    }
    public function findFeature($featureName)
    {
        $features = $this->getFeatures();
        assert(is_array($features));

        return $features[$featureName];
    }

    public function getFeatures()
    {
        $features = $this->getCachedFeatures();
        if ($features === null) {
            if (!$this->config->isFetchingEnabled()) {
                if (!$data = $this->getBootstrappedResponse()) {
                    throw new LogicException('Fetching of Unleash api is disabled but no bootstrap is provided');
                }
            } else {
                try {
                    $data = $this->httpClient->fetchFeatures();
                    if (!empty($data) ) {
                        $this->setLastValidState($data);
                    } else {
                        throw new HttpResponseException("Invalid status code: '{$response->getStatusCode()}'");
                    }
                } catch (Exception $exception) {
                    $data = $this->getLastValidState();
                }

                if (is_null($data)) {
                    $data = $this->getBootstrappedResponse();
                }
                if ($data === null) {
                    throw new HttpResponseException(sprintf(
                        'Got invalid response code when getting features and no default bootstrap provided: %s',
                        isset($response) ? $response->getStatusCode() : 'unknown response status code'
                    ), 0, null);
                }
            }
            $features = $this->parseFeatures($data);
            $this->setCache($features);
        }
        return $features;
    }

    private function parseSegments(array $segmentsRaw)
    {
        $result = [];
        foreach ($segmentsRaw as $segmentRaw) {
            $result[$segmentRaw['id']] = new DefaultSegment(
                $segmentRaw['id'],
                $this->parseConstraints($segmentRaw['constraints'])
            );
        }

        return $result;
    }

    private function parseConstraints($constraintsRaw)
    {
        $constraints = [];

        foreach ($constraintsRaw as $constraint) {
            $constraints[] = new DefaultConstraint(
                $constraint['contextName'],
                $constraint['operator'],
                $constraint['values'] ? $constraint['values'] : null,
                $constraint['value'] ? $constraint['value'] :  null,
                $constraint['inverted'] ? $constraint['inverted'] : false,
                $constraint['caseInsensitive'] ? $constraint['caseInsensitive'] : false
            );
        }

        return $constraints;
    }

    private function parseFeatures($rawBody)
    {
        $features = [];
        $body = json_decode($rawBody, true);
        assert(is_array($body));

        $globalSegments = $this->parseSegments($body['segments'] ? $body['segments'] : []);

        if (!isset($body['features']) || !is_array($body['features'])) {
            throw new Exception("The body isn't valid because it doesn't contain a 'features' key");
        }

        foreach ($body['features'] as $feature) {
            $strategies = [];
            $variants = [];

            foreach ($feature['strategies'] as $strategy) {
                $hasNonexistentSegments = false;
                $segments = [];
                foreach ($strategy['segments'] ? $strategy['segments'] : [] as $segment) {
                    if (isset($globalSegments[$segment])) {
                        $segments[] = $globalSegments[$segment];
                    } else {
                        $hasNonexistentSegments = true;
                        break;
                    }
                }
                $strategies[] = new DefaultStrategy(
                    $strategy['name'],
                    $strategy['parameters'] ? $strategy['parameters'] : [],
                    $constraints,
                    $segments,
                    $hasNonexistentSegments
                );
            }
            foreach ($feature['variants'] ? $feature['variants'] : [] as $variant) {
                $overrides = [];
                foreach ($variant['overrides'] ? $variant['overrides'] : [] as $override) {
                    $overrides[] = new DefaultVariantOverride($override['contextName'], $override['values']);
                }
                $variants[] = new DefaultVariant(
                    $variant['name'],
                    true,
                    $variant['weight'],
                    $variant['stickiness'] ? $variant['stickiness'] : 'default',
                    isset($variant['payload'])
                        ? new DefaultVariantPayload($variant['payload']['type'], $variant['payload']['value'])
                        : null,
                    $overrides
                );
            }

            $features[$feature['name']] = new DefaultFeature(
                $feature['name'],
                $feature['enabled'],
                $strategies,
                $variants,
                $feature['impressionData'] ? $feature['impressionData'] : false
            );
        }

        return $features;
    }


    public function getCachedFeatures()
    {
        $cache = $this->config->getCache();

        if (!$cache->has(CacheKey::FEATURES)) {
            return null;
        }

        $result = $cache->get(CacheKey::FEATURES, []);
        assert(is_array($result));

        return $result;
    }

    public function setCache($features)
    {
        $cache = $this->config->getCache();
        $cache->set(CacheKey::FEATURES, $features, $this->config->getTtl());
    }

    public function getLastValidState()
    {
        if (!$this->config->getCache()->has(CacheKey::FEATURES_RESPONSE)) {
            return null;
        }

        $value = $this->config->getCache()->get(CacheKey::FEATURES_RESPONSE);
        assert(is_string($value));

        return $value;
    }

    private function getBootstrappedResponse()
    {
        return $this->config->getBootstrapHandler()->getBootstrapContents(
            $this->config->getBootstrapProvider()
        );
    }

    private function setLastValidState($data)
    {
        $this->config->getStaleCache()->set(
            CacheKey::FEATURES_RESPONSE,
            $data,
            $this->config->getStaleTtl()
        );
    }

}
