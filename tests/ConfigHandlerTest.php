<?php declare(strict_types=1);

namespace Lemonade\EmailValidator\Tests\Utils;

use Lemonade\EmailValidator\Utils\ConfigHandler;
use Lemonade\EmailValidator\Utils\ConfigHandlerItem;
use PHPUnit\Framework\TestCase;

class ConfigHandlerTest extends TestCase
{
    public function testEmptyConfigHandler(): void
    {
        $handler = new ConfigHandler();
        $this->assertEmpty($handler->getConfig(), 'New ConfigHandler should have no configurations.');
    }

    public function testAddSingleConfig(): void
    {
        $handler = new ConfigHandler();
        $configItem = new ConfigHandlerItem('https://example.com', '/api/v1/resource');

        $handler->addConfig($configItem);

        $this->assertCount(1, $handler->getConfig(), 'ConfigHandler should contain exactly one configuration.');
        $this->assertSame($configItem, $handler->getConfig()[0], 'Added configuration should match the retrieved configuration.');
    }

    public function testAddMultipleConfigs(): void
    {
        $handler = new ConfigHandler();
        $configItem1 = new ConfigHandlerItem('https://example.com', '/api/v1/resource');
        $configItem2 = new ConfigHandlerItem('https://api.example.com', '/v2/data');

        $handler->addConfig($configItem1);
        $handler->addConfig($configItem2);

        $configs = $handler->getConfig();

        $this->assertCount(2, $configs, 'ConfigHandler should contain exactly two configurations.');
        $this->assertSame($configItem1, $configs[0], 'The first configuration should match the first added item.');
        $this->assertSame($configItem2, $configs[1], 'The second configuration should match the second added item.');
    }

    public function testGetConfigReturnsArray(): void
    {
        $handler = new ConfigHandler();
        $this->assertIsArray($handler->getConfig(), 'getConfig should return an array.');
    }
}
