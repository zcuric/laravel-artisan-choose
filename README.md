# Laravel Artisan Choose

`php artisan choose` adds a searchable command picker to Laravel, similar to `just --choose`.

## Installation

```bash
composer require zdravko/laravel-artisan-choose
```

Laravel package discovery will register the service provider automatically.

## Usage

Run the chooser:

```bash
php artisan choose
```

The command opens a searchable list of visible Artisan commands:

- Press `Enter` on an empty search box to browse the full list.
- Type to filter commands by name, alias, or description.
- Use the arrow keys to highlight a command.
- Press `Enter` to select it.

After selecting a command, you can optionally enter raw Artisan arguments / options such as:

```text
Taylor --yell
--force
--seed --database=testing
```

The chosen command is then executed through the current Artisan application, so existing prompts and output still work normally.

## Behavior

- Hidden Artisan commands are excluded.
- The chooser command does not list itself.
- Command details show the description, aliases, and usage while you browse.

## Testing

```bash
composer test
```
