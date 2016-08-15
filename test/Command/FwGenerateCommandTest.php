<?php

namespace Droid\Test\Plugin\Fw\Command;

use RuntimeException;

use Droid\Model\Inventory\Host;
use Droid\Model\Inventory\Inventory;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

use Droid\Plugin\Fw\Command\FwGenerateCommand;
use Droid\Plugin\Fw\Generator\UfwGenerator;
use Droid\Plugin\Fw\Generator\UfwGeneratorFactory;

class FwGenerateCommandTest extends \PHPUnit_Framework_TestCase
{
    protected $app;
    protected $fac;
    protected $generator;
    protected $host;
    protected $inventory;
    protected $tester;
    protected $test_host_name = 'some-host';

    protected function setUp()
    {
        $this->app = new Application;

        $this->fac = $this
            ->getMockBuilder(UfwGeneratorFactory::class)
            ->getMock()
        ;
        $this->generator = $this
            ->getMockBuilder(UfwGenerator::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
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
            ->fac
            ->method('makeUfwGenerator')
            ->willReturn($this->generator)
        ;
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

        $command = new FwGenerateCommand($this->fac);
        $command->setInventory($this->inventory);

        $this->tester = new CommandTester($command);
        $this->app->add($command);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessageRegExp /^I do not have a host named "[^"]+" in my Inventory/
     */
    public function testGenerateWithUnknownHostArgWillThrowException()
    {
        $this
            ->inventory
            ->expects($this->once())
            ->method('hasHost')
            ->with('some-unknown-host')
            ->willReturn(false)
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('fw:generate')->getName(),
            'hostname' => 'some-unknown-host',
        ));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage I do not have any hosts in my Inventory
     */
    public function testGenerateWithoutHostArgAndEmptyInventoryWillThrowException()
    {
        $this
            ->inventory
            ->expects($this->once())
            ->method('getHosts')
            ->willReturn(array())
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('fw:generate')->getName(),
        ));
    }

    public function testGenerateWhenGenerationFailsWillPrintError()
    {
        $this
            ->inventory
            ->expects($this->once())
            ->method('hasHost')
            ->with($this->test_host_name)
            ->willReturn(true)
        ;
        $this
            ->generator
            ->method('generate')
            ->willThrowException(new RuntimeException)
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('fw:generate')->getName(),
            'hostname' => $this->test_host_name,
        ));

        $this->assertRegExp(
            sprintf(
                '/I cannot generate rules for the host "%s"/',
                $this->test_host_name
            ),
            $this->tester->getDisplay()
        );
    }

    public function testGenerateWithHostArgWithoutRulesWillPrintWarning()
    {
        $this
            ->inventory
            ->expects($this->once())
            ->method('hasHost')
            ->with($this->test_host_name)
            ->willReturn(true)
        ;
        $this
            ->generator
            ->method('generate')
            ->willReturn(null)
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('fw:generate')->getName(),
            'hostname' => $this->test_host_name,
        ));

        $this->assertRegExp(
            sprintf(
                '/No rules are defined for, or apply to the host "%s"/',
                $this->test_host_name
            ),
            $this->tester->getDisplay()
        );
    }

    public function testGenerateWithHostArgWillPrintRulesForNamedHost()
    {
        $this
            ->inventory
            ->expects($this->once())
            ->method('hasHost')
            ->with($this->test_host_name)
            ->willReturn(true)
        ;
        $this
            ->generator
            ->method('generate')
            ->willReturn(sprintf('# Generated by Droid for host `%s`', $this->test_host_name))
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('fw:generate')->getName(),
            'hostname' => $this->test_host_name,
        ));

        $this->assertRegExp(
            sprintf('/Rules for the host "%s"/', $this->test_host_name),
            $this->tester->getDisplay()
        );
        $this->assertRegExp(
            sprintf('/# Generated by Droid for host `%s`/', $this->test_host_name),
            $this->tester->getDisplay()
        );
    }

    public function testGenerateWithoutHostArgWillPrintRulesForKnownHosts()
    {
        $this
            ->inventory
            ->expects($this->atLeastOnce())
            ->method('getHosts')
            ->willReturn(array($this->host))
        ;
        $this
            ->generator
            ->expects($this->once())
            ->method('generate')
            ->with($this->test_host_name)
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('fw:generate')->getName(),
        ));
    }
}
