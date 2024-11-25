<?php declare(strict_types=1);

namespace Lemonade\EmailValidator\Tests\Utils;

use Lemonade\EmailValidator\Utils\DomainValidator;
use PHPUnit\Framework\TestCase;

class DomainValidatorTest extends TestCase
{

    public function testValidDomains(): void
    {
        $validDomains = [
            'example.com',
            'sub.example.com',
            'xn--d1acj3b.xn--p1ai', // IDN doména (punycode)
            'пример.рф',           // IDN doména (azbuka)
            'münchen.de',          // IDN doména (německé znaky)
            'example.co.uk',
        ];

        foreach ($validDomains as $domain) {
            $this->assertTrue(DomainValidator::isValid($domain), "Domain '$domain' should be valid.");
        }
    }

    public function testInvalidDomains(): void
    {
        $invalidDomains = [
            '',                     // Prázdná hodnota
            null,                   // Null hodnota
            '-example.com',         // Začíná pomlčkou
            'example-.com',         // Končí pomlčkou
            'xn--invalid-.xn--p1ai', // Neplatný punycode formát
            'toolongdomain.example.' . str_repeat('a', 240) . '.com', // Překročení délky
            'exa_mple.com',         // Neplatný znak "_"
            '123',                  // Jen čísla, chybí TLD
        ];

        foreach ($invalidDomains as $domain) {
            $this->assertFalse(DomainValidator::isValid($domain), "Domain '$domain' should be invalid.");
        }
    }
}
