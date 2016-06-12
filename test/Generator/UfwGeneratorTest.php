<?php

namespace Droid\Test\Plugin\Fw\Generator;

use Droid\Model\Feature\Firewall\Firewall;
use Droid\Model\Inventory\Inventory;

use Droid\Plugin\Fw\Generator\UfwGenerator;

class UfwGeneratorTest extends \PHPUnit_Framework_TestCase
{
    protected $firewall;
    protected $inventory;

    protected function setUp()
    {
        $this->inventory = $this
            ->getMockBuilder(Inventory::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->firewall = $this
            ->getMockBuilder(Firewall::class)
            ->setConstructorArgs(array($this->inventory))
            ->getMock()
        ;
    }

    public function testICanLoadUfwGenerator()
    {
        new UfwGenerator($this->firewall);
    }
}
