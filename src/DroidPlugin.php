<?php

namespace Droid\Plugin\Fw;

use Droid\Plugin\Fw\Command\FwGenerateCommand;
use Droid\Plugin\Fw\Command\FwInstallCommand;
use Droid\Plugin\Fw\Generator\UfwGeneratorFactory;

class DroidPlugin
{
    public function __construct($droid)
    {
        $this->droid = $droid;
    }

    public function getCommands()
    {
        $fac = new UfwGeneratorFactory;

        return array(
            new FwGenerateCommand($fac),
            new FwInstallCommand($fac, '/tmp')
        );
    }
}
