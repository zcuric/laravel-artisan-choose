<?php

declare(strict_types=1);

namespace CastelCode\LaravelArtisanChoose\Tests;

use CastelCode\LaravelArtisanChoose\ArtisanChooseServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            ArtisanChooseServiceProvider::class,
        ];
    }
}
