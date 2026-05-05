<?php

declare(strict_types=1);

namespace CastelCode\LaravelArtisanChoose\Support;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class CommandInputPrompter
{
    /**
     * @return array<string, mixed>
     */
    public function promptFor(Command $command): array
    {
        $parameters = [];
        $definition = $command->getNativeDefinition();

        foreach ($definition->getArguments() as $argument) {
            $response = $this->promptForArgument($argument);

            if ($response['include']) {
                $parameters[$argument->getName()] = $response['value'];
            }
        }

        foreach ($definition->getOptions() as $option) {
            $response = $this->promptForOption($option);

            if ($response['include']) {
                $parameters['--'.$option->getName()] = $response['value'];
            }
        }

        return $parameters;
    }

    /**
     * @return array{include: bool, value?: mixed}
     */
    protected function promptForArgument(InputArgument $argument): array
    {
        if ($argument->isArray()) {
            return $this->promptForArrayValues(
                label: $argument->getName(),
                description: $argument->getDescription(),
                required: $argument->isRequired(),
                default: is_array($argument->getDefault()) ? $argument->getDefault() : [],
                kind: 'argument',
            );
        }

        if ($argument->isRequired()) {
            return [
                'include' => true,
                'value' => text(
                    label: "Value for {$argument->getName()}",
                    required: "The {$argument->getName()} argument is required.",
                    hint: $this->buildHint($argument->getDescription()),
                ),
            ];
        }

        $default = $argument->getDefault();

        if ($default !== null) {
            $choice = select(
                label: "How should {$argument->getName()} be set?",
                options: [
                    'default' => 'Use default value',
                    'custom' => 'Enter custom value',
                ],
                default: 'default',
                hint: $this->buildHint(
                    $argument->getDescription(),
                    ['Default: '.$this->stringifyValue($default)],
                ),
            );

            if ($choice === 'default') {
                return ['include' => false];
            }

            return [
                'include' => true,
                'value' => text(
                    label: "Value for {$argument->getName()}",
                    default: $this->stringifyValue($default),
                    hint: $this->buildHint($argument->getDescription()),
                ),
            ];
        }

        if (! confirm(
            label: "Provide {$argument->getName()}?",
            default: false,
            hint: $this->buildHint($argument->getDescription()),
        )) {
            return ['include' => false];
        }

        return [
            'include' => true,
            'value' => text(
                label: "Value for {$argument->getName()}",
                hint: $this->buildHint($argument->getDescription()),
            ),
        ];
    }

    /**
     * @return array{include: bool, value?: mixed}
     */
    protected function promptForOption(InputOption $option): array
    {
        $label = '--'.$option->getName();
        $description = $option->getDescription();

        if ($option->isNegatable()) {
            $choice = select(
                label: "How should {$label} be set?",
                options: [
                    'default' => 'Keep default behavior',
                    'enable' => 'Enable',
                    'disable' => 'Disable',
                ],
                default: 'default',
                hint: $this->buildHint($description, ['Default: automatic']),
            );

            return match ($choice) {
                'enable' => ['include' => true, 'value' => true],
                'disable' => ['include' => true, 'value' => false],
                default => ['include' => false],
            };
        }

        if (! $option->acceptValue()) {
            return confirm(
                label: "Enable {$label}?",
                default: (bool) $option->getDefault(),
                hint: $this->buildHint($description),
            )
                ? ['include' => true, 'value' => true]
                : ['include' => false];
        }

        if ($option->isArray()) {
            return $this->promptForArrayValues(
                label: $label,
                description: $description,
                required: false,
                default: is_array($option->getDefault()) ? $option->getDefault() : [],
                kind: 'option',
            );
        }

        if ($option->isValueRequired()) {
            if (! confirm(
                label: "Set {$label}?",
                default: false,
                hint: $this->buildHint($description),
            )) {
                return ['include' => false];
            }

            return [
                'include' => true,
                'value' => text(
                    label: "Value for {$label}",
                    required: "The {$label} option requires a value.",
                    hint: $this->buildHint($description),
                ),
            ];
        }

        $default = $option->getDefault();

        if ($default !== null) {
            $choice = select(
                label: "How should {$label} be set?",
                options: [
                    'default' => 'Keep default behavior',
                    'flag' => 'Use option without a value',
                    'custom' => 'Enter a value',
                ],
                default: 'default',
                hint: $this->buildHint(
                    $description,
                    ['Default: '.$this->stringifyValue($default)],
                ),
            );

            return match ($choice) {
                'flag' => ['include' => true, 'value' => null],
                'custom' => [
                    'include' => true,
                    'value' => text(
                        label: "Value for {$label}",
                        default: $this->stringifyValue($default),
                        hint: $this->buildHint($description),
                    ),
                ],
                default => ['include' => false],
            };
        }

        $choice = select(
            label: "How should {$label} be set?",
            options: [
                'skip' => 'Skip',
                'flag' => 'Use option without a value',
                'custom' => 'Enter a value',
            ],
            default: 'skip',
            hint: $this->buildHint($description),
        );

        return match ($choice) {
            'flag' => ['include' => true, 'value' => null],
            'custom' => [
                'include' => true,
                'value' => text(
                    label: "Value for {$label}",
                    hint: $this->buildHint($description),
                ),
            ],
            default => ['include' => false],
        };
    }

    /**
     * @param  array<int, mixed>  $default
     * @return array{include: bool, value?: array<int, string>}
     */
    protected function promptForArrayValues(
        string $label,
        string $description,
        bool $required,
        array $default,
        string $kind,
    ): array {
        if ($default !== []) {
            $choice = select(
                label: "How should {$label} be set?",
                options: [
                    'default' => 'Use default values',
                    'custom' => 'Enter custom values',
                ],
                default: 'default',
                hint: $this->buildHint(
                    $description,
                    ['Default: '.$this->stringifyArray($default)],
                ),
            );

            if ($choice === 'default') {
                return ['include' => false];
            }
        } elseif (! $required && ! confirm(
            label: 'Add values for '.$label.'?',
            default: false,
            hint: $this->buildHint($description),
        )) {
            return ['include' => false];
        }

        $values = [];
        $index = 1;

        do {
            $values[] = text(
                label: "Value {$index} for {$label}",
                required: "At least one {$kind} value is required.",
                hint: $index === 1 ? $this->buildHint($description) : '',
            );

            $index++;
        } while (confirm(
            label: 'Add another value for '.$label.'?',
            default: false,
        ));

        return ['include' => true, 'value' => $values];
    }

    /**
     */
    protected function buildHint(string $description, array $extra = []): string
    {
        $parts = array_values(array_filter([
            trim($description) !== '' ? trim($description) : null,
            ...$extra,
        ], static fn (?string $value): bool => $value !== null && $value !== ''));

        return $parts === [] ? '' : implode(PHP_EOL, $parts);
    }

    protected function stringifyValue(mixed $value): string
    {
        return match (true) {
            is_bool($value) => $value ? 'true' : 'false',
            $value === null => '',
            is_scalar($value) => (string) $value,
            default => json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '',
        };
    }

    /**
     * @param  array<int, mixed>  $values
     */
    protected function stringifyArray(array $values): string
    {
        return implode(', ', array_map(
            fn (mixed $value): string => $this->stringifyValue($value),
            $values,
        ));
    }
}
