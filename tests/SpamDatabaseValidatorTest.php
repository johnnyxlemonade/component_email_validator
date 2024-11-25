<?php declare(strict_types=1);

namespace Lemonade\EmailValidator\Tests\Validators;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\FulfilledPromise;
use Lemonade\EmailValidator\Utils\ConfigHandler;
use Lemonade\EmailValidator\Utils\ConfigHandlerItem;
use Lemonade\EmailValidator\Validators\SpamDatabaseValidator;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Promise\RejectedPromise;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Exception;

class SpamDatabaseValidatorTest extends TestCase
{
    public function testValidateWithValidEmail(): void
    {
        $email = 'valid@example.com';

        // Mock ConfigHandler
        $configHandler = $this->createMock(ConfigHandler::class);
        $configHandler->method('getConfig')->willReturn([$this->createMock(ConfigHandlerItem::class)]);

        // Mock Cache
        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(false);
        $cache->method('getItem')->willReturn($cacheItem);

        // Mock HTTP Client
        $client = $this->createMock(Client::class);
        $client->method('getAsync')->willReturn(new FulfilledPromise(false)); // No spam

        $validator = new SpamDatabaseValidator($configHandler, $client, $cache);

        $this->assertTrue($validator->validate($email), 'Valid email should pass validation.');
    }

    public function testValidateWithSpamEmail(): void
    {
        $email = 'spam@example.com';

        // Mock ConfigHandler
        $configHandler = $this->createMock(ConfigHandler::class);
        $configHandlerItem = $this->createMock(ConfigHandlerItem::class);
        $configHandlerItem->method('getUrl')->willReturn('https://api.mock.com/api?email={email}&json');
        $configHandlerItem->method('getPath')->willReturn('email.appears');
        $configHandler->method('getConfig')->willReturn([$configHandlerItem]);

        // Mock Cache
        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(false); // Cache miss
        $cache->method('getItem')->willReturn($cacheItem);

        // Mock HTTP Client
        $client = $this->createMock(Client::class);
        $responseBody = json_encode(['email' => ['appears' => true]]); // Simulovaná spam odpověď
        $response = new \GuzzleHttp\Psr7\Response(200, [], $responseBody);
        $client->method('getAsync')->willReturn(new FulfilledPromise($response));

        // Vytvoření instance SpamDatabaseValidator s mockovanými závislostmi
        $validator = new SpamDatabaseValidator($configHandler, $client, $cache);

        // Assert: Spam email should fail validation
        $this->assertFalse($validator->validate($email), 'Spam email should fail validation.');
    }

    public function testValidateWhenAllApisFail(): void
    {
        $email = 'unknown@example.com';

        // Mock ConfigHandler
        $configHandler = $this->createMock(ConfigHandler::class);
        $configHandlerItem = $this->createMock(ConfigHandlerItem::class);
        $configHandlerItem->method('getUrl')->willReturn('https://api.mock.com/api?email={email}&json');
        $configHandlerItem->method('getPath')->willReturn('email.appears');
        $configHandlerItem->method('getHeaders')->willReturn([]);
        $configHandlerItem->method('getTtl')->willReturn(3600);
        $configHandler->method('getConfig')->willReturn([$configHandlerItem]);

        // Mock Cache
        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(false); // Simulace cache miss
        $cache->method('getItem')->willReturn($cacheItem);

        // Mock HTTP Client to simulate all API failures
        $client = $this->createMock(Client::class);
        $client->method('getAsync')
            ->willReturn(new RejectedPromise(new \Exception('API failed'))); // Simulujeme selhání všech API

        // Mock Logger
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error')->with(
            'SpamDatabaseValidator: All API requests failed',
            ['email' => $email]
        );

        // Vytvoření instance validatoru
        $validator = new SpamDatabaseValidator($configHandler, $client, $cache, $logger);

        // Assert: When all APIs fail, validate should fallback to valid
        $this->assertTrue($validator->validate($email), 'When all APIs fail, validation should fallback to valid.');
    }

    public function testParseApiResponse(): void
    {
        $validator = new SpamDatabaseValidator(
            $this->createMock(ConfigHandler::class),
            $this->createMock(Client::class),
            $this->createMock(CacheItemPoolInterface::class)
        );

        $config = $this->createMock(ConfigHandlerItem::class);
        $config->method('getPath')->willReturn('data.spam');

        $responseBody = json_encode(['data' => ['spam' => true]]);
        $this->assertTrue($this->invokeMethod($validator, 'parseApiResponse', [$responseBody, $config]));

        $responseBody = json_encode(['data' => ['spam' => false]]);
        $this->assertFalse($this->invokeMethod($validator, 'parseApiResponse', [$responseBody, $config]));
    }

    protected function invokeMethod($object, string $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
