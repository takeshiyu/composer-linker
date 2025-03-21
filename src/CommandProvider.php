<?php

declare(strict_types=1);

namespace TakeshiYu\Linker;

use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;
use TakeshiYu\Linker\Commands\LinkCommand;
use TakeshiYu\Linker\Commands\LinkedCommand;
use TakeshiYu\Linker\Commands\UnlinkCommand;

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
            new UnlinkCommand,
            new LinkedCommand,
        ];
    }
}
