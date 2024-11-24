<?php declare(strict_types=1);

namespace Lemonade\EmailValidator\Validators;

use Lemonade\EmailValidator\Utils\DomainValidator;
use Psr\Log\LoggerInterface;

/**
 * Třída DisposableEmailValidator
 *
 * Slouží k validaci e-mailových adres, které mohou pocházet z disposable (dočasných) domén.
 * Disposable domény jsou často používány pro jednorázové e-mailové adresy, které nejsou určeny k dlouhodobému použití.
 *
 * Funkcionalita:
 * - Kontrola, zda e-mail obsahuje validní doménu.
 * - Validace proti seznamu zakázaných (disposable) domén.
 * - Logování výsledků validace, pokud je logger poskytnut.
 *
 * Použití:
 * - Třída přijímá seznam disposable domén (např. `mailinator.com`, `temp-mail.org`) a volitelně logger pro logování událostí.
 * - E-mail je validován nejprve podle formátu a poté proti seznamu disposable domén.
 *
 * Příklady:
 * - Platné e-maily: `user@example.com`, `admin@mydomain.org`.
 * - Neplatné e-maily: `user@mailinator.com` (doména je disposable), `invalid-email` (neplatný formát).
 */
class DisposableEmailValidator implements ValidatorInterface
{
    /**
     * @param array $domains Seznam disposable domén.
     * @param LoggerInterface|null $logger Logger pro logování chyb a událostí.
     */
    public function __construct(
        protected readonly array $domains = [],
        protected readonly ?LoggerInterface $logger = null
    ) {}

    /**
     * Validuje, zda e-mail nepochází z disposable domény.
     *
     * @param string $email E-mailová adresa, která má být validována.
     * @return bool Vrací `true`, pokud e-mail není disposable, jinak `false`.
     */
    public function validate(string $email): bool
    {
        $email = trim($email); // Odstranění nechtěných bílých znaků

        // Validace formátu e-mailu
        if (!$this->isEmailValid($email)) {
            $this->logger?->warning("DisposableEmailValidator: Invalid email format", ['email' => $email]);
            return false;
        }

        $domain = $this->extractDomain($email);

        // Ověření formátu domény
        if (!$this->isDomainValid($domain)) {
            $this->logger?->warning("DisposableEmailValidator: Invalid domain format", ['domain' => $domain, 'email' => $email]);
            return false;
        }

        // Kontrola disposable domén
        if ($this->isDisposableDomain($domain)) {
            $this->logger?->info("DisposableEmailValidator: Disposable email detected", ['domain' => $domain, 'email' => $email]);
            return false;
        }

        return true;
    }

    /**
     * Validuje formát e-mailové adresy.
     *
     * @param string $email E-mailová adresa.
     * @return bool Vrací `true`, pokud je formát e-mailu platný.
     */
    private function isEmailValid(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Extrahuje doménu z e-mailové adresy.
     *
     * @param string $email E-mailová adresa.
     * @return string Doména extrahovaná z e-mailu.
     */
    private function extractDomain(string $email): string
    {
        return substr($email, strrpos($email, "@") + 1);
    }

    /**
     * Ověřuje formát domény pomocí DomainValidator.
     *
     * @param string $domain Doména.
     * @return bool Vrací `true`, pokud je formát domény platný.
     */
    private function isDomainValid(string $domain): bool
    {
        return DomainValidator::isValid($domain);
    }

    /**
     * Kontroluje, zda doména patří mezi disposable.
     *
     * @param string $domain Doména.
     * @return bool Vrací `true`, pokud je doména disposable.
     */
    private function isDisposableDomain(string $domain): bool
    {
        return in_array($domain, $this->domains, true);
    }

    /**
     * Vrací koeficient chyby pro disposable domény.
     *
     * @return float Koeficient chyby (0.9 pro vysokou závažnost).
     */
    public function getErrorCoefficient(): float
    {
        return 0.9; // Vysoká závažnost
    }
}
