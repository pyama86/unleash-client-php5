<?php

namespace Unleash\Tests;
use Cache\Adapter\Filesystem\FilesystemCachePool;
use JsonSerializable;
use PHPUnit\Framework\Constraint\IsIdentical;
use PHPUnit\Framework\TestCase;
use ReflectionObject;
use Traversable;
use Unleash\Bootstrap\DefaultBootstrapHandler;
use Unleash\Bootstrap\EmptyBootstrapProvider;
use Unleash\Bootstrap\JsonSerializableBootstrapProvider;
use Unleash\Configuration\UnleashConfiguration;
use Unleash\Configuration\UnleashContext;
use Unleash\Unleash;
use Unleash\Strategy\DefaultStrategyHandler;
use Unleash\UnleashBuilder;
use Unleash\Exception\InvalidValueException;

class UnleashBuilderTest extends TestCase
{
    private $instance;

    protected function setUp()
    {
        $this->instance = UnleashBuilder::create();
    }

    public function testWithInstanceId()
    {
        self::assertNotSame($this->instance, $this->instance->withInstanceId('test'));
    }

    public function testWithCacheTimeToLive()
    {
        self::assertNotSame($this->instance, $this->instance->withCacheTimeToLive(123));
    }

    public function testWithAppName()
    {
        self::assertNotSame($this->instance, $this->instance->withAppName('test-app'));
    }

    public function testBuild()
    {
        $instance = $this->instance
            ->withAppUrl('https://example.com')
            ->withAppName('Test App')
            ->withInstanceId('test')
            ->withAutomaticRegistrationEnabled(false)
            ->build();
        $reflection = new ReflectionObject($instance);
        $repositoryProperty = $reflection->getProperty('repository');
        $repositoryProperty->setAccessible(true);
        $repository = $repositoryProperty->getValue($instance);
        $strategiesProperty = $reflection->getProperty('strategyHandlers');
        $strategiesProperty->setAccessible(true);
        $strategies = $strategiesProperty->getValue($instance);
        $reflection = new ReflectionObject($repository);
        $configProperty = $reflection->getProperty('config');
        $configProperty->setAccessible(true);
        $config = $configProperty->getValue($repository);
        assert($config instanceof UnleashConfiguration);

        self::assertEquals('https://example.com/', $config->getUrl());
        self::assertEquals('Test App', $config->getAppName());
        self::assertEquals('test', $config->getInstanceId());
        self::assertNotNull($config->getCache());
        self::assertCount(5, $strategies);


        $instance = $this->instance
            ->withAppUrl('https://example.com')
            ->withAppName('Test App')
            ->withInstanceId('test')
            ->withAutomaticRegistrationEnabled(false)
            ->build();
        $repository = new ReflectionObject($repositoryProperty->getValue($instance));

        $instance = $this->instance
            ->withAppUrl('https://example.com')
            ->withAppName('Test App')
            ->withInstanceId('test')
            ->withAutomaticRegistrationEnabled(false)
            ->build();
        self::assertCount(5, $strategiesProperty->getValue($instance));

        $instance = $this->instance
            ->withAppUrl('https://example.com')
            ->withAppName('Test App')
            ->withInstanceId('test')
            ->withAutomaticRegistrationEnabled(false)
            ->withCacheTimeToLive(359)
            ->build();
        $repository = $repositoryProperty->getValue($instance);
        $config = $configProperty->getValue($repository);
        assert($config instanceof UnleashConfiguration);
        self::assertEquals(359, $config->getTtl());
    }

    public function testWithAppUrl()
    {
        self::assertNotSame($this->instance, $this->instance->withAppUrl('https://example.com'));
    }

