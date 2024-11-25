<?php

namespace Lemonade\EmailValidator\Tests;
use Lemonade\EmailValidator\EmailValidationManager;
use Lemonade\EmailValidator\Validators\ValidatorInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class EmailValidationManagerTest extends TestCase
{
    public function testCanAddValidator(): void
    {
        $manager = new EmailValidationManager(new NullLogger());
        $validator = $this->createMock(ValidatorInterface::class);

        $manager->addValidator($validator);

        $reflection = new \ReflectionClass($manager);
        $property = $reflection->getProperty('validators');
        $property->setAccessible(true);

        $this->assertContains($validator, $property->getValue($manager));
    }

    public function testValidationFailsWithInvalidEmail(): void
    {
        $manager = new EmailValidationManager(new NullLogger());
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturn(false);

        $manager->addValidator($validator);
        $isValid = $manager->validate('invalid-email');

        $this->assertFalse($isValid);
        $this->assertNotEmpty($manager->getErrors());
    }

    public function testValidationPassesWithValidEmail(): void
    {
        $manager = new EmailValidationManager(new NullLogger());
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturn(true);

        $manager->addValidator($validator);
        $isValid = $manager->validate('test@example.com');

        $this->assertTrue($isValid);
        $this->assertEmpty($manager->getErrors());
    }
}
