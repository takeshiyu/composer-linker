<?php

declare(strict_types=1);

namespace TakeshiYu\Linker\Commands;

use Composer\Command\BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TakeshiYu\Linker\LinkerService;

class LinkCommand extends BaseCommand
{
    /**
     * Configures the current command.
     */
    protected function configure(): void
    {
        $this
            ->setName('link')
            ->setDescription('Link a local package for development')
            ->addArgument(
                'path-or-name',
                InputArgument::OPTIONAL,
                'Path to local package or name of globally registered package'
            )
            ->setHelp(<<<'EOT'
The <info>link</info> command helps you work with local packages during development.

Usage:

    <info>composer link</info>                   Register the current directory as a linkable package
    <info>composer link /path/to/package</info>  Register a specific directory as a linkable package
    <info>composer link vendor/package</info>    Link to a globally registered package in your project

This is similar to npm link, but adapted for Composer's workflow.
EOT
            );
    }

    /**
     * Executes the current command.
     *
     * @return int 0 if everything went fine, or an exit code
     *
     * @throws LogicException When this abstract method is not implemented
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $linkerService = new LinkerService;
        $pathOrName = $input->getArgument('path-or-name');

        // No argument - register current directory
        if (empty($pathOrName)) {
            $result = $linkerService->register(getcwd());

            if ($result['success']) {
                $output->writeln("<info>{$result['message']}</info>");
                $output->writeln('');
                $output->writeln('To use this package in another project, run:');
                $output->writeln("<comment>  composer link {$result['package']}</comment>");
            } else {
                $output->writeln("<error>{$result['message']}</error>");

                return 1;
            }

            return 0;
        }

        // If the argument is a path (directory)
        if (is_dir($pathOrName)) {
            $result = $linkerService->register($pathOrName);

            if ($result['success']) {
                $output->writeln("<info>{$result['message']}</info>");
                $output->writeln('');
                $output->writeln('To use this package in another project, run:');
                $output->writeln("<comment>  composer link {$result['package']}</comment>");
            } else {
                $output->writeln("<error>{$result['message']}</error>");

                return 1;
            }

            return 0;
        }

        // If the argument is a package name, link to this project
        if (strpos($pathOrName, '/') !== false) {
            $result = $linkerService->link($pathOrName);

            if ($result['success']) {
                $output->writeln("<info>{$result['message']}</info>");
                $output->writeln("Package path: <comment>{$result['path']}</comment>");
            } else {
                $output->writeln("<error>{$result['message']}</error>");

                return 1;
            }

            return 0;
        }

        $output->writeln('<error>Invalid argument. Please provide a valid directory path or package name.</error>');

        return 1;
    }
}
