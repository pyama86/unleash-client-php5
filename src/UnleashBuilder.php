<?php

namespace Unleash;
use League\Flysystem\Adapter\Local;
use Cache\Adapter\Filesystem\FilesystemCachePool;
use League\Flysystem\Filesystem;
use Unleash\Configuration\UnleashConfiguration;
use Unleash\Client\HttpClient;

use Unleash\Strategy\DefaultStrategyHandler;
use Unleash\Strategy\IpAddressStrategyHandler;
use Unleash\Strategy\UserIdStrategyHandler;
use Exception;

class UnleashBuilder
{
	private $appUrl = null;
	private $instanceId = null;
	private $appName = null;
	private $httpClient = null;
	private $requestFactory = null;
	private $cache = null;
	private $cacheTtl = null;
	private $staleTtl = null;
	private $registrationService = null;
	private $autoregister = true;
	private $headers = [];
	private $metricsEnabled = null;
	private $metricsInterval = null;
	private $fetchingEnabled = true;


    public function __construct()
    {
        $this->strategies = [
            new DefaultStrategyHandler(),
            new IpAddressStrategyHandler(),
            new UserIdStrategyHandler(),
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

    private function with($property, $value) 
    {
        $copy = clone $this;
        $copy->{$property} = $value;
        return $copy;
    }

    public function withCacheTimeToLive($timeToLive)
    {
        return $this->with('cacheTtl', $timeToLive);
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
            throw new Exception("App url must be set, please use 'withAppUrl()' method");
        }
        if ($instanceId === null) {
            throw new Exception("Instance ID must be set, please use 'withInstanceId()' method");
        }
        if ($appName === null) {
            throw new Exception(
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

        $configuration = new UnleashConfiguration($appUrl, $appName, $instanceId);
        $configuration
            ->setCache($cache)
            ->setTtl($this->cacheTtl ? $this->cacheTtl : $configuration->getTtl())
            ->setStaleTtl($this->staleTtl ? $this->staleTtl : $configuration->getStaleTtl())
            ->setHeaders($this->headers)
            ->setAutoRegistrationEnabled($this->autoregister)
            ->setFetchingEnabled($this->fetchingEnabled);
        $httpClient = new HttpClient($configuration);
        return new Unleash($configuration, $httpClient, $this->strategies);
    }
}
