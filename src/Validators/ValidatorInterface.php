<?php declare(strict_types=1);

namespace Lemonade\EmailValidator\Validators;

/**
 * Rozhraní pro všechny validátory e-mailů.
 *
 * Hlavní funkce:
 * - Umožňuje validovat e-mailové adresy.
 * - Poskytuje koeficient chyby, který určuje závažnost problému při validaci.
 */
interface ValidatorInterface
{
    /**
     * Provede validaci e-mailu.
     *
     * @param string $email E-mailová adresa, která má být validována.
     * @return bool Vrací true, pokud e-mail prošel validací, jinak false.
     */
    public function validate(string $email): bool;

    /**
     * Vrátí koeficient chyby pro daný validátor.
     *
     * - Koeficient chyby je hodnota v rozmezí 0.0 až 1.0.
     * - Hodnota 0.0 znamená nízkou závažnost problému (např. kosmetické chyby).
     * - Hodnota 1.0 označuje kritickou chybu (např. neplatný formát e-mailu).
     *
     * @return float Koeficient chyby, hodnota mezi 0.0 a 1.0.
     */
    public function getErrorCoefficient(): float;
}
