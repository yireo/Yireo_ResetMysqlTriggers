<?php declare(strict_types=1);

namespace Yireo\ResetMysqlTriggers\Util;

use InvalidArgumentException;
use Magento\Framework\App\ResourceConnection;

class TriggerQuery
{
    public function __construct(
        private readonly ResourceConnection $resourceConnection
    ) {
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function getTriggers(): array
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(
                'information_schema.TRIGGERS',
                ['TRIGGER_NAME', 'ACTION_TIMING', 'EVENT_OBJECT_TABLE', 'EVENT_MANIPULATION']
            )
            ->where('TRIGGER_SCHEMA = DATABASE()')
            ->order('TRIGGER_NAME');

        return $connection->fetchAll($select);
    }

    public function triggerExists(string $triggerName): bool
    {
        foreach ($this->getTriggers() as $trigger) {
            if ($trigger['TRIGGER_NAME'] === $triggerName) {
                return true;
            }
        }

        return false;
    }

    public function removeTrigger(string $triggerName): void
    {
        if ($triggerName === '') {
            throw new InvalidArgumentException('Trigger name is empty');
        }

        $connection = $this->resourceConnection->getConnection();
        $databaseName = (string)$connection->fetchOne('SELECT DATABASE()');
        $identifier = $databaseName !== ''
            ? $connection->quoteIdentifier([$databaseName, $triggerName])
            : $connection->quoteIdentifier([$triggerName]);

        // phpcs:ignore Magento2.SQL.RawQuery -- identifier is quoted, DDL has no query builder
        $connection->query('DROP TRIGGER IF EXISTS ' . $identifier);
    }
}
