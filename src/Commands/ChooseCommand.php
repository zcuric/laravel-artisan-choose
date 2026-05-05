<?php

declare(strict_types=1);

namespace CastelCode\LaravelArtisanChoose\Commands;

use CastelCode\LaravelArtisanChoose\Support\CommandInputPrompter;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Collection;
use RuntimeException;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\StringInput;

use function Laravel\Prompts\search;
use function Laravel\Prompts\text;

class ChooseCommand extends Command
{
    protected $signature = 'choose';

    protected $description = 'Choose and run an Artisan command from an interactive searchable list';

    public function __construct(
        protected CommandInputPrompter $prompter,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $application = $this->getApplication();

        if ($application === null) {
            throw new RuntimeException('The Artisan application is not available.');
        }

        $commands = $this->availableCommands();

        if ($commands->isEmpty()) {
            $this->components->error('No visible Artisan commands are available to choose from.');

            return self::FAILURE;
        }

        $selectedCommand = search(
            label: 'Choose an Artisan command',
            placeholder: 'Type to filter commands...',
            options: fn (?string $value): array => $this->searchableCommandNames($commands, $value ?? ''),
            scroll: min(12, max(1, $commands->count())),
            hint: 'Press enter on an empty search box to browse the full list.',
        );

        $command = $application->find($selectedCommand);
        $parameters = $this->prompter->promptFor($command);
        $extraInput = trim(text(
            label: 'Additional raw arguments / options (optional)',
            placeholder: '--force',
            hint: 'Use this for advanced cases or to override the generated prompts.',
        ));

        if ($extraInput !== '') {
            $commandString = $this->buildCommandString(
                commandName: $selectedCommand,
                command: $command,
                parameters: $parameters,
                rawInput: $extraInput,
            );

            return $this->laravel->make(Kernel::class)->call($commandString, [], $this->output);
        }

        return $this->laravel->make(Kernel::class)->call($selectedCommand, $parameters, $this->output);
    }

    /**
     * @return Collection<int, array{name: string, description: string, aliases: array<int, string>, synopsis: string}>
     */
    protected function availableCommands(): Collection
    {
        return collect($this->getApplication()?->all() ?? [])
            ->filter(fn (SymfonyCommand $command, string $name): bool => $name === $command->getName())
            ->reject(fn (SymfonyCommand $command): bool => $command->isHidden())
            ->reject(fn (SymfonyCommand $command): bool => $command->getName() === $this->getName())
            ->map(fn (SymfonyCommand $command): array => [
                'name' => (string) $command->getName(),
                'description' => trim((string) $command->getDescription()),
                'aliases' => $command->getAliases(),
                'synopsis' => trim($command->getSynopsis()),
            ])
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    /**
     * @param  Collection<int, array{name: string, description: string, aliases: array<int, string>, synopsis: string}>  $commands
     * @return array<int, string>
     */
    protected function searchableCommandNames(Collection $commands, string $query): array
    {
        $query = mb_strtolower(trim($query));

        return $commands
            ->filter(function (array $command) use ($query): bool {
                if ($query === '') {
                    return true;
                }

                if (str_contains(mb_strtolower($command['name']), $query)) {
                    return true;
                }

                if ($command['description'] !== '' && str_contains(mb_strtolower($command['description']), $query)) {
                    return true;
                }

                foreach ($command['aliases'] as $alias) {
                    if (str_contains(mb_strtolower($alias), $query)) {
                        return true;
                    }
                }

                return false;
            })
            ->pluck('name')
            ->all();
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    protected function buildCommandString(
        string $commandName,
        SymfonyCommand $command,
        array $parameters,
        string $rawInput,
    ): string {
        $parts = [$commandName];
        $definition = $command->getNativeDefinition();

        foreach ($definition->getArguments() as $argument) {
            if (! array_key_exists($argument->getName(), $parameters)) {
                continue;
            }

            $value = $parameters[$argument->getName()];

            if (is_array($value)) {
                foreach ($value as $item) {
                    $parts[] = $this->escapeToken((string) $item);
                }

                continue;
            }

            $parts[] = $this->escapeToken((string) $value);
        }

        foreach ($definition->getOptions() as $option) {
            $key = '--'.$option->getName();

            if (! array_key_exists($key, $parameters)) {
                continue;
            }

            $value = $parameters[$key];

            if ($option->isNegatable()) {
                $parts[] = $value === false ? '--no-'.$option->getName() : '--'.$option->getName();

                continue;
            }

            if (! $option->acceptValue()) {
                if ($value) {
                    $parts[] = '--'.$option->getName();
                }

                continue;
            }

            if ($option->isArray()) {
                foreach ((array) $value as $item) {
                    $parts[] = '--'.$option->getName().'='.$this->escapeToken((string) $item);
                }

                continue;
            }

            if ($value === null && $option->isValueOptional()) {
                $parts[] = '--'.$option->getName();

                continue;
            }

            $parts[] = '--'.$option->getName().'='.$this->escapeToken((string) $value);
        }

        return trim(implode(' ', $parts).' '.$rawInput);
    }

    protected function escapeToken(string $token): string
    {
        return (new StringInput(''))->escapeToken($token);
    }
}
