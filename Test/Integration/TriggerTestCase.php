<?php declare(strict_types=1);

namespace Yireo\ResetMysqlTriggers\Test\Integration;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

abstract class TriggerTestCase extends TestCase
{
    protected const TABLE = 'yireo_reset_mysql_triggers_test';

    /**
     * @var string[]
     */
    private array $createdTriggers = [];

    protected function setUp(): void
    {
        $this->getConnection()->query(sprintf(
            'CREATE TABLE IF NOT EXISTS %s (id INT NOT NULL)',
            $this->getConnection()->quoteIdentifier(self::TABLE)
        ));
    }

    protected function tearDown(): void
    {
        $connection = $this->getConnection();
        foreach ($this->createdTriggers as $triggerName) {
            $connection->query('DROP TRIGGER IF EXISTS ' . $connection->quoteIdentifier([$triggerName]));
        }

        $connection->query('DROP TABLE IF EXISTS ' . $connection->quoteIdentifier(self::TABLE));
        $this->createdTriggers = [];
    }

    protected function createTrigger(string $triggerName): void
    {
        $connection = $this->getConnection();
        $connection->query(sprintf(
            'CREATE TRIGGER %s BEFORE INSERT ON %s FOR EACH ROW SET NEW.id = NEW.id',
            $connection->quoteIdentifier([$triggerName]),
            $connection->quoteIdentifier(self::TABLE)
        ));
        $this->createdTriggers[] = $triggerName;
    }

    /**
     * @return string[]
     */
    protected function getTriggerNames(): array
    {
        return $this->getConnection()->fetchCol(
            'SELECT TRIGGER_NAME FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE()'
        );
    }

    protected function getConnection(): AdapterInterface
    {
        return Bootstrap::getObjectManager()->get(ResourceConnection::class)->getConnection();
    }
}
