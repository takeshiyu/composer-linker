<?php

declare(strict_types=1);

namespace TakeshiYu\Linker;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

class LinkerPlugin implements Capable, PluginInterface
{
    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * Apply plugin modifications to Composer
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    /**
     * Remove any hooks from Composer
     */
    public function deactivate(Composer $composer, IOInterface $io)
    {
        // Nothing to do here
    }

    /**
     * Prepare the plugin to be uninstalled
     */
    public function uninstall(Composer $composer, IOInterface $io)
    {
        // Clean up global links directory if needed
        $linkerService = new LinkerService;
        $globalLinksDir = $linkerService->getGlobalLinksDir();

        if (is_dir($globalLinksDir)) {
            $answer = $io->askConfirmation(
                'Do you want to remove all composer-linker global registry data? [y/N] ',
                false
            );

            if ($answer) {
                $io->write("<info>Removing composer-linker global registry at {$globalLinksDir}</info>");

                // Use Symfony Filesystem to safely remove the directory
                $fs = new Filesystem;
                try {
                    $fs->remove($globalLinksDir);
                    $io->write('<info>Global registry has been removed.</info>');
                } catch (IOExceptionInterface $e) {
                    $io->writeError('<error>Failed to remove directory: '.$e->getMessage().'</error>');
                }
            } else {
                $io->write('<info>Preserving composer-linker global registry. You can manually remove it at:</info>');
                $io->write("<comment>{$globalLinksDir}</comment>");
            }
        }
    }

    /**
     * Tell Composer which capabilities this plugin has
     *
     * @return array
     */
    public function getCapabilities()
    {
        return [
            'Composer\Plugin\Capability\CommandProvider' => 'TakeshiYu\Linker\CommandProvider',
        ];
    }
}
