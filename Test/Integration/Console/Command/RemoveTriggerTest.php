<?php declare(strict_types=1);

namespace Yireo\ResetMysqlTriggers\Test\Integration\Console\Command;

use Magento\Framework\Console\CommandListInterface;
use Magento\TestFramework\Helper\Bootstrap;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Yireo\ResetMysqlTriggers\Console\Command\RemoveTrigger;
use Yireo\ResetMysqlTriggers\Test\Integration\TriggerTestCase;
use Yireo\ResetMysqlTriggers\Util\TriggerQuery;

/**
 * @magentoDbIsolation disabled
 */
class RemoveTriggerTest extends TriggerTestCase
{
    public function testCommandIsRegistered(): void
    {
        $commands = Bootstrap::getObjectManager()->get(CommandListInterface::class)->getCommands();

        $this->assertInstanceOf(RemoveTrigger::class, $commands['Yireo_ResetMysqlTriggers::remove']);
    }

    public function testRemovesGivenTrigger(): void
    {
        $this->createTrigger('yireo.test`odd name');
        $this->createTrigger('yireo_test_keep');

        $tester = $this->execute(['trigger' => 'yireo.test`odd name']);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode(), $tester->getDisplay());
        $this->assertStringContainsString('Removed trigger: yireo.test`odd name', $tester->getDisplay());
        $this->assertNotContains('yireo.test`odd name', $this->getTriggerNames());
        $this->assertContains('yireo_test_keep', $this->getTriggerNames());
    }

    public function testUnknownTriggerFails(): void
    {
        $tester = $this->execute(['trigger' => '<info>yireo_missing</info>']);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString('Trigger "<info>yireo_missing</info>" not found', $tester->getDisplay());
    }

    public function testRequiresTriggerNameOrAll(): void
    {
        $this->assertSame(Command::INVALID, $this->execute([])->getStatusCode());
    }

    public function testRejectsTriggerNameWithAll(): void
    {
        $triggerQuery = $this->createMock(TriggerQuery::class);
        $triggerQuery->expects($this->never())->method('removeTrigger');

        $tester = $this->execute(['trigger' => 'yireo_test', '--all' => true, '--force' => true], $triggerQuery);

        $this->assertSame(Command::INVALID, $tester->getStatusCode());
    }

    public function testDryRunRemovesNothing(): void
    {
        $triggerQuery = $this->getTriggerQueryMock(['trg_a', 'b']);
        $triggerQuery->expects($this->never())->method('removeTrigger');

        $tester = $this->execute(['--all' => true, '--dry-run' => true], $triggerQuery);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('Would remove trigger: trg_a', $tester->getDisplay());
        $this->assertStringContainsString('Would remove trigger: b', $tester->getDisplay());
    }

    public function testAllWithoutForceAbortsWhenNotInteractive(): void
    {
        $triggerQuery = $this->getTriggerQueryMock(['trg_a']);
        $triggerQuery->expects($this->never())->method('removeTrigger');

        $tester = $this->execute(['--all' => true], $triggerQuery, ['interactive' => false]);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString('--force', $tester->getDisplay());
    }

    public function testAllAsksForConfirmation(): void
    {
        $triggerQuery = $this->getTriggerQueryMock(['trg_a']);
        $triggerQuery->expects($this->never())->method('removeTrigger');

        $tester = new CommandTester(new RemoveTrigger($triggerQuery));
        $tester->setInputs(['n']);
        $tester->execute(['--all' => true]);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString('Remove all 1 triggers', $tester->getDisplay());
    }

    public function testAllWithConfirmationRemovesTriggers(): void
    {
        $triggerQuery = $this->getTriggerQueryMock(['trg_a', 'b']);
        $triggerQuery->expects($this->exactly(2))->method('removeTrigger');

        $tester = new CommandTester(new RemoveTrigger($triggerQuery));
        $tester->setInputs(['y']);
        $tester->execute(['--all' => true]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
    }

    public function testAllWithForceRemovesTriggersAndWarnsAboutIndexers(): void
    {
        $triggerQuery = $this->getTriggerQueryMock(['trg_a', 'b']);
        $removed = [];
        $triggerQuery->method('removeTrigger')->willReturnCallback(
            static function (string $name) use (&$removed): void {
                $removed[] = $name;
            }
        );

        $tester = $this->execute(['--all' => true, '--force' => true], $triggerQuery, ['interactive' => false]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertSame(['trg_a', 'b'], $removed);
        $this->assertStringContainsString('indexer:set-mode schedule', $tester->getDisplay());
    }

    public function testAllContinuesAfterFailureAndReportsIt(): void
    {
        $triggerQuery = $this->getTriggerQueryMock(['a', 'b']);
        $removed = [];
        $triggerQuery->method('removeTrigger')->willReturnCallback(
            static function (string $name) use (&$removed): void {
                if ($name === 'a') {
                    throw new RuntimeException('<error>boom</error>');
                }
                $removed[] = $name;
            }
        );

        $tester = $this->execute(['--all' => true, '--force' => true], $triggerQuery);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertSame(['b'], $removed);
        $this->assertStringContainsString('Failed to remove trigger "a": <error>boom</error>', $tester->getDisplay());
        $this->assertStringNotContainsString('indexer:set-mode', $tester->getDisplay());
    }

    /**
     * @param string[] $names
     */
    private function getTriggerQueryMock(array $names): TriggerQuery&\PHPUnit\Framework\MockObject\MockObject
    {
        $triggerQuery = $this->createMock(TriggerQuery::class);
        $triggerQuery->method('getTriggers')->willReturn(array_map(
            static fn (string $name): array => ['TRIGGER_NAME' => $name],
            $names
        ));

        return $triggerQuery;
    }

    private function execute(array $input, ?TriggerQuery $triggerQuery = null, array $options = []): CommandTester
    {
        $command = $triggerQuery
            ? new RemoveTrigger($triggerQuery)
            : Bootstrap::getObjectManager()->create(RemoveTrigger::class);

        $tester = new CommandTester($command);
        $tester->execute($input, $options);

        return $tester;
    }
}
