<?php

namespace Unleash\Tests\Configuration;

use DateTimeImmutable;
use DateTime;
use Unleash\Configuration\UnleashConfiguration;
use Unleash\Configuration\UnleashContext;
use Unleash\Unleash;
use Unleash\Enum\ContextField;
use Unleash\Exception\InvalidValueException;
use Unleash\Metrics\MetricsHandler;
use Unleash\Stickiness\MurmurHashCalculator;
use Unleash\Strategy\DefaultStrategyHandler;
use Unleash\Tests\AbstractHttpClientTest;
use Unleash\Client\HttpClient;
use Unleash\Metrics\DefaultMetricsHandler;
use Unleash\Metrics\DefaultMetricsSender;

class UnleashContextTest extends AbstractHttpClientTest
{
    public function testCustomProperties()
    {
        $context = new UnleashContext();

        self::assertFalse($context->hasCustomProperty('test'));

        $context->setCustomProperty('test', 'test');
        self::assertTrue($context->hasCustomProperty('test'));
        self::assertEquals('test', $context->getCustomProperty('test'));

        $context->setCustomProperty('test', 'test2');
        self::assertEquals('test2', $context->getCustomProperty('test'));

        $context->removeCustomProperty('test');
        self::assertFalse($context->hasCustomProperty('test'));

        $this->expectException(InvalidValueException::class);
        $context->getCustomProperty('test');
    }

    public function testCustomPropertyRemoval()
    {
        $context = new UnleashContext();
        $context->removeCustomProperty('nonexistent');

        $this->expectException(InvalidValueException::class);
        $context->removeCustomProperty('nonexistent', false);
    }

    public function testFindContextValue()
    {
        unset($_SERVER['REMOTE_ADDR']);

        $date = date(DateTime::ISO8601);
        $context = (new UnleashContext('123', '456', '789'))
            ->setCustomProperty('someField', '012')
            ->setCustomProperty('currentTime', $date)
        ;
        self::assertEquals('123', $context->findContextValue(ContextField::USER_ID));
        self::assertEquals('456', $context->findContextValue(ContextField::IP_ADDRESS));
        self::assertEquals('789', $context->findContextValue(ContextField::SESSION_ID));
        self::assertEquals($date, $context->findContextValue(ContextField::CURRENT_TIME));
        self::assertEquals('012', $context->findContextValue('someField'));
        self::assertNull($context->findContextValue('someOtherField'));

        $context = new UnleashContext();
        self::assertNull($context->findContextValue(ContextField::USER_ID));
        self::assertNull($context->findContextValue(ContextField::IP_ADDRESS));
        self::assertNull($context->findContextValue(ContextField::SESSION_ID));
        self::assertTrue(is_string($context->findContextValue(ContextField::CURRENT_TIME)));
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        self::assertEquals('127.0.0.1', $context->findContextValue(ContextField::IP_ADDRESS));
    }

    public function testHasMatchingFieldValue()
    {
        unset($_SERVER['REMOTE_ADDR']);

        $date = date(DateTime::ISO8601);
        $context = (new UnleashContext('123', '456', '789', [], null, 'dev'))
            ->setCustomProperty('someField', '012')
            ->setCustomProperty('currentTime', $date)
        ;

        self::assertTrue($context->hasMatchingFieldValue(ContextField::USER_ID, [
            '123',
            '789',
        ]));
        self::assertTrue($context->hasMatchingFieldValue(ContextField::IP_ADDRESS, [
            '741',
            '456',
        ]));
        self::assertTrue($context->hasMatchingFieldValue(ContextField::SESSION_ID, [
            '852',
            '789',
        ]));
        self::assertTrue($context->hasMatchingFieldValue(ContextField::ENVIRONMENT, [
            'dev',
            'production',
        ]));
        self::assertTrue($context->hasMatchingFieldValue(ContextField::CURRENT_TIME, [
            $date,
        ]));
        self::assertTrue($context->hasMatchingFieldValue('someField', [
            '753',
            '012',
        ]));

        self::assertFalse($context->hasMatchingFieldValue(ContextField::USER_ID, []));
        self::assertFalse($context->hasMatchingFieldValue('nonexistent', ['someValue']));
    }

    public function testHostname()
    {
        // for most standard systems, the hostname should not be null
        $context = new UnleashContext();
        self::assertNotNull($context->getHostname());

        $context = new UnleashContext(null, null, null, [], 'myCustomHostname');
        self::assertEquals('myCustomHostname', $context->getHostname());

        $context = new UnleashContext();
        $context->setHostname('customHostname');
        self::assertEquals('customHostname', $context->getHostname());
        self::assertTrue($context->hasCustomProperty(ContextField::HOSTNAME));
        self::assertEquals('customHostname', $context->findContextValue(ContextField::HOSTNAME));

        $context->setHostname(null);
        self::assertFalse($context->hasCustomProperty(ContextField::HOSTNAME));
    }

    public function testCurrentTime()
    {
        $context = new UnleashContext();
        self::assertInstanceOf(DateTime::class, $context->getCurrentTime());

        $time = new DateTimeImmutable('2022-01-01 15:00:00+0200');
        $context->setCurrentTime($time);
        self::assertSame($time->format('c'), $context->getCurrentTime()->format('c'));
        self::assertEquals(
            '2022-01-01T15:00:00+0200',
            $context->getCurrentTime()->format(DateTime::ISO8601)
        );
        self::assertEquals(
            $context->getCurrentTime()->format(DateTime::ISO8601),
            $context->findContextValue(ContextField::CURRENT_TIME)
        );
    }

    public function testCurrentTimeE2E()
    {
        $this->pushResponse([
            'features' => [
                [
                    'name'=> 'test',
                    'enabled'=> true,
                    'strategies'=> [
                        [
                            'name'=> 'default',
                            'parameters'=> [],
                            'constraints'=> [
                                [
                                    'contextName'=> 'currentTime',
                                    'operator'=> 'DATE_AFTER',
                                    'value' => '2022-01-29T13:00:00.000Z',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
        $config = new UnleashConfiguration('', '', '');
        $config->setAutoRegistrationEnabled(false)
               ->setCache($this->getCache());

        $httpClient = new HttpClient($config, $this->httpClient);
        $unleash = new Unleash(
            $config,
            $httpClient,
            [
                new DefaultStrategyHandler()
            ],
            (new DefaultMetricsHandler(
                new DefaultMetricsSender(
                    $httpClient,
                    $config
                ),
                $config
            ))
        );

        self::assertTrue($unleash->isEnabled('test'));
    }
}
