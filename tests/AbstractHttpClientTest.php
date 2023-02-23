<?php

namespace Unleash\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Throwable;
use Unleash\Configuration\Context;
use Unleash\Configuration\UnleashConfiguration;
use Unleash\Metrics\MetricsHandler;
use Unleash\Repository\DefaultUnleashRepository;
use Unleash\Client\HttpClient;
use Symfony\Component\Cache\Simple\NullCache;
abstract class AbstractHttpClientTest extends TestCase
{
    /**
     * @var MockHandler
     */
    protected $mockHandler;

    /**
     * @var DefaultUnleashRepository
     */
    protected $repository;

    /**
     * @var array[]
     */
    protected $requestHistory = [];

    /**
     * @var HandlerStack
     */
    protected $handlerStack;

    /**
     * @var Client
     */
    protected $httpClient;

    /**
     * @var MetricsHandler
     */
    protected $metricsHandler;

    /**
     * @var VariantHandler
     */
    protected $variantHandler;

    protected function setUp()
    {
        $this->mockHandler = new MockHandler();

        $this->handlerStack = HandlerStack::create($this->mockHandler);
        $this->handlerStack->push(Middleware::history($this->requestHistory));

        $this->httpClient = new Client([
            'handler' => $this->handlerStack,
        ]);
        $config = new UnleashConfiguration('', '', '');
        $config->setCache($this->getCache());
        $this->repository = new DefaultUnleashRepository(
            (new HttpClient($config, $this->httpClient)),
            $config
        );
    }

    /**
     * @param array|Throwable $response
     */
    protected function pushResponse($response, $count = 1, $statusCode = 200)
    {
        for ($i = 0; $i < $count; ++$i) {
            if (is_array($response)) {
                $mocked = new Response($statusCode, ['Content-Type' => 'application/json'], json_encode($response));
            } else {
                $mocked = $response;
            }
            $this->mockHandler->append($mocked);
        }
    }

    private function getCache()
    {
        return new NullCache();
    }
}
