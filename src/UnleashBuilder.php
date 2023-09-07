<?php

namespace Unleash;
use League\Flysystem\Adapter\Local;
use Cache\Adapter\Filesystem\FilesystemCachePool;
use League\Flysystem\Filesystem;
use Unleash\Configuration\UnleashConfiguration;
use Unleash\Client\HttpClient;

use Unleash\Strategy\ApplicationHostnameStrategyHandler;
use Unleash\Strategy\DefaultStrategyHandler;
use Unleash\Strategy\GradualRolloutRandomStrategyHandler;
use Unleash\Strategy\GradualRolloutSessionIdStrategyHandler;
use Unleash\Strategy\GradualRolloutStrategyHandler;
use Unleash\Strategy\GradualRolloutUserIdStrategyHandler;
use Unleash\Strategy\IpAddressStrategyHandler;
use Unleash\Strategy\UserIdStrategyHandler;

use Unleash\Metrics\DefaultMetricsHandler;
use Unleash\Metrics\DefaultMetricsSender;
use Unleash\Stickiness\MurmurHashCalculator;
use Unleash\Bootstrap\EmptyBootstrapProvider;
use Unleash\Bootstrap\JsonSerializableBootstrapProvider;
use Unleash\Bootstrap\DefaultBootstrapHandler;
use Exception;
use Unleash\Exception\InvalidValueException;

class UnleashBuilder
{
	private $appUrl = null;
	private $instanceId = null;
	private $appName = null;
	private $cache = null;
	private $staleCache = null;
	private $cacheTtl = null;
	private $staleTtl = null;
	private $autoregister = true;
	private $headers = [];
	private $fetchingEnabled = true;
    private $metricsEnabled = null;
    private $bootstrapProvider = null;
    private $bootstrapHandler = null;
    private $connectTimeout = null;

    public function __construct()
    {
        $rolloutStrategyHandler = new GradualRolloutStrategyHandler(new MurmurHashCalculator());
        $this->strategies = [
            new DefaultStrategyHandler(),
            new IpAddressStrategyHandler(),
            new UserIdStrategyHandler(),
            $rolloutStrategyHandler,
            new ApplicationHostnameStrategyHandler(),
            new GradualRolloutUserIdStrategyHandler($rolloutStrategyHandler),
            new GradualRolloutSessionIdStrategyHandler($rolloutStrategyHandler),
            new GradualRolloutRandomStrategyHandler($rolloutStrategyHandler),
        ];
    }

	public static function create() {
		 return new self();
	}

	public function withAppName($appName)
	{
		return $this->with('appName', $appName);
	}

	public function withAppUrl($appUrl)
	{
		return $this->with('appUrl', $appUrl);
	}

    public function withInstanceId($instanceId)
    {
        return $this->with('instanceId', $instanceId);
    }

    public function withFetchingEnabled($enabled)
    {
        return $this->with('fetchingEnabled', $enabled);
    }

    public function withHeader($header, $value)
    {
        return $this->with('headers', array_merge((array) $this->headers, [$header => $value]));
    }

    public function withConnectTimeout($timeout)
    {
        return $this->with('connectTimeout', $timeout);
    }

    private function with($property, $value)
    {
        $copy = clone $this;
        $copy->{$property} = $value;
        return $copy;
    }

    public function withStaleCacheHandler($cache)
    {
        return $this->with('staleCache', $cache);
    }

    public function withCacheTimeToLive($timeToLive)
    {
        return $this->with('cacheTtl', $timeToLive);
    }

    public function withMetricsEnabled($enabled)
    {
        return $this->with('metricsEnabled', $enabled);
    }

    public function withMetricsInterval($milliseconds)
    {
        return $this->with('metricsInterval', $milliseconds);
    }


    public function withBootstrap($bootstrap)
    {
        if ($bootstrap === null) {
            $provider = new EmptyBootstrapProvider();
        } else {
            $provider = new JsonSerializableBootstrapProvider($bootstrap);
        }
        return $this->withBootstrapProvider($provider);
    }

    public function withBootstrapProvider($provider)
    {
        return $this->with('bootstrapProvider', $provider);
    }

    public function withBootstrapHandler($handler)
    {
        return $this->with('bootstrapHandler', $handler);
    }


    public function withAutomaticRegistrationEnabled($enabled)
    {
        return $this->with('autoregister', $enabled);
    }

    public function withHeaders($headers)
    {
        return $this->with('headers', $headers);
    }

    public function withStaleTtl($ttl)
    {
        return $this->with('staleTtl', $ttl);
    }


    public function build()
    {
        $appUrl = $this->appUrl;
        $instanceId = $this->instanceId;
        $appName = $this->appName;

        if (!$this->fetchingEnabled) {
            $appUrl = is_null($appUrl) ? 'http://127.0.0.1' : $appUrl;
            $instanceId = is_null($instanceId) ? 'dev' : $instanceId;
            $appName = is_null($appName) ? 'dev' : $appName;
        }

        if ($appUrl === null) {
            throw new InvalidValueException("App url must be set, please use 'withAppUrl()' method");
        }
        if ($instanceId === null) {
            throw new InvalidValueException("Instance ID must be set, please use 'withInstanceId()' method");
        }
        if ($appName === null) {
            throw new InvalidValueException(
                "App name must be set, please use 'withAppName()' or 'withGitlabEnvironment()' method"
            );
        }

        $cache = $this->cache;
        if ($cache === null) {
            $filesystemAdapter = new Local(sys_get_temp_dir() . '/unleash-default-cache');
            $filesystem = new Filesystem($filesystemAdapter);
            $cache = new FilesystemCachePool($filesystem);
            if ($cache === null) {
                throw new Exception("No cache implementation provided");
            }
        }

        $staleCache = !is_null($this->staleCache)? $this->staleCache : $cache;

        $bootstrapHandler = !is_null($this->bootstrapHandler) ? $this->bootstrapHandler : new DefaultBootstrapHandler();
        $bootstrapProvider = !is_null($this->bootstrapProvider) ? $this->bootstrapProvider : new EmptyBootstrapProvider();

        $configuration = new UnleashConfiguration($appUrl, $appName, $instanceId);
        $configuration
            ->setCache($cache)
            ->setStaleCache($staleCache)
            ->setTtl(!is_null($this->cacheTtl) ? $this->cacheTtl : $configuration->getTtl())
            ->setStaleTtl(!is_null($this->staleTtl) ? $this->staleTtl : $configuration->getStaleTtl())
            ->setHeaders($this->headers)
            ->setAutoRegistrationEnabled($this->autoregister)
            ->setBootstrapHandler($bootstrapHandler)
            ->setBootstrapProvider($bootstrapProvider)
            ->setMetricsEnabled(!is_null($this->metricsEnabled) ? $this->metricsEnabled : $configuration->isMetricsEnabled())
            ->setMetricsInterval(!is_null($this->metricsInterval) ? $this->metricsInterval : $configuration->getMetricsInterval())
            ->setFetchingEnabled($this->fetchingEnabled)
            ->setConnectTimeout(!is_null($this->connectTimeout) ? $this->connectTimeout : $configuration->getConnectTimeout());
        $httpClient = new HttpClient($configuration);
        return new Unleash(
            $configuration,
            $httpClient,
            $this->strategies,
            new DefaultMetricsHandler(
                new DefaultMetricsSender(
                    $httpClient,
                    $configuration
                ),
                $configuration
            )
        );
    }
}
