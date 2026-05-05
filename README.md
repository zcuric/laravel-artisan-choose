# Laravel Artisan Choose

`php artisan choose` adds a searchable command picker to Laravel, inspired by [`just --choose`](https://github.com/casey/just#selecting-recipes-to-run-with-an-interactive-chooser).

Instead of remembering every Artisan command name, you can open an interactive chooser, search by command name, alias, or description, inspect command details, and run the selected command immediately.

<img width="643" height="320" alt="image" src="https://github.com/user-attachments/assets/e0e17895-684a-4b26-98e1-b4e31e6384ea" />

## Features

- Search visible Artisan commands by name, alias, or description
- Browse the full command list by pressing `Enter` on an empty search box
- See command description, aliases, and usage while moving through the list
- Generate interactive forms for command arguments and options
- Keep a raw arguments / options escape hatch for advanced cases
- Keep normal Artisan behavior after selection, including nested prompts and console output

## Requirements

- PHP 8.2+
- Laravel 10, 11, 12, or 13

## Installation

```bash
composer require zcuric/laravel-artisan-choose
```

Laravel package discovery registers the service provider automatically.

## Usage

Start the chooser:

```bash
php artisan choose
```

Interactive flow:

1. Press `Enter` on an empty search box to browse all available commands.
2. Type to narrow the list by command name, alias, or description.
3. Use the arrow keys to highlight a command.
4. Press `Enter` to select it.
5. Fill in the generated prompts for the selected command's arguments and options.
6. Optionally enter extra **raw** Artisan arguments or options for advanced cases.

The generated forms currently handle the most reliable command-definition shapes automatically:

- required and optional arguments
- array arguments
- boolean flags
- negatable options
- required and optional value options
- array value options

For example, after the generated prompts you can still append raw input such as:

```text
Taylor --yell
--force
--seed --database=testing
```

The chooser then executes the selected command through the current Artisan application. Commands with no custom arguments or options skip straight to the raw-input fallback prompt.

## Examples

Choose `demo:greet`-style commands and answer generated prompts for required arguments and flags:

```bash
php artisan choose
```

Choose `migrate` and still use the raw fallback:

```text
--force
```

Choose `make:controller`, answer the generated prompts, and optionally append raw input:

```text
Admin/UserController --resource
```

## Behavior

- Hidden Artisan commands are excluded from the chooser.
- The `choose` command does not list itself.
- Command aliases are searchable.
- Command descriptions are searchable.
- Usage details are shown in the side information area while browsing.
- `choose` generates prompts from the selected command's native Symfony input definition instead of prompting for Artisan's global flags.
- Raw extra input is appended after the generated parameters, so it can handle edge cases or intentionally override prompted values.

## Testing

```bash
composer lint
composer test
```

To apply Pint formatting locally:

```bash
composer format
```

## CI

GitHub Actions runs:

- `composer validate`
- `composer lint`
- `composer test`
- compatibility test runs for Laravel 10, 11, 12, and 13

## Packagist Sync

Prefer enabling the Packagist GitHub integration so releases sync automatically.

If you need the manual GitHub webhook instead, configure:

- **Payload URL:** `https://packagist.org/api/github?username=zcuric`
- **Content Type:** `application/json`
- **Secret:** your Packagist API token
- **Events:** push

## License

MIT
