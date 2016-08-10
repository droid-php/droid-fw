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
        $output->write($script . "\n");
    }
}
