<?php declare(strict_types=1);

namespace Lemonade\EmailValidator\Tests\Utils;

use Lemonade\EmailValidator\Utils\ValidationErrorFormatter;
use Lemonade\EmailValidator\EmailValidationManager;
use Lemonade\EmailValidator\Validators\ValidatorInterface;
use PHPUnit\Framework\TestCase;

class ValidationErrorFormatterTest extends TestCase
{
    public function testFormatErrorsWithValidEmail(): void
    {
        $manager = $this->createMock(EmailValidationManager::class);
        $manager->method('validate')->willReturn(true);
        $manager->method('getErrors')->willReturn([]);

        $result = ValidationErrorFormatter::formatErrors($manager, 'valid@example.com');
        $this->assertStringContainsString('E-mail je platný.', $result);
    }

    public function testFormatErrorsWithInvalidEmail(): void
    {
        $manager = $this->createMock(EmailValidationManager::class);
        $manager->method('validate')->willReturn(false);

        $mockValidator = $this->createMock(ValidatorInterface::class);
        $mockValidator->method('getErrorCoefficient')->willReturn(0.8);

        $manager->method('getErrors')->willReturn([$mockValidator]);

        $result = ValidationErrorFormatter::formatErrors($manager, 'invalid@example.com');
        $this->assertStringContainsString('E-mail není platný.', $result);
        $this->assertStringContainsString('Chybovost: 0.8', $result);
    }

    public function testFormatErrorsWithMultipleEmails(): void
    {
        $manager = $this->createMock(EmailValidationManager::class);
        $manager->method('validate')->willReturn(true);

        $mockValidator = $this->createMock(ValidatorInterface::class);
        $mockValidator->method('getErrorCoefficient')->willReturn(0.8);

        $manager->expects($this->exactly(2))->method('validate');
        $manager->method('getErrors')->willReturnOnConsecutiveCalls([], [$mockValidator]);

        $emails = ['valid@example.com', 'invalid@example.com'];
        $result = ValidationErrorFormatter::formatErrors($manager, $emails);

        $this->assertStringContainsString('Výsledky pro e-mail: valid@example.com', $result);
        $this->assertStringContainsString('E-mail je platný.', $result);

        $this->assertStringContainsString('Výsledky pro e-mail: invalid@example.com', $result);
        $this->assertStringContainsString('E-mail není platný.', $result);
        $this->assertStringContainsString('Chybovost: 0.8', $result);
    }

    public function testDisplayErrors(): void
    {
        $manager = $this->createMock(EmailValidationManager::class);
        $manager->method('validate')->willReturn(true);
        $manager->method('getErrors')->willReturn([]);

        $this->expectOutputString("Výsledky pro e-mail: valid@example.com\n  E-mail je platný.\n\n");
        ValidationErrorFormatter::displayErrors($manager, 'valid@example.com');
    }
}
