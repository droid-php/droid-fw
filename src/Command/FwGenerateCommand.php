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

    protected $inventory;

    public function setInventory(Inventory $inventory)
    {
        $this->inventory = $inventory;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $hostname = $input->getArgument('hostname');

        $output->writeLn("Generating firewall for: " . $input->getArgument('hostname'));
        if (!$this->inventory) {
            throw new RuntimeException("Inventory not defined.");
        }

        $firewall = new Firewall($this->inventory);

        $rules = $firewall->getRulesByHostname($hostname);
        $generator = new UfwGenerator($firewall);
        $o = $generator->generate($hostname);
        echo $o;
    }
}
