<?php

namespace Droid\Plugin\Fw;

use Droid\Plugin\Fw\Command\FwGenerateCommand;

class DroidPlugin
{
    public function __construct($droid)
    {
        $this->droid = $droid;
    }

    public function getCommands()
    {
        return array(
            new FwGenerateCommand('/tmp')
        );
    }
}
