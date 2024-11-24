<?php declare(strict_types=1);

namespace Lemonade\EmailValidator\Validators;

use Lemonade\EmailValidator\Utils\DomainValidator;
use Psr\Log\LoggerInterface;
use ValueError;

/**
 * Třída MxRecordValidator
 *
 * Slouží k validaci e-mailové adresy pomocí kontroly existence MX (Mail Exchange) záznamů domény.
 * MX záznamy jsou nezbytné pro směrování e-mailů a jejich existence naznačuje, že doména může přijímat e-maily.
 *
 * Funkcionalita:
 * - Kontrola, zda e-mail obsahuje platnou doménu.
 * - Ověření existence MX záznamů domény pomocí funkce `checkdnsrr`.
 * - Volitelné logování chyb a událostí pomocí PSR-3 loggeru.
 *
 * Použití:
 * - Validátor ověřuje, zda doména e-mailové adresy je schopná přijímat e-maily.
 * - Neověřuje dostupnost konkrétní e-mailové schránky (např. `user@example.com`).
 *
 * Konstruktor:
 * - Přijímá volitelný logger (`LoggerInterface`) pro logování výsledků a chyb.
 *
 * Metody:
 * - `validate(string $email): bool`: Kontroluje platnost e-mailové adresy a existence MX záznamů.
 * - `getErrorCoefficient(): float`: Vrací koeficient závažnosti pro tento validátor.
 *
 * Příklady:
 * - Platné e-maily: `user@example.com` (doména `example.com` má MX záznamy).
 * - Neplatné e-maily: `user@invalid-domain.com` (doména nemá MX záznamy).
 *
 * Logování:
 * - Při absenci MX záznamů nebo neplatném formátu e-mailu zaznamená varování.
 * - Pokud dojde k chybě při dotazu na DNS, zaznamená chybu.
 */
class MxRecordValidator implements ValidatorInterface
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
     * Validuje e-mailovou adresu pomocí kontroly MX záznamů domény.
     *
     * @param string $email E-mailová adresa, která má být validována.
     * @return bool Vrací `true`, pokud má doména platný MX záznam, jinak `false`.
     */
    public function validate(string $email): bool
    {
        // Extrakce domény z e-mailové adresy
        $atPosition = strrpos($email, '@');
        if ($atPosition === false) {
            $this->logger?->warning("MxRecordValidator: Missing '@' in email address", ['email' => $email]);
            return false;
        }

        $domain = substr($email, $atPosition + 1);

        // Validace domény pomocí DomainValidator
        if (!DomainValidator::isValid($domain)) {
            $this->logger?->warning("MxRecordValidator: Invalid domain format", ['domain' => $domain, 'email' => $email]);
            return false;
        }

        // Kontrola existence MX záznamů pomocí checkdnsrr
        try {
            $result = checkdnsrr($domain, 'MX');

            if (!$result) {
                $this->logger?->info("MxRecordValidator: No MX record found for domain", ['domain' => $domain, 'email' => $email]);
            }

            return $result;
        } catch (ValueError $e) {
            $this->logger?->error("MxRecordValidator: Error during DNS check", [
                'exception' => $e,
                'domain' => $domain,
                'email' => $email,
            ]);
            return false;
        }
    }

    /**
     * Vrací koeficient chyby pro MX záznamy.
     *
     * @return float Hodnota 1.0, která označuje kritickou závažnost.
     */
    public function getErrorCoefficient(): float
    {
        return 1.0; // Kritická závažnost
    }
}