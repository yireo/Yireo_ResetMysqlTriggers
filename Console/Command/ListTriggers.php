<?php declare(strict_types=1);

namespace Yireo\ResetMysqlTriggers\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yireo\ResetMysqlTriggers\Console\OutputSanitizer;
use Yireo\ResetMysqlTriggers\Util\TriggerQuery;

class ListTriggers extends Command
{
    public function __construct(
        private readonly TriggerQuery $triggerQuery,
        ?string $name = null
    ) {
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
                OutputSanitizer::sanitize($trigger['TRIGGER_NAME'] ?? ''),
                OutputSanitizer::sanitize($trigger['ACTION_TIMING'] ?? ''),
                OutputSanitizer::sanitize($trigger['EVENT_OBJECT_TABLE'] ?? ''),
                OutputSanitizer::sanitize($trigger['EVENT_MANIPULATION'] ?? ''),
            ]);
        }

        $table->render();

        return Command::SUCCESS;
    }
}
