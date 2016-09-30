<?php

namespace Droid\Test\Plugin\Fw\Command;

use RuntimeException;

use Droid\Model\Inventory\Host;
use Droid\Model\Inventory\Inventory;
use org\bovigo\vfs\vfsStream;
use SSHClient\Client\ClientInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

use Droid\Plugin\Fw\Command\FwInstallCommand;
use Droid\Plugin\Fw\Generator\UfwGenerator;
use Droid\Plugin\Fw\Generator\UfwGeneratorFactory;

class FwInstallCommandTest extends \PHPUnit_Framework_TestCase
{
    protected $app;
    protected $fac;
    protected $generator;
    protected $host;
    protected $inventory;
    protected $ssh;
    protected $tester;
    protected $vfs;
    protected $test_host_name = 'some-host';

    protected function setUp()
    {
        $this->app = new Application;
        $this->vfs = vfsStream::setup('tmp');

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
        $this->ssh = $this
            ->getMockBuilder(ClientInterface::class)
            ->disableOriginalConstructor()
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
        $this
            ->host
            ->method('getScpClient')
            ->willReturn($this->ssh)
        ;
        $this
            ->host
            ->method('getSshClient')
            ->willReturn($this->ssh)
        ;

        $command = new FwInstallCommand($this->fac, vfsStream::url('tmp'));
        $command->setInventory($this->inventory);

        $this->tester = new CommandTester($command);
        $this->app->add($command);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessageRegExp /^I do not have a host named "[^"]+" in my Inventory/
     */
    public function testInstallWithUnknownHostArgWillThrowException()
    {
        $this
            ->inventory
            ->expects($this->once())
            ->method('hasHost')
            ->with('some-unknown-host')
            ->willReturn(false)
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('fw:install')->getName(),
            'hostname' => 'some-unknown-host',
        ));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage I do not have any hosts in my Inventory
     */
    public function testInstallWithoutHostArgAndEmptyInventoryWillThrowException()
    {
        $this
            ->inventory
            ->expects($this->once())
            ->method('getHosts')
            ->willReturn(array())
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('fw:install')->getName(),
        ));
    }

    public function testInstallWhenGenerationFailsWillPrintError()
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
            'command' => $this->app->find('fw:install')->getName(),
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

    public function testInstallWithHostArgWithoutRulesWillPrintWarning()
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
            'command' => $this->app->find('fw:install')->getName(),
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

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessageRegExp /^I could not copy the script "[^"]*" to the host "some-host": err/
     */
    public function testInstallWhenScriptCannotBeCopiedToHostWillThrowException()
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
            ->willReturn('rulez script')
        ;
        $this
            ->ssh
            ->expects($this->once())
            ->method('getRemotePath')
            ->willReturn('some-remote-path')
        ;
        $this
            ->ssh
            ->expects($this->once())
            ->method('copy')
        ;
        $this
            ->ssh
            ->expects($this->once())
            ->method('getExitCode')
            ->willReturn(1)
        ;
        $this
            ->ssh
            ->expects($this->once())
            ->method('getErrorOutput')
            ->willReturn('err')
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('fw:install')->getName(),
            'hostname' => $this->test_host_name,
        ));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessageRegExp /^I could not execute the script "[^"]*" on the host "some-host": err/
     */
    public function testInstallWhenScriptCannotBeExecutedOnHostWillThrowException()
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
            ->willReturn('rulez script')
        ;
        $this
            ->ssh
            ->expects($this->once())
            ->method('getRemotePath')
            ->willReturn('some-remote-path')
        ;
        $this
            ->ssh
            ->expects($this->exactly(2))
            ->method('getExitCode')
            ->willReturnOnConsecutiveCalls(0, 1)
        ;
        $this
            ->ssh
            ->expects($this->once())
            ->method('copy')
        ;
        $this
            ->ssh
            ->expects($this->once())
            ->method('exec')
        ;
        $this
            ->ssh
            ->expects($this->once())
            ->method('getErrorOutput')
            ->willReturn('err')
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('fw:install')->getName(),
            'hostname' => $this->test_host_name,
        ));
    }

    public function testInstallWithHostArgWillGenerateAndUploadAndExecuteScriptOnNamedHost()
    {
        $pathMatcher = $this->matchesRegularExpression('@^vfs://tmp/[0-9a-z]{16,16}$@');

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
            ->willReturn('rulez script')
        ;
        $this
            ->ssh
            ->expects($this->once())
            ->method('getRemotePath')
            ->with($pathMatcher)
            ->willReturn('some-remote-path')
        ;
        $this
            ->ssh
            ->expects($this->exactly(2))
            ->method('getExitCode')
            ->willReturnOnConsecutiveCalls(0, 0)
        ;
        $this
            ->ssh
            ->expects($this->once())
            ->method('copy')
            ->with(
                $pathMatcher,
                $this->equalTo('some-remote-path'),
                $this->identicalTo(null),
                $this->identicalTo(null),
                $this->identicalTo(true)
            )
        ;
        $this
            ->ssh
            ->expects($this->once())
            ->method('exec')
            ->with(
                $this->callback(function ($x) use ($pathMatcher) {
                    return '/bin/sh' === $x[0]
                        && $pathMatcher->evaluate(
                            $x[1],
                            'The path to the script is passed as the second arg to exec',
                            true
                        );
                }),
                $this->identicalTo(null),
                $this->identicalTo(null),
                $this->identicalTo(true)
            )
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('fw:install')->getName(),
            'hostname' => $this->test_host_name,
        ));

        $this->assertRegExp(
            sprintf(
                '/I have successfully activated the firewall rules on host "%s"./',
                $this->test_host_name
            ),
            $this->tester->getDisplay()
        );
    }

    public function testInstallWithoutHostArgWillGenerateAndUploadAndExecuteScriptOnKnownHosts()
    {
        $pathMatcher = $this->matchesRegularExpression('@^vfs://tmp/[0-9a-z]{16,16}$@');

        $this
            ->inventory
            ->expects($this->atLeastOnce())
            ->method('getHosts')
            ->willReturn(array($this->host))
        ;
        $this
            ->generator
            ->method('generate')
            ->willReturn('rulez script')
        ;
        $this
            ->ssh
            ->expects($this->once())
            ->method('getRemotePath')
            ->with($pathMatcher)
            ->willReturn('some-remote-path')
        ;
        $this
            ->ssh
            ->expects($this->exactly(2))
            ->method('getExitCode')
            ->willReturnOnConsecutiveCalls(0, 0)
        ;
        $this
            ->ssh
            ->expects($this->once())
            ->method('copy')
            ->with(
                $pathMatcher,
                $this->equalTo('some-remote-path'),
                $this->identicalTo(null),
                $this->identicalTo(null),
                $this->identicalTo(true)
            )
        ;
        $this
            ->ssh
            ->expects($this->once())
            ->method('exec')
            ->with(
                $this->callback(function ($x) use ($pathMatcher) {
                    return '/bin/sh' === $x[0]
                        && $pathMatcher->evaluate(
                            $x[1],
                            'The path to the script is passed as the second arg to exec',
                            true
                        );
                }),
                $this->identicalTo(null),
                $this->identicalTo(null),
                $this->identicalTo(true)
            )
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('fw:install')->getName()
        ));

        $this->assertRegExp(
            sprintf(
                '/I have successfully activated the firewall rules on host "%s"./',
                $this->test_host_name
            ),
            $this->tester->getDisplay()
        );
    }
}
