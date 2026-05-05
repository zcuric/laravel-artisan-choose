<?php

declare(strict_types=1);

namespace CastelCode\LaravelArtisanChoose\Tests\Feature;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Collection;
use CastelCode\LaravelArtisanChoose\Commands\ChooseCommand;
use CastelCode\LaravelArtisanChoose\Tests\TestCase;

class ChooseCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->registerFixtureCommands();
    }

    public function test_an_empty_search_returns_every_available_command_name(): void
    {
        $command = new class extends ChooseCommand
        {
            /**
             * @param  \Illuminate\Support\Collection<int, array{name: string, description: string, aliases: array<int, string>, synopsis: string}>  $commands
             * @return array<int, string>
             */
            public function searchableNames(Collection $commands, string $query): array
            {
                return $this->searchableCommandNames($commands, $query);
            }

            /**
             * @param  \Illuminate\Support\Collection<int, array{name: string, description: string, aliases: array<int, string>, synopsis: string}>  $commands
             */
            public function infoFor(Collection $commands, ?string $name): ?string
            {
                return $name === null ? null : $this->commandInfo($commands, $name);
            }
        };

        $commands = collect([
            [
                'name' => 'demo:greet',
                'description' => 'Greet a person by name',
                'aliases' => [],
                'synopsis' => 'demo:greet {name} {--yell}',
            ],
            [
                'name' => 'demo:hello',
                'description' => 'Say hello from a demo command',
                'aliases' => ['demo:welcome'],
                'synopsis' => 'demo:hello',
            ],
            [
                'name' => 'demo:sync',
                'description' => 'Synchronize demo fixtures',
                'aliases' => [],
                'synopsis' => 'demo:sync',
            ],
        ]);

        $this->assertSame(
            ['demo:greet', 'demo:hello', 'demo:sync'],
            $command->searchableNames($commands, '')
        );

        $this->assertNull($command->infoFor($commands, null));
    }

    public function test_it_can_pass_raw_arguments_and_options_to_the_selected_command(): void
    {
        $this->artisan('choose')
            ->expectsSearch('Choose an Artisan command', 'demo:greet', 'person', [
                'demo:greet',
            ])
            ->expectsQuestion('Additional arguments / options (optional)', 'Taylor --yell')
            ->expectsOutput('HELLO, TAYLOR!')
            ->assertSuccessful();
    }

    public function test_it_can_find_commands_by_alias(): void
    {
        $this->artisan('choose')
            ->expectsSearch('Choose an Artisan command', 'demo:hello', 'welcome', [
                'demo:hello',
            ])
            ->expectsQuestion('Additional arguments / options (optional)', '')
            ->expectsOutput('Hello from demo:hello')
            ->assertSuccessful();
    }

    public function test_it_filters_out_hidden_commands_and_the_chooser_itself(): void
    {
        $this->artisan('choose')
            ->expectsSearch('Choose an Artisan command', 'demo:hello', 'demo:', [
                'demo:greet',
                'demo:hello',
                'demo:sync',
            ])
            ->expectsQuestion('Additional arguments / options (optional)', '')
            ->expectsOutput('Hello from demo:hello')
            ->assertSuccessful();
    }

    private function registerFixtureCommands(): void
    {
        $this->app[Kernel::class]->registerCommand(new class extends Command
        {
            protected $signature = 'demo:hello';

            protected $description = 'Say hello from a demo command';

            protected function configure(): void
            {
                parent::configure();

                $this->setAliases(['demo:welcome']);
            }

            public function handle(): int
            {
                $this->line('Hello from demo:hello');

                return self::SUCCESS;
            }
        });

        $this->app[Kernel::class]->registerCommand(new class extends Command
        {
            protected $signature = 'demo:greet {name} {--yell}';

            protected $description = 'Greet a person by name';

            public function handle(): int
            {
                $greeting = 'Hello, '.$this->argument('name').'!';

                $this->line($this->option('yell') ? strtoupper($greeting) : $greeting);

                return self::SUCCESS;
            }
        });

        $this->app[Kernel::class]->registerCommand(new class extends Command
        {
            protected $signature = 'demo:sync';

            protected $description = 'Synchronize demo fixtures';

            public function handle(): int
            {
                $this->line('Synced demo fixtures');

                return self::SUCCESS;
            }
        });

        $this->app[Kernel::class]->registerCommand(new class extends Command
        {
            protected $signature = 'demo:hidden';

            protected $description = 'A hidden demo command';

            protected $hidden = true;

            public function handle(): int
            {
                return self::SUCCESS;
            }
        });
    }
}
