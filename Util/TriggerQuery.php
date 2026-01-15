<?php declare(strict_types=1);

namespace Yireo\ResetMysqlTriggers\Util;

use Magento\Framework\App\ResourceConnection;

class TriggerQuery
{
    public function __construct(
        private ResourceConnection $resourceConnection
    ) {
    }

    public function getTriggers(): array
    {
        $database = $this->getDatabaseName();
        $query = 'SELECT * FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = "'.$database.'"';

        $connection = $this->resourceConnection->getConnection();
        return $connection->fetchAll($query);
    }

    public function removeTrigger(string $triggerName): bool
    {
        $query = 'DROP TRIGGER IF EXISTS '.$triggerName;
        $connection = $this->resourceConnection->getConnection();
        $connection->query($query);
        return true;
    }

    private function getDatabaseName(): string
    {
        $connection = $this->resourceConnection->getConnection();
        $config = $connection->getConfig();

        return $config['dbname'] ?? '';
    }
}
