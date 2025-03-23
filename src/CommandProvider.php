<?php

declare(strict_types=1);

namespace TakeshiYu\Composer\Linker;

use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;
use TakeshiYu\Composer\Linker\Commands\LinkCommand;
use TakeshiYu\Composer\Linker\Commands\LinkedCommand;
use TakeshiYu\Composer\Linker\Commands\UnlinkCommand;

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
