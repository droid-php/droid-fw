<?php

namespace Droid\Plugin\Fw;

class DroidPlugin
{
    public function __construct($droid)
    {
        $this->droid = $droid;
    }
    
    public function getCommands()
    {
        $commands = [];
        $commands[] = new \Droid\Plugin\Fw\Command\FwGenerateCommand();
        return $commands;
    }
}
