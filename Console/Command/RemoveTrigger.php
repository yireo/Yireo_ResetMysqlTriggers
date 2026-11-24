<?php declare(strict_types=1);

namespace Yireo\ResetMysqlTriggers\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Throwable;
use Yireo\ResetMysqlTriggers\Console\OutputSanitizer;
use Yireo\ResetMysqlTriggers\Util\TriggerQuery;

class RemoveTrigger extends Command
{
    public function __construct(
        private readonly TriggerQuery $triggerQuery,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName('db:trigger:remove');
        $this->setDescription('Remove a trigger or all triggers');
        $this->addArgument('trigger', InputArgument::OPTIONAL, 'Trigger name');
        $this->addOption('all', 'a', InputOption::VALUE_NONE, 'Remove all triggers');
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Remove all triggers without confirmation');
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Only show which triggers would be removed');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $all = (bool)$input->getOption('all');
        $triggerName = (string)$input->getArgument('trigger');

        if ($all === ($triggerName !== '')) {
            $output->writeln('<error>Specify either a trigger name or use the option --all</error>');
            return Command::INVALID;
        }

        $triggerNames = $all ? $this->getAllTriggerNames() : [$triggerName];
        if (!$all && !$this->triggerQuery->triggerExists($triggerName)) {
            $output->writeln(sprintf(
                '<error>Trigger "%s" not found</error>',
                OutputSanitizer::sanitize($triggerName)
            ));
            return Command::FAILURE;
        }

        if ($triggerNames === []) {
            $output->writeln('No triggers found');
            return Command::SUCCESS;
        }

        if ($input->getOption('dry-run')) {
            foreach ($triggerNames as $name) {
                $output->writeln('Would remove trigger: ' . OutputSanitizer::sanitize($name));
            }
            return Command::SUCCESS;
        }

        if ($all && !$input->getOption('force') && !$this->confirm($input, $output, count($triggerNames))) {
            $output->writeln('<error>Aborted: use --force to remove all triggers without confirmation</error>');
            return Command::FAILURE;
        }

        return $this->removeTriggers($triggerNames, $output);
    }

    /**
     * @return string[]
     */
    private function getAllTriggerNames(): array
    {
        return array_map(
            static fn (array $trigger): string => (string)$trigger['TRIGGER_NAME'],
            $this->triggerQuery->getTriggers()
        );
    }

    private function confirm(InputInterface $input, OutputInterface $output, int $count): bool
    {
        if (!$input->isInteractive()) {
            return false;
        }

        $question = new ConfirmationQuestion(
            sprintf('Remove all %d triggers from the database? [y/N] ', $count),
            false
        );

        return (bool)(new QuestionHelper())->ask($input, $output, $question);
    }

    /**
     * @param string[] $triggerNames
     */
    private function removeTriggers(array $triggerNames, OutputInterface $output): int
    {
        $failed = false;
        $removedMviewTrigger = false;

        foreach ($triggerNames as $name) {
            try {
                $this->triggerQuery->removeTrigger($name);
            } catch (Throwable $exception) {
                $failed = true;
                $output->writeln(sprintf(
                    '<error>Failed to remove trigger "%s": %s</error>',
                    OutputSanitizer::sanitize($name),
                    OutputSanitizer::sanitize($exception->getMessage())
                ));
                continue;
            }

            $output->writeln('Removed trigger: ' . OutputSanitizer::sanitize($name));
            if (str_starts_with($name, 'trg_')) {
                $removedMviewTrigger = true;
            }
        }

        if ($removedMviewTrigger) {
            $output->writeln(
                '<comment>Indexer triggers were removed. Recreate them with: '
                . 'bin/magento indexer:set-mode schedule</comment>'
            );
        }

        return $failed ? Command::FAILURE : Command::SUCCESS;
    }
}
