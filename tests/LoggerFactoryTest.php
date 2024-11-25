<?php declare(strict_types=1);

namespace Lemonade\EmailValidator\Tests\Logger;

use Lemonade\EmailValidator\Logger\LoggerFactory;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use phpmock\phpunit\PHPMock;

class LoggerFactoryTest extends TestCase
{
    use PHPMock;

    private string $testLogFile;

    protected function setUp(): void
    {
        parent::setUp();

        // Nastavení testovacího logovacího souboru
        $logDir = __DIR__ . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        $this->testLogFile = realpath($logDir) . '/test_logger.log';
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (file_exists($this->testLogFile)) {
           unlink($this->testLogFile);
        }

        $logDir = dirname($this->testLogFile);
        if (is_dir($logDir) && count(scandir($logDir)) <= 2) {
            rmdir($logDir);
        }
    }

    public function testCreateLogger(): void
    {
        $logger = LoggerFactory::createLogger($this->testLogFile);

        $this->assertInstanceOf(Logger::class, $logger);

        $logger->info('Test log entry');
        $this->assertFileExists($this->testLogFile);

        $logContent = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('Test log entry', $logContent);
    }

    public function testCreateLoggerWithInvalidPath(): void
    {
        // Mockování funkce mkdir pro simulaci selhání
        $mkdirMock = $this->getFunctionMock('Lemonade\\EmailValidator\\Logger', 'is_writable');
        $mkdirMock->expects($this->once())->willReturn(false);

        $this->expectException(RuntimeException::class);

        $invalidLogFile = '/invalid/path/to/logfile.log';
        LoggerFactory::createLogger($invalidLogFile);
    }
}