<?php

declare(strict_types=1);

namespace CastelCode\LaravelArtisanChoose;

use Illuminate\Support\ServiceProvider;
use CastelCode\LaravelArtisanChoose\Commands\ChooseCommand;

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
