<?php declare(strict_types=1);

namespace Lemonade\EmailValidator\Validators;
use Psr\Log\LoggerInterface;

/**
 * Třída FormatValidator
 *
 * Slouží k validaci formátu e-mailové adresy podle standardu RFC.
 * Používá PHP funkci `filter_var` s filtrem `FILTER_VALIDATE_EMAIL`.
 *
 * Funkcionalita:
 * - Ověření, zda e-mailová adresa odpovídá platnému formátu.
 * - Volitelné logování chyb a událostí pomocí PSR-3 loggeru.
 *
 * Použití:
 * - Validátor ověřuje pouze základní formát e-mailu, například `user@example.com`.
 * - Neověřuje dostupnost domény ani existenci schránky.
 *
 * Konstruktor:
 * - Přijímá volitelný logger (`LoggerInterface`) pro logování událostí.
 * - Pokud je logger poskytován, zaznamená neplatné e-mailové adresy.
 *
 * Metody:
 * - `validate(string $email): bool`: Ověřuje formát e-mailu.
 * - `getErrorCoefficient(): float`: Vrací koeficient závažnosti pro tento validátor.
 *
 * Příklady:
 * - Platné e-maily: `user@example.com`, `name.surname@domain.org`.
 * - Neplatné e-maily: `userexample.com`, `name@domain`, `@example.com`.
 */
class FormatValidator implements ValidatorInterface
{
    /**
     * Konstruktor s volitelným loggerem.
     *
     * @param LoggerInterface|null $logger Logger pro logování chyb a událostí.
     */
    public function __construct(protected readonly ?LoggerInterface $logger = null)
    {
    }

    /**
     * Validuje formát e-mailové adresy.
     *
     * @param string $email E-mailová adresa, která má být validována.
     * @return bool Vrací `true`, pokud je e-mail platný, jinak `false`.
     */
    public function validate(string $email): bool
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {

            $this->logger?->warning("FormatValidator: Invalid email format", ['email' => $email]);
            return false;
        }

        return true;
    }

    /**
     * Vrací koeficient chyby pro validaci formátu.
     *
     * @return float Koeficient chyby (0.7 pro střední závažnost).
     */
    public function getErrorCoefficient(): float
    {
        return 0.7; // Střední závažnost
    }
}
