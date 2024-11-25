<?php

namespace Lemonade\EmailValidator\Tests\Validators;

use Lemonade\EmailValidator\Validators\FormatValidator;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;

class FormatValidatorTest extends TestCase
{
    public function testValidEmail(): void
    {
        $validator = new FormatValidator(new NullLogger());

        $this->assertTrue($validator->validate('user@example.com'));
        $this->assertTrue($validator->validate('name.surname@domain.org'));
        $this->assertTrue($validator->validate('user+alias@sub.domain.com'));
    }

    public function testInvalidEmail(): void
    {
        $validator = new FormatValidator(new NullLogger());

        $this->assertFalse($validator->validate('userexample.com')); // Chybí @
        $this->assertFalse($validator->validate('name@domain')); // Chybí TLD
        $this->assertFalse($validator->validate('@example.com')); // Chybí část před @
    }

    public function testLoggerIsCalledOnInvalidEmail(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once()) // Logger by měl být volán pouze jednou
        ->method('warning')
            ->with(
                "FormatValidator: Invalid email format",
                ['email' => 'invalid-email']
            );

        $validator = new FormatValidator($logger);

        $this->assertFalse($validator->validate('invalid-email'));
    }

    public function testErrorCoefficient(): void
    {
        $validator = new FormatValidator();

        $this->assertSame(0.7, $validator->getErrorCoefficient());
    }
}
