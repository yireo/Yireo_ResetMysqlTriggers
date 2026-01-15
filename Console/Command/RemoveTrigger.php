<?php

namespace Yireo\ResetMysqlTriggers\Console\Command;

use Symfony\Component\Console\Command\Command;
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
        $this->addOption('all', 'a', InputOption::VALUE_NONE, 'Remove all triggers');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $all = $input->getOption('all');
        foreach ($this->triggerQuery->getTriggers() as $trigger) {
            if (false === $all) {
                continue;
            }

            $this->triggerQuery->removeTrigger($trigger['TRIGGER_NAME']);
        }

        return Command::SUCCESS;
    }
}
