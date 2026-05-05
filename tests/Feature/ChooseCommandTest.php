<?php

declare(strict_types=1);

namespace CastelCode\LaravelArtisanChoose\Tests\Feature;

use CastelCode\LaravelArtisanChoose\Commands\ChooseCommand;
use CastelCode\LaravelArtisanChoose\Support\CommandInputPrompter;
use CastelCode\LaravelArtisanChoose\Tests\TestCase;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Collection;
use Illuminate\Testing\PendingCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ChooseCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->registerFixtureCommands();
    }

    public function test_an_empty_search_returns_every_available_command_name(): void
    {
        $command = new class(new CommandInputPrompter) extends ChooseCommand
        {
            /**
             * @param  Collection<int, array{name: string, description: string, aliases: array<int, string>, synopsis: string}>  $commands
             * @return array<int, string>
             */
            public function searchableNames(Collection $commands, string $query): array
            {
                return $this->searchableCommandNames($commands, $query);
            }
        };

        $commands = collect([
            [
                'name' => 'demo:bundle',
                'description' => 'Bundle multiple items together',
                'aliases' => [],
                'synopsis' => 'demo:bundle {items*} {--tag=*}',
            ],
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
        ]);

        $this->assertSame(
            ['demo:bundle', 'demo:greet', 'demo:hello'],
            $command->searchableNames($commands, '')
        );
    }

    public function test_it_generates_prompts_for_required_arguments_and_flags(): void
    {
        $this->requireSearchAssertions();

        $this->artisan('choose')
            ->expectsSearch('Choose an Artisan command', 'demo:greet', 'person', [
                'demo:greet',
            ])
            ->expectsQuestion('Value for name', 'Taylor')
            ->expectsConfirmation('Enable --yell?', 'yes')
            ->expectsQuestion('Additional raw arguments / options (optional)', '')
            ->expectsOutput('HELLO, TAYLOR!')
            ->assertSuccessful();
    }

    public function test_it_can_find_commands_by_alias(): void
    {
        $this->requireSearchAssertions();

        $this->artisan('choose')
            ->expectsSearch('Choose an Artisan command', 'demo:hello', 'welcome', [
                'demo:hello',
            ])
            ->expectsQuestion('Additional raw arguments / options (optional)', '')
            ->expectsOutput('Hello from demo:hello')
            ->assertSuccessful();
    }

    public function test_it_generates_forms_for_optional_values_and_negatable_options(): void
    {
        $this->requireSearchAssertions();

        $this->artisan('choose')
            ->expectsSearch('Choose an Artisan command', 'demo:configure', 'defaults', [
                'demo:configure',
            ])
            ->expectsChoice('How should name be set?', 'default', [
                'default' => 'Use default value',
                'custom' => 'Enter custom value',
            ])
            ->expectsChoice('How should --cache be set?', 'disable', [
                'default' => 'Keep default behavior',
                'enable' => 'Enable',
                'disable' => 'Disable',
            ])
            ->expectsChoice('How should --mode be set?', 'custom', [
                'default' => 'Keep default behavior',
                'flag' => 'Use option without a value',
                'custom' => 'Enter a value',
            ])
            ->expectsQuestion('Value for --mode', 'fast')
            ->expectsQuestion('Additional raw arguments / options (optional)', '')
            ->expectsOutput('name=guest cache=off mode=fast')
            ->assertSuccessful();
    }

    public function test_it_generates_prompts_for_array_arguments_and_options(): void
    {
        $this->requireSearchAssertions();

        $this->artisan('choose')
            ->expectsSearch('Choose an Artisan command', 'demo:bundle', 'bundle', [
                'demo:bundle',
            ])
            ->expectsQuestion('Value 1 for items', 'alpha')
            ->expectsConfirmation('Add another value for items?', 'yes')
            ->expectsQuestion('Value 2 for items', 'beta')
            ->expectsConfirmation('Add another value for items?', 'no')
            ->expectsConfirmation('Add values for --tag?', 'yes')
            ->expectsQuestion('Value 1 for --tag', 'red')
            ->expectsConfirmation('Add another value for --tag?', 'yes')
            ->expectsQuestion('Value 2 for --tag', 'blue')
            ->expectsConfirmation('Add another value for --tag?', 'no')
            ->expectsQuestion('Additional raw arguments / options (optional)', '')
            ->expectsOutput('items=alpha,beta tags=red,blue')
            ->assertSuccessful();
    }

    public function test_raw_input_can_override_generated_prompts_for_advanced_cases(): void
    {
        $this->requireSearchAssertions();

        $this->artisan('choose')
            ->expectsSearch('Choose an Artisan command', 'demo:greet', 'person', [
                'demo:greet',
            ])
            ->expectsQuestion('Value for name', 'Taylor')
            ->expectsConfirmation('Enable --yell?', 'no')
            ->expectsQuestion('Additional raw arguments / options (optional)', '--yell')
            ->expectsOutput('HELLO, TAYLOR!')
            ->assertSuccessful();
    }

    public function test_it_filters_out_hidden_commands_and_the_chooser_itself(): void
    {
        $this->requireSearchAssertions();

        $this->artisan('choose')
            ->expectsSearch('Choose an Artisan command', 'demo:hello', 'demo:', [
                'demo:bundle',
                'demo:configure',
                'demo:greet',
                'demo:hello',
                'demo:sync',
            ])
            ->expectsQuestion('Additional raw arguments / options (optional)', '')
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
            protected $signature = 'demo:configure';

            protected $description = 'Configure defaults and cache usage';

            protected function configure(): void
            {
                parent::configure();

                $this->addArgument('name', InputArgument::OPTIONAL, 'Profile name', 'guest');
                $this->addOption('cache', null, InputOption::VALUE_NEGATABLE, 'Use cache');
                $this->addOption('mode', null, InputOption::VALUE_OPTIONAL, 'Execution mode', 'smart');
            }

            public function handle(): int
            {
                $cache = $this->option('cache');
                $cacheState = $cache === false ? 'off' : ($cache === true ? 'on' : 'auto');
                $mode = $this->option('mode');
                $modeValue = $mode === null ? '(flag)' : $mode;

                $this->line(sprintf(
                    'name=%s cache=%s mode=%s',
                    $this->argument('name'),
                    $cacheState,
                    $modeValue,
                ));

                return self::SUCCESS;
            }
        });

        $this->app[Kernel::class]->registerCommand(new class extends Command
        {
            protected $signature = 'demo:bundle';

            protected $description = 'Bundle multiple items together';

            protected function configure(): void
            {
                parent::configure();

                $this->addArgument('items', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'Bundle items');
                $this->addOption('tag', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Bundle tags');
            }

            public function handle(): int
            {
                $this->line(sprintf(
                    'items=%s tags=%s',
                    implode(',', $this->argument('items')),
                    implode(',', $this->option('tag')),
                ));

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

    private function requireSearchAssertions(): void
    {
        if (! method_exists(PendingCommand::class, 'expectsSearch')) {
            $this->markTestSkipped('Search prompt assertions are not available on this Laravel version.');
        }
    }
}
