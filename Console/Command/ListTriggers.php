<?php

namespace Yireo\ResetMysqlTriggers\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yireo\ResetMysqlTriggers\Util\TriggerQuery;

class ListTriggers extends Command
{
    public function __construct(
        private TriggerQuery $triggerQuery,
        ?string $name = null)
    {
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName('db:trigger:list');
        $this->setDescription('List all triggers');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $table = new Table($output);
        $table->setHeaders([
            'Trigger name',
            'Action timing',
            'Event object table',
            'Event manipulation',
        ]);

        foreach ($this->triggerQuery->getTriggers() as $trigger) {
            $table->addRow([
                $trigger['TRIGGER_NAME'],
                $trigger['ACTION_TIMING'],
                $trigger['EVENT_OBJECT_TABLE'],
                $trigger['EVENT_MANIPULATION'],
            ]);
        }

        $table->render();

        return Command::SUCCESS;
    }
}
