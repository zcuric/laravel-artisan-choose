<?php

declare(strict_types=1);

namespace CastelCode\LaravelArtisanChoose;

use CastelCode\LaravelArtisanChoose\Commands\ChooseCommand;
use Illuminate\Support\ServiceProvider;

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
