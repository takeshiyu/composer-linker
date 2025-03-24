# Composer Linker

A Composer plugin for linking local packages during development, similar to `npm link`.

<p align="center">
    <a href="https://github.com/takeshiyu/composer-linker/actions/workflows/php.yml"><img src="https://github.com/takeshiyu/composer-linker/actions/workflows/php.yml/badge.svg" alt="Build Status"></a>
</p>

## Key Features

- **No project files** - All link information is stored in the global Composer directory
- **Simple workflow** - Register once, link anywhere
- **Clean development** - No modifications to your composer.json
- **Automatic backups** - Original packages are preserved and can be restored

## Requirements

* Composer 2.0+
* PHP 7.2+
* Filesystem that supports symbolic links

## Installation

```bash
composer global require takeshiyu/composer-linker
```

## Usage

### Register a local package

First, register your local package globally:

```bash
# In your package directory
composer link

# Or specify a path
composer link /path/to/your/package
```

This makes the package available for linking in any project.

### Link a package to your project

In your project directory, link to a registered package:

```bash
composer link vendor/package
```

This creates a symbolic link in your vendor directory pointing to the local package source.

### List linked packages

Show all packages linked in your current project:

```bash
composer linked
```

Show all globally registered packages:

```bash
composer linked --global
```

Show all projects and their linked packages:

```bash
composer linked --all
```

### Unlink a package

Restore a package to the installed version:

```bash
composer unlink vendor/package
```

## How it works

This plugin:

1. Stores all link information in a central links.json file in ~/.composer/links/
2. Uses symbolic links to connect your project to local packages
3. Preserves your composer.json file (no modifications needed)
4. Works at the filesystem level, similar to npm link
5. Never creates any files in your project directory

## Benefits

* No files in your project directory that could be committed to version control
* Consistent workflow across projects
* Easy to switch between development and production versions
* Works with Composer's autoloading system

## License

`Composer Linker` package is open-sourced software licensed under the [MIT license](LICENSE)
