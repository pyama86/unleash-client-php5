<?php

require __DIR__ . '/vendor/autoload.php';
use Unleash\UnleashBuilder;
use Unleash\Configuration\UnleashContext;

date_default_timezone_set('Asia/Tokyo');
$appName = "unleash-php5";

$feature = "test-feature";

$unleashBuilder = UnleashBuilder::create()
->withAppName($appName)
->withAppUrl($_ENV["UNLEASH_SERVER"])
->withHeader('Authorization', $_ENV["UNLEASH_API_TOKEN"])
->withInstanceId(gethostname());

try {
    $unleash = $unleashBuilder->build();
} catch (\Exception $e) {
    var_dump($e);
    $unleash = $unleashBuilder->withFetchingEnabled(false)
                                          ->withCacheTimeToLive(0)
                                          ->build();
}

$unleash->isEnabled($test);
$context = new UnleashContext();
$context->setCurrentUserId('dummy');

var_dump($unleash->isEnabled($feature, $context));