    public function testWithHeader()
    {
        self::assertNotSame($this->instance, $this->instance->withHeader('Authorization', 'test'));

        $instance = $this->instance
            ->withHeader('Authorization', 'test')
            ->withHeader('Some-Header', 'test');
        $reflection = new ReflectionObject($instance);
        $headersProperty = $reflection->getProperty('headers');
        $headersProperty->setAccessible(true);
        $headers = $headersProperty->getValue($instance);
        self::assertCount(2, $headers);

        $instance = $instance
            ->withHeader('Authorization', 'test2');
        $headers = $headersProperty->getValue($instance);
        self::assertCount(2, $headers);
        self::assertArrayHasKey('Authorization', $headers);
        self::assertEquals('test2', $headers['Authorization']);

        $unleash = $instance
            ->withAppUrl('test')
            ->withInstanceId('test')
            ->withAppName('test')
            ->withAutomaticRegistrationEnabled(false)
            ->build();
        $reflection = new ReflectionObject($unleash);
        $repositoryProperty = $reflection->getProperty('repository');
        $repositoryProperty->setAccessible(true);
        $repository = $repositoryProperty->getValue($unleash);

        $reflection = new ReflectionObject($repository);
        $configProperty = $reflection->getProperty('config');
        $configProperty->setAccessible(true);
        $config = $configProperty->getValue($repository);

        $reflection = new ReflectionObject($config);
        $headersPropertyBuilt = $reflection->getProperty('headers');
        $headersPropertyBuilt->setAccessible(true);
        $headersBuilt = $headersPropertyBuilt->getValue($config);
        self::assertEquals($headers, $headersBuilt);

        $instance = $instance
            ->withHeaders([
                'Some-Header-2' => 'value',
                'Some-Header-3' => 'value',
            ]);
        $headers = $headersProperty->getValue($instance);
        self::assertCount(2, $headers);
        self::assertArrayHasKey('Some-Header-2', $headers);
        self::assertArrayHasKey('Some-Header-3', $headers);
    }

    public function testWithAutomaticRegistrationEnabled()
    {
        self::assertNotSame($this->instance, $this->instance->withAutomaticRegistrationEnabled(false));
    }

    public function testWithMetricsEnabled()
    {
        self::assertNotSame($this->instance, $this->instance->withMetricsEnabled(false));
    }

    public function testWithMetricsInterval()
    {
        self::assertNotSame($this->instance, $this->instance->withMetricsInterval(5000));
    }

    public function testWithFetchingEnabled()
    {
        $builder = $this->instance
            ->withAutomaticRegistrationEnabled(false)
            ->withMetricsEnabled(false)
            ->withAppUrl('test')
            ->withInstanceId('test')
            ->withAppName('test');

        self::assertNotSame($builder, $builder->withFetchingEnabled(true));

        self::assertTrue($this->getConfiguration($builder->build())->isFetchingEnabled());
        self::assertFalse(
            $this->getConfiguration($builder->withFetchingEnabled(false)->build())->isFetchingEnabled()
        );
        self::assertTrue(
            $this->getConfiguration($builder->withFetchingEnabled(true)->build())->isFetchingEnabled()
        );

        // no exception should be thrown
        $this->instance->withFetchingEnabled(false)->build();
    }

    public function testWithStaleTtl()
    {
        $instance = $this->instance->withFetchingEnabled(false);
        self::assertNull($this->getProperty($this->instance, 'staleTtl'));
        self::assertEquals(
            30 * 60,
            $this->getConfiguration($instance->build())->getStaleTtl()
        );

        $instance = $this->instance->withFetchingEnabled(false)->withStaleTtl(60 * 60);
        self::assertNull($this->getProperty($this->instance, 'staleTtl'));
        self::assertEquals(
            60 * 60,
            $this->getConfiguration($instance->build())->getStaleTtl()
        );
    }

    private function getConfiguration($unleash)
    {
        $configProperty = (new ReflectionObject($unleash))->getProperty('config');
        $configProperty->setAccessible(true);

        return $configProperty->getValue($unleash);
    }

    private function getBootstrapProvider(DefaultUnleash $unleash)
    {
        return $this->getConfiguration($unleash)->getBootstrapProvider();
    }


    private function getReflection($object)
    {
        return new ReflectionObject($object);
    }

    private function getProperty($object, $property)
    {
        $property = $this->getReflection($object)->getProperty($property);
        $property->setAccessible(true);

        return $property->getValue($object);
    }
}
