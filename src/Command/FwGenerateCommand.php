<?php

namespace Droid\Plugin\Fw\Command;

use RuntimeException;

use Droid\Model\Inventory\Inventory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Droid\Plugin\Fw\Generator\UfwGeneratorFactory;

class FwGenerateCommand extends Command
{
    protected $inventory;

    private $fac;
    private $generator;

    public function __construct(UfwGeneratorFactory $factory, $name = null)
    {
        $this->fac = $factory;
        return parent::__construct($name);
    }

    public function configure()
    {
        $this->setName('fw:generate')
            ->setDescription('Generate and print UFW firewall rules for an Inventory host or all Inventory hosts.')
            ->addArgument(
                'hostname',
                InputArgument::OPTIONAL,
                'Name of a host for which to generate and print firewall rules'
            )
        ;
    }

    public function setInventory(Inventory $inventory)
    {
        $this->inventory = $inventory;
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

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $hostNames = array();

        $hostArg = $input->getArgument('hostname');
        if ($hostArg) {
            $hostNames[] = $hostArg;
        } else {
            $hostNames = array_map(
                function ($x) {
                    return $x->getName();
                },
                $this->inventory->getHosts()
            );
        }

        foreach ($hostNames as $hostName) {
            $script = null;
            try {
                $script = $this->getGenerator()->generate($hostName);
            } catch (RuntimeException $e) {
                $output->writeLn(
                    sprintf(
                        '<error>I cannot generate rules for the host "%s": %s</error>',
                        $hostName,
                        $e->getMessage()
                    )
                );
                continue;
            }
            if ($script === null) {
                $output->writeLn(
                    sprintf('<comment>No rules are defined for, or apply to the host "%s".</comment>', $hostName)
                );
                continue;
            }
            $output->writeLn(sprintf('<info>Rules for the host "%s":-</info>', $hostName));
            $output->write($script . "\n");
        }
    }

    private function getGenerator()
    {
        if (!$this->generator) {
            $this->generator = $this->fac->makeUfwGenerator($this->inventory);
        }
        return $this->generator;
    }
}
