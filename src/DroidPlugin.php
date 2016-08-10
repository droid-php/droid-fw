<?php

namespace Droid\Plugin\Fw;

use Droid\Plugin\Fw\Command\FwGenerateCommand;
use Droid\Plugin\Fw\Command\FwInstallCommand;

class DroidPlugin
{
    public function __construct($droid)
    {
        $this->droid = $droid;
    }

    public function getCommands()
    {
        return array(
            new FwGenerateCommand(),
            new FwInstallCommand('/tmp')
        );
    }
}
