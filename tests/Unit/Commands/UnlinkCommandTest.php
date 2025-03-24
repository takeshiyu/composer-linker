<?php

declare(strict_types=1);

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use TakeshiYu\Composer\Linker\Commands\LinkCommand;
use TakeshiYu\Composer\Linker\Commands\UnlinkCommand;
use TakeshiYu\Composer\Linker\LinkerService;

setupTestEnvironment();

describe('unlink command', function () {
    it('can unlink a package', function () {
        $packagePath = createMockPackage('test/package', 'test-package');
        chdir($packagePath);
        $command = new LinkCommand;
        $input = new ArrayInput([]);
        $output = new BufferedOutput;
        $exitCode = $command->run($input, $output);
        expect($exitCode)->toBe(LinkCommand::SUCCESS);

        $projectPath = createMockPackage('project', 'project');
        chdir($projectPath);
        mkdir($projectPath.'/vendor', 0777, true);
        $command = new LinkCommand;
        $input = new ArrayInput(['path-or-name' => 'test/package']);
        $output = new BufferedOutput;
        $exitCode = $command->run($input, $output);
        expect($exitCode)->toBe(LinkCommand::SUCCESS);
        $linkService = new LinkerService;
        $linkedPackages = $linkService->getLinkedPackages();
        expect($linkedPackages)->toHaveKey('test/package');

        $command = new UnlinkCommand;
        $input = new ArrayInput(['package' => 'test/package']);
        $output = new BufferedOutput;
        $exitCode = $command->run($input, $output);
        expect($exitCode)->toBe(UnlinkCommand::SUCCESS);
        $outputContent = $output->fetch();
        expect($outputContent)->toContain('has been unlinked');

        $unlinkService = new LinkerService;
        $unlinkedPackages = $unlinkService->getLinkedPackages();
        expect($unlinkedPackages)->not->toHaveKey('test/package');
    });

    it('returns error when trying to unlink a non-linked package', function () {
        $projectPath = createMockPackage('test/project', 'test-project');
        chdir($projectPath);

        $command = new UnlinkCommand;
        $input = new ArrayInput(['package' => 'non-existent/package']);
        $output = new BufferedOutput;

        $exitCode = $command->run($input, $output);
        expect($exitCode)->toBe(UnlinkCommand::FAILURE);

        $outputContent = $output->fetch();
        expect($outputContent)->toContain('is not linked in this project');
    });

});
