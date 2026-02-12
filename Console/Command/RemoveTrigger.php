<?php

namespace Yireo\ResetMysqlTriggers\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Yireo\ResetMysqlTriggers\Util\TriggerQuery;

class RemoveTrigger extends Command
{
    public function __construct(
        private TriggerQuery $triggerQuery,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName('db:trigger:remove');
        $this->setDescription('Reset triggers');
        $this->addArgument('trigger', InputArgument::OPTIONAL , 'Trigger name');
        $this->addOption('all', 'a', InputOption::VALUE_NONE, 'Remove all triggers');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $all = $input->getOption('all');
        $triggerName = $input->getArgument('trigger');

        if (false === $all && empty($triggerName)) {
            $output->writeln('<error>Specify either a trigger name or use the option --all</error>');
            return Command::FAILURE;
        }

        $triggers = $this->triggerQuery->getTriggers();
        if ($all) {
            foreach ($triggers as $trigger) {
                $this->triggerQuery->removeTrigger($trigger['TRIGGER_NAME']);
            }
        }

        if (!empty($triggerName)) {
            foreach ($triggers as $trigger) {
                if ($trigger['TRIGGER_NAME'] !== $triggerName) {
                    continue;
                }

                $this->triggerQuery->removeTrigger($trigger['TRIGGER_NAME']);
            }
        }

        return Command::SUCCESS;
    }
}
