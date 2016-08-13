<?php

namespace Droid\Test\Plugin\Fw\Command;

use RuntimeException;

use Droid\Model\Feature\Firewall\Rule;
use Droid\Model\Inventory\Host;
use Droid\Model\Inventory\Inventory;
use org\bovigo\vfs\vfsStream;
use SSHClient\Client\ClientInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

use Droid\Plugin\Fw\Command\FwInstallCommand;

class FwInstallCommandTest extends \PHPUnit_Framework_TestCase
{
    protected $app;
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

        $command = new FwInstallCommand(vfsStream::url('tmp'));
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
            'command' => $this->app->find('fw:install')->getName(),
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
            'command' => $this->app->find('fw:install')->getName(),
            'hostname' => $this->test_host_name,
        ));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessageRegExp /^I could not copy the script "[^"]*" to the host "some-host": err/
     */
    public function testCommandThrowsExceptionWhenFailingToCopyScriptToHost()
    {
        $rule = new Rule;
        $rule->setAddress('0.0.0.0/0');
        $rule->setPort(22);

        $this
            ->host
            ->method('getRules')
            ->willReturn(array($rule))
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
    public function testCommandThrowsExceptionWhenFailingToExecuteScriptOnHost()
    {
        $rule = new Rule;
        $rule->setAddress('0.0.0.0/0');
        $rule->setPort(22);

        $this
            ->host
            ->method('getRules')
            ->willReturn(array($rule))
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

    public function testCommandGeneratesAndUploadsAndExecutesScript()
    {
        $pathMatcher = $this->matchesRegularExpression('@^vfs://tmp/[0-9a-z]{16,16}$@');

        $rule = new Rule;
        $rule->setAddress('0.0.0.0/0')->setPort(22);

        $this
            ->host
            ->method('getRules')
            ->willReturn(array($rule))
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
                'some-remote-path'
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
                })
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
}
