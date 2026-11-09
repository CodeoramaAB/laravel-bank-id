<?php

namespace Jgroup\BankID\Tests;

use Faker\Factory;
use Jgroup\BankID\BankIDServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    protected $faker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->faker = Factory::create('sv_SE');
    }

    protected function getPackageProviders($app)
    {
        return [
            BankIDServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        //
    }
}
