<?php

declare(strict_types=1);

namespace CastelCode\LaravelArtisanChoose\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use CastelCode\LaravelArtisanChoose\ArtisanChooseServiceProvider;

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
