<?php

namespace Lemonade\EmailValidator\Tests;

use Lemonade\EmailValidator\Validators\DisposableEmailValidator;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class DisposableEmailValidatorTest extends TestCase
{
    public function testValidEmail(): void
    {
        $domains = ['mailinator.com', 'temp-mail.org']; // Seznam disposable domén
        $validator = new DisposableEmailValidator($domains, new NullLogger());

        $email = 'user@example.com'; // Platný e-mail

        $this->assertTrue($validator->validate($email));
    }

    public function testDisposableEmail(): void
    {
        $domains = ['mailinator.com', 'temp-mail.org']; // Seznam disposable domén
        $validator = new DisposableEmailValidator($domains, new NullLogger());

        $email = 'user@mailinator.com'; // Disposable e-mail

        $this->assertFalse($validator->validate($email));
    }

    public function testInvalidEmailFormat(): void
    {
        $domains = ['mailinator.com', 'temp-mail.org'];
        $validator = new DisposableEmailValidator($domains, new NullLogger());

        $email = 'invalid-email'; // Neplatný formát e-mailu

        $this->assertFalse($validator->validate($email));
    }

    public function testInvalidDomainFormat(): void
    {
        $domains = ['mailinator.com', 'temp-mail.org'];
        $validator = new DisposableEmailValidator($domains, new NullLogger());

        $email = 'user@invalid_domain'; // Neplatná doména

        $this->assertFalse($validator->validate($email));
    }
}
