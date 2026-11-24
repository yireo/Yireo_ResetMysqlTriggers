<?php declare(strict_types=1);

namespace Yireo\ResetMysqlTriggers\Test\Integration\Util;

use InvalidArgumentException;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\Attributes\DataProvider;
use Yireo\ResetMysqlTriggers\Test\Integration\TriggerTestCase;
use Yireo\ResetMysqlTriggers\Util\TriggerQuery;

/**
 * @magentoDbIsolation disabled
 */
class TriggerQueryTest extends TriggerTestCase
{
    public function testGetTriggersReturnsTriggerDetails(): void
    {
        $this->createTrigger('yireo_test_trg');

        $triggers = array_filter(
            $this->getTriggerQuery()->getTriggers(),
            static fn (array $trigger): bool => $trigger['TRIGGER_NAME'] === 'yireo_test_trg'
        );

        $this->assertSame([[
            'TRIGGER_NAME' => 'yireo_test_trg',
            'ACTION_TIMING' => 'BEFORE',
            'EVENT_OBJECT_TABLE' => self::TABLE,
            'EVENT_MANIPULATION' => 'INSERT',
        ]], array_values($triggers));
    }

    public function testTriggerExists(): void
    {
        $this->createTrigger('yireo_test_trg');

        $this->assertTrue($this->getTriggerQuery()->triggerExists('yireo_test_trg'));
        $this->assertFalse($this->getTriggerQuery()->triggerExists('yireo_test_trg_missing'));
    }

    #[DataProvider('getTriggerNamesToRemove')]
    public function testRemoveTriggerOnlyRemovesGivenTrigger(string $triggerName): void
    {
        $this->createTrigger($triggerName);
        $this->createTrigger('yireo_test_keep');

        $this->getTriggerQuery()->removeTrigger($triggerName);

        $triggerNames = $this->getTriggerNames();
        $this->assertNotContains($triggerName, $triggerNames);
        $this->assertContains('yireo_test_keep', $triggerNames);
    }

    public static function getTriggerNamesToRemove(): array
    {
        return [
            'plain' => ['yireo_test_trg'],
            'dot' => ['yireo.test'],
            'backtick' => ['yireo`tick'],
            'space' => ['yireo test'],
            'injection' => ['yireo_x; DROP TABLE yireo_reset_mysql_triggers_test'],
        ];
    }

    public function testRemoveTriggerDoesNotExecuteInjectedSql(): void
    {
        $this->createTrigger('yireo_test_keep');

        $this->getTriggerQuery()->removeTrigger('yireo_test_missing; DROP TRIGGER yireo_test_keep');

        $this->assertContains('yireo_test_keep', $this->getTriggerNames());
    }

    public function testRemoveTriggerRejectsEmptyName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->getTriggerQuery()->removeTrigger('');
    }

    private function getTriggerQuery(): TriggerQuery
    {
        return Bootstrap::getObjectManager()->get(TriggerQuery::class);
    }
}
