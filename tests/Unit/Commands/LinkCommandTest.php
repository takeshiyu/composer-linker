<?php

declare(strict_types=1);

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use TakeshiYu\Composer\Linker\Commands\LinkCommand;
use TakeshiYu\Composer\Linker\LinkerService;

setupTestEnvironment();

describe('link command', function () {
    it('can register current directory when no arguments are provided', function () {
        $packagePath = createMockPackage('test/package', 'test-package');
        chdir($packagePath);

        $command = new LinkCommand;
        $input = new ArrayInput([]);
        $output = new BufferedOutput;

        $exitCode = $command->run($input, $output);
        expect($exitCode)->toBe(LinkCommand::SUCCESS);

        $outputContent = $output->fetch();
        expect($outputContent)
            ->toContain('has been registered globally')
            ->and($outputContent)->toContain('test/package');

        $linkerService = new LinkerService;
        $registeredPackages = $linkerService->getRegisteredPackages();

        expect($registeredPackages)->toHaveKey('test/package');
        expect($registeredPackages['test/package']['path'])->toBe($packagePath);
    });

    it('can register a package when a path is provided', function () {
        $packagePath = createMockPackage('test/package', 'test-package');

        $command = new LinkCommand;
        $input = new ArrayInput(['path-or-name' => $packagePath]);
        $output = new BufferedOutput;

        $exitCode = $command->run($input, $output);
        expect($exitCode)->toBe(LinkCommand::SUCCESS);

        $outputContent = $output->fetch();
        expect($outputContent)
            ->toContain('has been registered globally')
            ->and($outputContent)->toContain('test/package');

        $linkerService = new LinkerService;
        $registeredPackages = $linkerService->getRegisteredPackages();

        expect($registeredPackages)->toHaveKey('test/package');
        expect($registeredPackages['test/package']['path'])->toBe($packagePath);
    });

    it('can link a globally registered package to current project', function () {
        $packagePath = createMockPackage('test/package', 'test-package');
        chdir($packagePath);
        $command = new LinkCommand;
        $input = new ArrayInput([]);
        $output = new BufferedOutput;
        $command->run($input, $output);

        $projectPath = createMockPackage('project', 'project');
        chdir($projectPath);
        mkdir($projectPath.'/vendor', 0777, true);

        $command = new LinkCommand;
        $input = new ArrayInput(['path-or-name' => 'test/package']);
        $output = new BufferedOutput;
        $exitCode = $command->run($input, $output);
        expect($exitCode)->toBe(LinkCommand::SUCCESS);

        $outputContent = $output->fetch();
        expect($outputContent)
            ->toContain('has been linked to the current project')
            ->and($outputContent)->toContain('test/package');

        $projectService = new LinkerService;
        $projectLinks = $projectService->getLinkedPackages();
        expect($projectLinks)->toHaveKey('test/package');

        $linkPath = $projectPath.'/vendor/test/package';
        expect(is_link($linkPath))->toBeTrue();
        expect(realpath($linkPath))->toBe(realpath($packagePath));
    });
});
