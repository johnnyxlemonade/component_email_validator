# Lemonade Email Validator

A PHP library for validating email addresses with features like spam database checks, DNS record validation, and disposable email detection.

---

## Features
- **Email Format Validation**: Checks if the email format is valid.
- **Spam Database Validation**: Verifies if the email exists in known spam databases.
- **DNS Record Validation**: Ensures the domain has valid MX records.
- **Disposable Email Detection**: Detects temporary/disposable email addresses.

---

## Installation

Use [Composer](https://getcomposer.org/) to install the library:

```bash
composer require lemonade/email-validator
```

### Requirements
- PHP 8.1 or later
- Dependencies:
  - `guzzlehttp/guzzle` ^7.0
  - `guzzlehttp/promises` ^1.0
  - `psr/cache` ^1.0 || ^2.0 || ^3.0
  - `psr/log` ^1.0 || ^2.0 || ^3.0
  - `symfony/cache` ^5.0 || ^6.0
  - `monolog/monolog` ^2.0 || ^3.0

---

## Usage

### 1. Create an EmailValidationManager

```php
use Lemonade\EmailValidator\EmailValidationManager;
use Lemonade\EmailValidator\Validators\FormatValidator;
use Lemonade\EmailValidator\Validators\MxRecordValidator;
use Lemonade\EmailValidator\Validators\SpamDatabaseValidator;
use Lemonade\EmailValidator\Validators\DisposableEmailValidator;
use Lemonade\EmailValidator\Logger\LoggerFactory;

// Initialize logger
$logger = LoggerFactory::createLogger('/path/to/logfile.log');

// Create EmailValidationManager
$emailValidationManager = new EmailValidationManager($logger);

// Add validators
$emailValidationManager->addValidator(new FormatValidator($logger));
$emailValidationManager->addValidator(new MxRecordValidator($logger));
$emailValidationManager->addValidator(new DisposableEmailValidator(['mailinator.com', 'temp-mail.org'], $logger));
$emailValidationManager->addValidator(new SpamDatabaseValidator($config, $client, $cache, $logger));

// Validate an email
$email = "example@mailinator.com";
$isValid = $emailValidationManager->validate($email);

if ($isValid) {
    echo "Email is valid!";
} else {
    echo "Invalid email. Errors: ";
    print_r($emailValidationManager->getErrors());
}
```

---

## Classes and Components

### 1. `EmailValidationManager`
Main class to manage email validation. Add validators and validate an email.

- **Methods**:
  - `addValidator(ValidatorInterface $validator)`: Adds a validator.
  - `validate(string $email): bool`: Validates an email.
  - `getErrors(): array`: Returns validation errors.
  - `getErrorMessage(): string`: Returns error messages.

---

### 2. Validators

#### a. `FormatValidator`
Checks the email format using PHP's `filter_var`.

#### b. `MxRecordValidator`
Validates if the domain has valid MX records using `checkdnsrr`.

#### c. `DisposableEmailValidator`
Detects temporary email addresses using a list of disposable domains.

- **Constructor Parameters**:
  - `array $domains`: List of disposable domains.
  - `LoggerInterface|null $logger`: Optional logger.

#### d. `SpamDatabaseValidator`
Checks email against external spam databases via API.

- **Constructor Parameters**:
  - `ConfigHandler $config`: API configurations.
  - `Client $client`: HTTP client.
  - `CacheItemPoolInterface $cache`: Cache for results.
  - `LoggerInterface|null $logger`: Optional logger.

---

### 3. Utilities

#### a. `DomainValidator`
Validates domain names using a regex-based approach.

#### b. `ConfigHandler` and `ConfigHandlerItem`
Manage API configurations for `SpamDatabaseValidator`.

#### c. `LoggerFactory`
Creates a Monolog logger.

---

## Testing

Run the tests using PHPUnit:

```bash
composer test
```

---

## License

This library is licensed under the MIT License.


---

## Design Patterns Used in the Project

1. **Factory Pattern**
  - Used in `LoggerFactory` to create instances of `Logger`.
  - Simplifies the initialization process and provides a single point of creation for loggers.

2. **Strategy Pattern**
  - Validators such as `FormatValidator`, `MxRecordValidator`, `DisposableEmailValidator`, and `SpamDatabaseValidator` implement the `ValidatorInterface`.
  - This allows flexible addition or removal of validation strategies.

3. **Dependency Injection**
  - Used in constructors (e.g., `SpamDatabaseValidator`) to inject dependencies such as `LoggerInterface`, `ConfigHandler`, `Client`, and `CacheItemPoolInterface`.
  - Enhances testability and decouples components.

4. **Decorator Pattern**
  - Optional logging in validators enhances their functionality without altering their core logic.
  - For example, logging errors or warnings during email validation.

5. **Singleton Pattern**
  - The `EmailValidationManager` acts as a central point for managing validators and coordinating the validation process, though not implemented as a strict singleton.

---


---

## Demo

Below is a complete example of how to use the Lemonade Email Validator library:

```php
<?php

// Autoload dependencies
require 'vendor/autoload.php';

use Lemonade\EmailValidator\EmailValidationManager;
use Lemonade\EmailValidator\Utils\ConfigHandler;
use Lemonade\EmailValidator\Utils\ConfigHandlerItem;
use Lemonade\EmailValidator\Validators\FormatValidator;
use Lemonade\EmailValidator\Validators\MxRecordValidator;
use Lemonade\EmailValidator\Validators\DisposableEmailValidator;
use Lemonade\EmailValidator\Validators\SpamDatabaseValidator;
use Lemonade\EmailValidator\Utils\ValidationErrorFormatter;
use Lemonade\EmailValidator\Logger\LoggerFactory;
use Lemonade\EmailValidator\GuzzleClientFactory;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

// Initialize a logger to track events and errors
$logger = LoggerFactory::createLogger(logFile: __DIR__ . '/logs/email_validation.log');

// Create an EmailValidationManager instance to manage all validations
$emailValidationManager = new EmailValidationManager(logger: $logger);
$emailValidationManager->addValidator(validator: new FormatValidator());
$emailValidationManager->addValidator(validator: new MxRecordValidator());
$emailValidationManager->addValidator(validator: new DisposableEmailValidator(domains: ['mailinator.com', '10minutemail.com', 'tempmail.net']));

// Create a Guzzle client for HTTP requests with logging enabled
$client = GuzzleClientFactory::createClient(
    enableLogging: true, 
    logFile: __DIR__ . '/logs/guzzle_validation.log'
);

// Configure caching for API results
$cache = new FilesystemAdapter(
    namespace: 'email_validator', // Separate namespace for email validation cache
    defaultLifetime: 3600,        // Cache duration in seconds
    directory: __DIR__ . '/cache' // Directory for cache storage
);

// Set up API configuration for spam database checks
$config = new ConfigHandler();
$config->addConfig(item: ConfigHandlerItem::fromArray(
    url: "https://api.stopforumspam.org/api?email={email}&json", 
    path: "email.appears"
));

// Validate an email address
$isValid = $emailValidationManager->validate("redirect-dsadsa1#webmark.eting.org");

// Display the results of validation in a formatted manner
ValidationErrorFormatter::displayErrors(
    manager: $emailValidationManager, 
    emails: "redirect-dsadsa@webmark.eting.org"
);

```

---