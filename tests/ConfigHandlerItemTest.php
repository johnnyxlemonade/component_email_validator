<?php declare(strict_types=1);

namespace Lemonade\EmailValidator\Tests\Utils;

use InvalidArgumentException;
use Lemonade\EmailValidator\Utils\ConfigHandlerItem;
use PHPUnit\Framework\TestCase;

class ConfigHandlerItemTest extends TestCase
{
    public function testValidInstanceCreation(): void
    {
        $item = ConfigHandlerItem::fromArray(
            'https://example.com',
            '/api/v1/resource',
            1800,
            ['Authorization' => 'Bearer token']
        );

        $this->assertInstanceOf(ConfigHandlerItem::class, $item);
        $this->assertSame('https://example.com', $item->getUrl());
        $this->assertSame('/api/v1/resource', $item->getPath());
        $this->assertSame(1800, $item->getTtl());
        $this->assertSame(['Authorization' => 'Bearer token'], $item->getHeaders());
    }

    public function testInvalidUrlThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Neplatná URL adresa');

        ConfigHandlerItem::fromArray(
            'invalid-url',
            '/api/v1/resource',
            1800
        );
    }

    public function testInvalidPathThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cesta nesmí být prázdná.');

        ConfigHandlerItem::fromArray(
            'https://example.com',
            '',
            1800
        );
    }

    public function testInvalidTtlThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('TTL musí být kladné číslo.');

        ConfigHandlerItem::fromArray(
            'https://example.com',
            '/api/v1/resource',
            -1
        );
    }


    public function testInvalidHeaderKeyValueTypesThrowException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Hlavičky musí být pole ve formátu klíč-hodnota (řetězce).');

        ConfigHandlerItem::fromArray(
            'https://example.com',
            '/api/v1/resource',
            1800,
            ['Authorization' => 123] // Neplatný typ hodnoty
        );
    }

    public function testToStringReturnsJson(): void
    {
        $item = ConfigHandlerItem::fromArray(
            'https://example.com',
            '/api/v1/resource',
            1800,
            ['Authorization' => 'Bearer token']
        );

        $json = (string) $item;

        $expectedJson = json_encode([
            'url' => 'https://example.com',
            'path' => '/api/v1/resource',
            'ttl' => 1800,
            'headers' => ['Authorization' => 'Bearer token'],
        ], JSON_PRETTY_PRINT);

        $this->assertJson($json);
        $this->assertSame($expectedJson, $json);
    }
}
