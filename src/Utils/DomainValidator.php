<?php declare(strict_types=1);

namespace Lemonade\EmailValidator\Utils;

/**
 * Třída DomainValidator
 *
 * Slouží k validaci doménových jmen podle běžných pravidel a standardů.
 *
 * Funkcionalita:
 * - Ověřuje, zda doménové jméno splňuje požadavky na formát a délku.
 * - Používá regulární výrazy pro kontrolu struktury domény.
 *
 * Pravidla validace:
 * - Doména nesmí být prázdná nebo `null`.
 * - Maximální délka domény je 253 znaků.
 * - Jednotlivé části domény (labels) nesmí začínat ani končit znakem `-`.
 * - Top-level doména (TLD) musí obsahovat alespoň 2 znaky.
 *
 * Příklady:
 * - Platné domény: `example.com`, `sub.example.com`, `xn--d1acj3b.xn--p1ai`
 * - Neplatné domény: `-example.com`, `example-.com`, `example..com`
 */
class DomainValidator
{
    /**
     * Ověřuje, zda je doménové jméno platné.
     *
     * @param string|null $domain Doménové jméno ke kontrole.
     * @return bool Vrací `true`, pokud je doména platná, jinak `false`.
     */
    public static function isValid(?string $domain): bool
    {
        if ($domain === null || $domain === '') {
            return false; // Prázdná nebo null hodnota není platná doména
        }

        $domain = trim($domain); // Odstranění bílých znaků

        // Kontrola délky domény
        if (strlen($domain) > 253) {
            return false; // Maximální délka domény je 253 znaků
        }

        // Regulární výraz pro validaci struktury doménového jména
        $pattern = '/^(?=.{1,253}$)(?:(?!-)[a-zA-Z0-9\-]{1,63}(?<!-)\.)+[a-zA-Z]{2,63}$/';

        return (bool)preg_match($pattern, $domain);
    }
}
