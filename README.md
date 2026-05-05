# Laravel Artisan Choose

`php artisan choose` adds a searchable command picker to Laravel, inspired by [`just --choose`](https://github.com/casey/just#selecting-recipes-to-run-with-an-interactive-chooser).

Instead of remembering every Artisan command name, you can open an interactive chooser, search by command name, alias, or description, inspect command details, and run the selected command immediately.

## Features

- Search visible Artisan commands by name, alias, or description
- Browse the full command list by pressing `Enter` on an empty search box
- See command description, aliases, and usage while moving through the list
- Run the selected command with optional raw arguments and options
- Keep normal Artisan behavior after selection, including nested prompts and console output

## Requirements

- PHP 8.2+
- Laravel 10, 11, or 12

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
5. Optionally enter extra Artisan arguments or options.

For example, after choosing a command you can enter:

```text
Taylor --yell
--force
--seed --database=testing
```

The chooser then executes the selected command through the current Artisan application.

## Examples

Choose a command and run it with no extra input:

```bash
php artisan choose
```

Choose `migrate` and then enter:

```text
--force
```

Choose `make:controller` and then enter:

```text
Admin/UserController --resource
```

## Behavior

- Hidden Artisan commands are excluded from the chooser.
- The `choose` command does not list itself.
- Command aliases are searchable.
- Command descriptions are searchable.
- Usage details are shown in the side information area while browsing.

## Testing

```bash
composer test
```

## License

MIT
