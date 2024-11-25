<?php

namespace Lemonade\EmailValidator\Tests\Validators;

use Lemonade\EmailValidator\Validators\MxRecordValidator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use phpmock\phpunit\PHPMock;

class MxRecordValidatorTest extends TestCase
{
    use PHPMock;

    public function testValidEmailWithMxRecord(): void
    {
        // Mockování nativní funkce checkdnsrr
        $checkdnsrrMock = $this->getFunctionMock('Lemonade\EmailValidator\Validators', 'checkdnsrr');
        $checkdnsrrMock->expects($this->once())->with('example.com', 'MX')->willReturn(true);

        $validator = new MxRecordValidator($this->createMock(LoggerInterface::class));

        $this->assertTrue($validator->validate('user@example.com'));
    }

    public function testDomainWithoutMxRecord(): void
    {
        $checkdnsrrMock = $this->getFunctionMock('Lemonade\EmailValidator\Validators', 'checkdnsrr');
        $checkdnsrrMock->expects($this->once())->with('invalid-domain.com', 'MX')->willReturn(false);

        $validator = new MxRecordValidator($this->createMock(LoggerInterface::class));

        $this->assertFalse($validator->validate('user@invalid-domain.com'));
    }
}
