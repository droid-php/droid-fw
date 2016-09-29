<?php

namespace Droid\Plugin\Fw\Command;

use RuntimeException;

use Droid\Model\Inventory\Inventory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Droid\Plugin\Fw\Generator\UfwGeneratorFactory;

class FwInstallCommand extends Command
{
    protected $inventory;
    protected $tempPath;

    private $fac;
    private $generator;


    public function __construct(
        UfwGeneratorFactory $factory,
        $tempPath,
        $name = null
    ) {
        $this->fac = $factory;
        $this->tempPath = $tempPath;
        return parent::__construct($name);
    }

    public function configure()
    {
        $this->setName('fw:install')
            ->setDescription('Generate and install UFW firewall rules on an Inventory host or all Inventory hosts.')
            ->addArgument(
                'hostname',
                InputArgument::OPTIONAL,
                'Name of a host for which to generate and install firewall rules'
            )
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $hostArg = $input->getArgument('hostname');

        if ($hostArg && ! $this->inventory->hasHost($hostArg)) {
            throw new RuntimeException(
                sprintf('I do not have a host named "%s" in my Inventory.', $hostArg)
            );
        } elseif (! $hostArg && ! $this->inventory->getHosts()) {
            throw new RuntimeException(
                'I do not have any hosts in my Inventory.'
            );
        }
    }

    public function setInventory(Inventory $inventory)
    {
        $this->inventory = $inventory;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $hosts = array();

        $hostArg = $input->getArgument('hostname');
        if ($hostArg) {
            $hosts[] = $this->inventory->getHost($hostArg);
        } else {
            $hosts = $this->inventory->getHosts();
        }

        foreach ($hosts as $host) {
            $script = null;
            try {
                $script = $this->getGenerator()->generate($host->getName());
            } catch (RuntimeException $e) {
                $output->writeLn(
                    sprintf(
                        '<error>I cannot generate rules for the host "%s": %s</error>',
                        $host->getName(),
                        $e->getMessage()
                    )
                );
                continue;
            }
            if ($script === null) {
                $output->writeLn(
                    sprintf(
                        '<comment>No rules are defined for, or apply to the host "%s".</comment>',
                        $host->getName()
                    )
                );
                continue;
            }

            $tempPath = $this->tempFilePath($host->getName());

            $this->writeToFile($script, $tempPath);

            $scpClient = $host->getScpClient();
            $scpClient->copy(
                $tempPath,
                $scpClient->getRemotePath($tempPath),
                null,
                null,
                true
            );
            if ($scpClient->getExitCode()) {
                throw new RuntimeException(
                    sprintf(
                        'I could not copy the script "%s" to the host "%s": %s',
                        $tempPath,
                        $host->getName(),
                        $scpClient->getErrorOutput()
                    )
                );
            }

            $sshClient = $host->getSshClient();
            $sshClient->exec(array('/bin/sh', $tempPath), null, null, true);
            if ($sshClient->getExitCode()) {
                throw new RuntimeException(
                    sprintf(
                        'I could not execute the script "%s" on the host "%s": %s',
                        $tempPath,
                        $host->getName(),
                        $sshClient->getErrorOutput()
                    )
                );
            }

            $output->writeLn(
                sprintf(
                    'I have successfully activated the firewall rules on host "%s".',
                    $host->getName()
                )
            );
        }
    }

    private function getGenerator()
    {
        if (!$this->generator) {
            $this->generator = $this->fac->makeUfwGenerator($this->inventory);
        }
        return $this->generator;
    }

    private function tempFilePath($hostname)
    {
        $now = new \DateTime;
        return $this->tempPath . DIRECTORY_SEPARATOR . substr(
            hash(
                'sha256',
                sprintf('%s-%s-%d', $hostname, $now->format('c'), rand())
            ),
            0,
            16
        );
    }

    private function writeToFile($content, $tempFile)
    {
        $fh = fopen($tempFile, 'wb');
        if ($fh === false) {
            throw new RuntimeException(
                sprintf('I cannot write to temporary file "%s".', $tempFile)
            );
        }
        fwrite($fh, $content, strlen($content));
        fclose($fh);
    }
}
