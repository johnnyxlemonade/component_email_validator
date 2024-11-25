<?php declare(strict_types=1);

namespace Lemonade\EmailValidator\Tests;

use Lemonade\EmailValidator\GuzzleClientFactory;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use phpmock\phpunit\PHPMock;

class GuzzleClientFactoryTest extends TestCase
{
    use PHPMock;

    private string $testLogFile;

    protected function setUp(): void
    {
        parent::setUp();

        $logDir = __DIR__ . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        $this->testLogFile = realpath($logDir) . '/test_guzzle.log';
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

    public function testInvalidLogPath(): void
    {
        // Mockování funkce mkdir v namespace LoggerFactory
        $mkdirMock = $this->getFunctionMock('Lemonade\\EmailValidator\\Logger', 'is_writable');
        $mkdirMock->expects($this->once())->willReturn(false); // Simulace selhání mkdir

        $this->expectException(RuntimeException::class);

        $invalidLogFile = __DIR__ . '/logs/should_fail.log';
        GuzzleClientFactory::createClient(true, $invalidLogFile);
    }
}
