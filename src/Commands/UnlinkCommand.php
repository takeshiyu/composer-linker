<?php

declare(strict_types=1);

namespace TakeshiYu\Composer\Linker\Commands;

use Composer\Command\BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TakeshiYu\Composer\Linker\LinkerService;

class UnlinkCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('unlink')
            ->setDescription('Unlink a local package and restore the installed version')
            ->addArgument(
                'package',
                InputArgument::REQUIRED,
                'Name of the package to unlink'
            )
            ->setHelp(<<<'EOT'
The <info>unlink</info> command removes a link to a local package.

It restores the package to the installed version from Packagist or other repository.

    <info>composer unlink vendor/package</info>  Remove link and restore installed version

You can see all linked packages with the <info>composer linked</info> command.
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $linkerService = new LinkerService;
        $packageName = $input->getArgument('package');

        $result = $linkerService->unlink($packageName);

        if ($result['success']) {
            $output->writeln("<info>{$result['message']}</info>");
        } else {
            $output->writeln("<e>{$result['message']}</e>");

            return 1;
        }

        return 0;
    }
}
