<?php

namespace Droid\Test\Plugin\Fw\Command;

use Droid\Model\Inventory\Host;
use Droid\Model\Inventory\Inventory;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

use Droid\Plugin\Fw\Command\FwGenerateCommand;

class FwGenerateCommandTest extends \PHPUnit_Framework_TestCase
{
    protected $app;
    protected $host;
    protected $inventory;
    protected $tester;

    protected function setUp()
    {
        $this->app = new Application;

        $this->host = $this
            ->getMockBuilder(Host::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->inventory = $this
            ->getMockBuilder(Inventory::class)
            ->getMock()
        ;

        $command = new FwGenerateCommand;
        $command->setInventory($this->inventory);

        $this->tester = new CommandTester($command);
        $this->app->add($command);
    }

    public function testCommandIsSane()
    {
        $this
            ->inventory
            ->method('getHost')
            ->with('some-host')
            ->willReturn($this->host)
        ;
        $this
            ->inventory
            ->method('getHostGroups')
            ->willReturn(array())
        ;
        $this
            ->host
            ->method('getRules')
            ->willReturn(array())
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('fw:generate')->getName(),
            'hostname' => 'some-host',
        ));
    }
}
