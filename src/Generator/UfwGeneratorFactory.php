<?php

namespace Droid\Plugin\Fw\Generator;

use Droid\Model\Feature\Firewall\Firewall;
use Droid\Model\Inventory\Inventory;

use Droid\Plugin\Fw\Model\UfwFirewall;

class UfwGeneratorFactory
{
    public function makeUfwGenerator(Inventory $inventory)
    {
        return new UfwGenerator(
            new UfwFirewall(
                new Firewall($inventory)
            )
        );
    }
}
