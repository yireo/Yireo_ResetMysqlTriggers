<?php declare(strict_types=1);

namespace Yireo\ResetMysqlTriggers\Test\Integration\Console\Command;

use Magento\Framework\Console\CommandListInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Yireo\ResetMysqlTriggers\Console\Command\ListTriggers;
use Yireo\ResetMysqlTriggers\Test\Integration\TriggerTestCase;

/**
 * @magentoDbIsolation disabled
 */
class ListTriggersTest extends TriggerTestCase
{
    public function testCommandIsRegistered(): void
    {
        $commands = Bootstrap::getObjectManager()->get(CommandListInterface::class)->getCommands();

        $this->assertInstanceOf(ListTriggers::class, $commands['Yireo_ResetMysqlTriggers::list']);
    }

    public function testListsTriggerNamesLiterally(): void
    {
        $this->createTrigger('yireo_<error>x</error>');

        $tester = new CommandTester(Bootstrap::getObjectManager()->create(ListTriggers::class));
        $tester->execute([], ['decorated' => true]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('yireo_<error>x</error>', $tester->getDisplay());
        $this->assertStringContainsString(self::TABLE, $tester->getDisplay());
    }
}
