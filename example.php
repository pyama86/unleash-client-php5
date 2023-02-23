<?php

require __DIR__ . '/vendor/autoload.php';
use Unleash\UnleashBuilder;
use Unleash\Configuration\UnleashContext;

date_default_timezone_set('Asia/Tokyo');
$appName = "unleash-php5";

$feature = "test-feature";

$bootstrapArray = [
    'features' => [
        [
            'name' => $feature,
            'enabled' => true,
            'strategies' => ['default']
        ]
    ]
];

$unleashBuilder = UnleashBuilder::create()
->withAppName($appName)
->withAppUrl($_ENV["UNLEASH_SERVER"])
->withHeader('Authorization', $_ENV["UNLEASH_API_TOKEN"])
->withMetricsInterval(10)
->withBootstrap($bootstrapArray)
->withInstanceId(gethostname());

$testBuilder = $unleashBuilder;
# for test
$unleash = $testBuilder ->withFetchingEnabled(false)
                                      ->withCacheTimeToLive(0)
                                      ->build();
var_dump($unleash->isEnabled($feature, $context));

# for production
$unleash = $unleashBuilder->build();
var_dump($unleash->isEnabled($feature));

$context = new UnleashContext();
$context->setCurrentUserId('dummy');
var_dump($unleash->isEnabled($feature, $context));

$unleash = $unleashBuilder->withFetchingEnabled(false)
                                      ->withCacheTimeToLive(0)
                                      ->build();

var_dump($unleash->isEnabled($feature, $context));
