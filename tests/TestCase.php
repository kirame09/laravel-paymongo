<?php

namespace Kirame\PayMongo\Tests;

use Kirame\PayMongo\PayMongoServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [PayMongoServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('paymongo.secret_key', 'sk_test_abc123');
        $app['config']->set('paymongo.public_key', 'pk_test_abc123');
        $app['config']->set('paymongo.webhook_secret', 'whsec_test_secret');
        $app['config']->set('paymongo.environment', 'sandbox');
        $app['config']->set('paymongo.base_url', 'https://api.paymongo.com/v1');
    }
}
