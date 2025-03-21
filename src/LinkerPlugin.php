<?php

declare(strict_types=1);

namespace TakeshiYu\Linker;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;

class LinkerPlugin implements Capable, PluginInterface
{
    protected Composer $composer;

    protected IOInterface $io;

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
        // Nothing to do here
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
