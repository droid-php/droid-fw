<?php

namespace Droid\Test\Plugin\Fw\Command;

use RuntimeException;

use Droid\Model\Feature\Firewall\Rule;
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
    protected $test_host_name = 'some-host';

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

        # define the behaviour of some commonly called methods
        $this
            ->inventory
            ->method('getHost')
            ->with($this->test_host_name)
            ->willReturn($this->host)
        ;
        $this
            ->inventory
            ->method('getHostGroups')
            ->willReturn(array())
        ;
        $this
            ->host
            ->method('getName')
            ->willReturn($this->test_host_name)
        ;

        $command = new FwGenerateCommand;
        $command->setInventory($this->inventory);

        $this->tester = new CommandTester($command);
        $this->app->add($command);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testCommandThrowsExceptionWhenHostIsUnknown()
    {
        $this
            ->inventory
            ->method('getHost')
            ->with('some-unknown-host')
            ->willThrowException(new RuntimeException)
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('fw:generate')->getName(),
            'hostname' => 'some-unknown-host',
        ));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage There are no rules defined for host "some-host"
     */
    public function testCommandThrowsExceptionWhenHostHasZeroRules()
    {
        $this
            ->host
            ->method('getRules')
            ->willReturn(array())
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('fw:generate')->getName(),
            'hostname' => $this->test_host_name,
        ));
    }
}
