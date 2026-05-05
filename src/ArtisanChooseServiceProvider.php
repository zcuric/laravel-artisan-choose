<?php

declare(strict_types=1);

namespace Zdravko\LaravelArtisanChoose;

use Illuminate\Support\ServiceProvider;
use Zdravko\LaravelArtisanChoose\Commands\ChooseCommand;

class ArtisanChooseServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ChooseCommand::class,
            ]);
        }
    }
}
