<?php

declare(strict_types=1);

use Symfony\Component\Filesystem\Filesystem;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)->in('Unit', 'Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Get temporary directory path.
 */
function getTempDirectory(string $suffix = ''): string
{
    $suffix = $suffix ? '/'.$suffix : '';

    return sys_get_temp_dir().'/composer-linker-tests'.'/'.uniqid().$suffix;
}

/**
 * Create temporary directory for tests.
 */
function createTempDirectory(string $suffix = ''): string
{
    $tempDirectory = getTempDirectory($suffix);

    $filesystem = new Filesystem;
    if (! $filesystem->exists($tempDirectory)) {
        $filesystem->mkdir($tempDirectory, 0777);
    }

    return realpath($tempDirectory);
}

/**
 * Clean up temporary directory after tests.
 */
function cleanTempDirectory(): bool
{
    $tempDirectory = getTempDirectory();
    $filesystem = new Filesystem;

    if ($filesystem->exists($tempDirectory)) {
        $filesystem->remove($tempDirectory);
    }

    return true;
}

/**
 * Create a mock package for testing
 */
function createMockPackage(string $name, string $path): string
{
    $packagePath = createTempDirectory($path);
    $filesystem = new Filesystem;

    // Create composer.json
    $filesystem->dumpFile(
        $packagePath.'/composer.json',
        json_encode(['name' => $name], JSON_PRETTY_PRINT)
    );

    return $packagePath;
}

/**
 * Setup a test environment before each test
 */
function setupTestEnvironment(): void
{
    beforeEach(function () {
        createTempDirectory();
    });

    afterEach(function () {
        cleanTempDirectory();
    });
}
