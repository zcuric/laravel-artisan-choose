<?php

declare(strict_types=1);

namespace Zdravko\LaravelArtisanChoose\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Zdravko\LaravelArtisanChoose\ArtisanChooseServiceProvider;

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
