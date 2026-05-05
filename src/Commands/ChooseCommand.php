<?php

declare(strict_types=1);

namespace Zdravko\LaravelArtisanChoose\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use RuntimeException;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

use function Laravel\Prompts\search;
use function Laravel\Prompts\text;

class ChooseCommand extends Command
{
    protected $signature = 'choose';

    protected $description = 'Choose and run an Artisan command from an interactive searchable list';

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
            info: fn (?string $value): ?string => $value === null ? null : $this->commandInfo($commands, $value),
            hint: 'Press enter on an empty search box to browse the full list.',
        );

        $extraInput = trim(text(
            label: 'Additional arguments / options (optional)',
            placeholder: '--force',
            hint: 'Leave empty to run the selected command as-is.',
        ));

        $input = trim($selectedCommand.' '.$extraInput);

        return $application->run(new StringInput($input), $this->output);
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{name: string, description: string, aliases: array<int, string>, synopsis: string}>
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
     * @param  \Illuminate\Support\Collection<int, array{name: string, description: string, aliases: array<int, string>, synopsis: string}>  $commands
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
     * @param  \Illuminate\Support\Collection<int, array{name: string, description: string, aliases: array<int, string>, synopsis: string}>  $commands
     */
    protected function commandInfo(Collection $commands, string $name): ?string
    {
        $command = $commands->firstWhere('name', $name);

        if ($command === null) {
            return null;
        }

        $details = array_filter([
            $command['description'] !== '' ? $command['description'] : 'No description provided.',
            $command['aliases'] !== [] ? 'Aliases: '.implode(', ', $command['aliases']) : null,
            $command['synopsis'] !== '' ? 'Usage: php artisan '.$command['synopsis'] : null,
        ]);

        return implode(PHP_EOL, $details);
    }
}
