<?php

declare(strict_types=1);

namespace TakeshiYu\Linker\Commands;

use Composer\Command\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TakeshiYu\Linker\LinkerService;

class LinkedCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('linked')
            ->setDescription('List all linked packages')
            ->addOption(
                'global',
                'g',
                InputOption::VALUE_NONE,
                'Show globally registered packages instead of project-linked packages'
            )
            ->setHelp(<<<'EOT'
The <info>linked</info> command shows all packages that are currently linked.

    <info>composer linked</info>           Show packages linked in this project
    <info>composer linked --global</info>  Show all globally registered packages

Links are stored in:
- Project links: <comment>.composer-links/</comment> directory in your project
- Global links: <comment>~/.composer/links/</comment> directory
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $linkerService = new LinkerService;
        $showGlobal = $input->getOption('global');

        if ($showGlobal) {
            $packages = $linkerService->getRegisteredPackages();

            if (empty($packages)) {
                $output->writeln('No packages are globally registered.');

                return 0;
            }

            $output->writeln('<info>Globally registered packages:</info>');
            $output->writeln(str_repeat('-', 100));
            $output->writeln(sprintf('%-30s %-60s %s', 'Package', 'Path', 'Status'));
            $output->writeln(str_repeat('-', 100));

            foreach ($packages as $package) {
                $status = $package['exists'] ? '<info>Available</info>' : '<e>Missing</e>';
                $output->writeln(sprintf(
                    '%-30s %-60s %s',
                    $package['name'],
                    $this->truncatePath($package['path'], 60),
                    $status
                ));
            }
        } else {
            $links = $linkerService->getLinkedPackages();

            if (empty($links)) {
                $output->writeln('No packages are linked in this project.');

                return 0;
            }

            $output->writeln('<info>Packages linked in this project:</info>');
            $output->writeln(str_repeat('-', 100));
            $output->writeln(sprintf('%-30s %-60s %s', 'Package', 'Path', 'Status'));
            $output->writeln(str_repeat('-', 100));

            foreach ($links as $link) {
                $status = $link['exists'] ? '<info>Available</info>' : '<e>Missing</e>';
                $output->writeln(sprintf(
                    '%-30s %-60s %s',
                    $link['name'],
                    $this->truncatePath($link['path'], 60),
                    $status
                ));
            }
        }

        return 0;
    }

    /**
     * Truncate a path for display if it's too long
     *
     * @param  string  $path
     * @param  int  $maxLength
     * @return string
     */
    private function truncatePath($path, $maxLength)
    {
        if (strlen($path) <= $maxLength) {
            return $path;
        }

        // Keep the last part of the path
        $parts = explode(DIRECTORY_SEPARATOR, $path);
        $filename = array_pop($parts);

        // Start with the root and filename
        $root = $parts[0] ?? '';
        $result = $root.DIRECTORY_SEPARATOR.'...'.DIRECTORY_SEPARATOR.$filename;

        // Add more path parts from the end until we reach the max length
        $i = count($parts) - 1;
        while ($i > 0 && strlen($result) + strlen($parts[$i]) + 1 <= $maxLength) {
            $result = $root.DIRECTORY_SEPARATOR.'...'.DIRECTORY_SEPARATOR.
                     $parts[$i].DIRECTORY_SEPARATOR.basename($result);
            $i--;
        }

        return $result;
    }
}
