<?php

declare(strict_types=1);

namespace TakeshiYu\Linker;

use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;
use TakeshiYu\Linker\Commands\LinkCommand;

class CommandProvider implements CommandProviderCapability
{
    /**
     * Retrieves an array of commands
     *
     * @return \Composer\Command\BaseCommand[]
     */
    public function getCommands()
    {
        return [
            new LinkCommand,
        ];
    }
}
