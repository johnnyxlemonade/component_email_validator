<?php declare(strict_types=1);

namespace Lemonade\EmailValidator;

use Lemonade\EmailValidator\Validators\ValidatorInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionException;

/**
 * Třída pro správu validace e-mailových adres.
 *
 * Hlavní funkce:
 * - Umožňuje přidat různé validátory implementující rozhraní ValidatorInterface.
 * - Provádí validaci e-mailové adresy pomocí přidaných validátorů.
 * - Poskytuje zpětnou vazbu ve formě seznamu chyb a logování.
 */
class EmailValidationManager
{
    /**
     * @var ValidatorInterface[]
     * Pole instancí validátorů, které se použijí při validaci e-mailu.
     */
    private array $validators = [];

    /**
     * @var ValidatorInterface[]
     * Seznam validátorů, které selhaly při validaci.
     */
    private array $errors = [];

    /**
     * Konstruktor třídy EmailValidationManager.
     *
     * @param LoggerInterface|null $logger Instance loggeru pro logování validací. Může být null, pokud není požadováno logování.
     */
    public function __construct(protected readonly ?LoggerInterface $logger = null)
    {
    }


    public function addValidator(ValidatorInterface $validator): void
    {
        try {
            $reflection = new \ReflectionClass($validator);
            $constructor = $reflection->getConstructor();

            if ($constructor) {
                $parameters = $constructor->getParameters();

                // Kontrola, zda konstruktor podporuje logger
                $supportsLogger = array_reduce(
                    $parameters,
                    fn($carry, $parameter) => $carry || (
                            $parameter->getName() === 'logger' &&
                            $parameter->getType()?->getName() === LoggerInterface::class
                        ),
                    false
                );

                // Pokud konstruktor podporuje logger, vytvoříme novou instanci
                if ($supportsLogger && $this->logger !== null) {
                    $args = [];
                    foreach ($parameters as $parameter) {
                        $name = $parameter->getName();

                        try {
                            // Pokusíme se získat aktuální hodnoty vlastností validátoru
                            $property = $reflection->getProperty($name);
                            $property->setAccessible(true);
                            $args[] = $property->getValue($validator) ?? (
                            $parameter->isDefaultValueAvailable()
                                ? $parameter->getDefaultValue()
                                : null
                            );
                        } catch (\ReflectionException $e) {
                            // Pokud vlastnost neexistuje, použijeme výchozí hodnotu
                            $args[] = $parameter->isDefaultValueAvailable()
                                ? $parameter->getDefaultValue()
                                : null;
                        }
                    }

                    // Vložíme logger na správné místo
                    foreach ($parameters as $index => $parameter) {
                        if ($parameter->getName() === 'logger') {
                            $args[$index] = $this->logger;
                        }
                    }

                    // Pokusíme se vytvořit novou instanci validátoru
                    try {
                        $validator = $reflection->newInstanceArgs($args);
                    } catch (\ReflectionException $e) {
                        // Logujeme chybu, pokud se nepodaří vytvořit novou instanci
                        $this->logger?->error('Failed to instantiate validator with logger', [
                            'validator' => $reflection->getName(),
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            // Přidáme validátor do seznamu
            $this->validators[] = $validator;

        } catch (\ReflectionException $e) {
            // Logujeme chybu, pokud se nepodaří analyzovat validátor
            $this->logger?->error('Reflection failed on validator', [
                'validator' => get_class($validator),
                'error' => $e->getMessage(),
            ]);
        }
    }


    /**
     * Provádí validaci e-mailové adresy pomocí přidaných validátorů.
     *
     * - Pokud některý validátor selže, je přidán do seznamu chyb a výsledek je zalogován.
     * - Pokud všechny validátory projdou, e-mail je považován za platný a výsledek je také zalogován.
     *
     * @param string $email E-mailová adresa, která má být validována.
     * @return bool Vrací true, pokud e-mail prošel všemi validacemi, jinak false.
     */
    public function validate(string $email): bool
    {
        $this->errors = [];

        foreach ($this->validators as $validator) {
            if (!$validator->validate($email)) {
                $this->errors[] = $validator;
                $this->logger?->warning("Validation failed", [
                    'validator' => get_class($validator),
                    'email' => $email,
                ]);
            }
        }

        if (empty($this->errors)) {
            $this->logger?->info("E-mail is valid", ['email' => $email]);
        }

        return empty($this->errors);
    }

    /**
     * Vrací seznam validátorů, které selhaly při validaci.
     *
     * @return ValidatorInterface[] Pole instancí validátorů, které selhaly.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Generuje zprávu o výsledku validace.
     *
     * @return string Textová zpráva o validaci e-mailu.
     */
    public function getErrorMessage(): string
    {
        if (empty($this->errors)) {
            return 'E-mail is valid.';
        }

        return 'E-mail is invalid. Errors: ' . implode(', ', array_map(
                fn(ValidatorInterface $validator) => $validator->getErrorMessage(),
                $this->errors
            ));
    }
}
