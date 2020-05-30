<?php

namespace Noitran\Lumen\Horizon\Tests;

use Noitran\Lumen\Horizon\HorizonServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

/**
 * Class TestCase
 */
abstract class TestCase extends OrchestraTestCase
{
    /**
     * @param \Laravel\Lumen\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app): array
    {
        return [
            HorizonServiceProvider::class,
        ];
    }
}
