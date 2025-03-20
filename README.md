# Composer Linker

A Composer plugin for linking local packages during development, similar to `npm link`.

## Requirements

* Composer 2.0+
* PHP 8.0+
* Filesystem that supports symbolic links

## Installation

```bash
composer global require yourname/composer-linker
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

### Unlink a package

Restore a package to the installed version:

```bash
composer unlink vendor/package
```

## License

`Composer Linker` package is open-sourced software licensed under the [MIT license](LICENSE)