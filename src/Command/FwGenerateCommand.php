<?php

namespace Droid\Plugin\Fw\Command;

use RuntimeException;

use Droid\Model\Feature\Firewall\Firewall;
use Droid\Model\Inventory\Inventory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Droid\Plugin\Fw\Generator\UfwGenerator;

class FwGenerateCommand extends Command
{
    protected $inventory;
    protected $tempPath;

    public function __construct($tempPath, $name = null)
    {
        $this->tempPath = $tempPath;
        return parent::__construct($name);
    }

    public function configure()
    {
        $this->setName('fw:generate')
            ->setDescription('Generate firewall for given host')
            ->addArgument(
                'hostname',
                InputArgument::REQUIRED,
                'Name of the host to generate the firewall for'
            )
        ;
    }

    public function setInventory(Inventory $inventory)
    {
        $this->inventory = $inventory;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $host = $this->inventory->getHost($input->getArgument('hostname'));

        $output->writeLn("Generating firewall for: " . $host->getName());
        if (!$this->inventory) {
            throw new RuntimeException("Inventory not defined.");
        }

        $firewall = new Firewall($this->inventory);
        $generator = new UfwGenerator($firewall);
        $script = $generator->generate($host->getName());
        if ($script === null) {
            throw new RuntimeException(
                sprintf(
                    'There are no rules defined for host "%s".',
                    $host->getName()
                )
            );
        }

        $tempPath = $this->tempFilePath($host->getName());

        $this->writeToFile($script, $tempPath);

        $scpClient = $host->getScpClient();
        $scpClient->copy($tempPath, $scpClient->getRemotePath($tempPath));
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
        $sshClient->exec(array('/bin/sh', $tempPath));
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
