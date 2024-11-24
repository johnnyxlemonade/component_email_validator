<?php declare(strict_types=1);

/**
 * Class ValidationErrorFormatter
 *
 * Lemonade\EmailValidator\Utils
 * @author Honza Mudrak <honzamudrak@gmail.com>
 */
namespace Lemonade\EmailValidator\Utils;
use Lemonade\EmailValidator\EmailValidationManager;

class ValidationErrorFormatter
{
    /**
     * Formátuje validace chyb pro více e-mailových adres.
     *
     * @param EmailValidationManager $manager
     * @param string|array $emails
     * @return string
     */
    public static function formatErrors(EmailValidationManager $manager, string|array $emails): string
    {

        // Pokud je předána jedna adresa (string), převedeme ji na pole
        if (is_string($emails)) {
            $emails = [$emails];
        }

        $result = '';

        // Pro každou e-mailovou adresu provede validaci
        foreach ($emails as $email) {

            // Pro každý email provede validaci
            $manager->validate($email);
            $errors = $manager->getErrors();

            $result .="<pre>";
            $result .= "Výsledky pro e-mail: $email\n";

            if (empty($errors)) {

                $result .= "  E-mail je platný.\n";

            } else {

                $result .= "  E-mail není platný. Chyby validace:\n";

                foreach ($errors as $error) {

                    $name = get_class($error);
                    $coeficient = $error->getErrorCoefficient();
                    $result .= "    - $name (Chybovost: $coeficient)\n";
                }
            }

            $result .="</pre>";
            $result .= "\n";
        }

        return $result;
    }

    /**
     * Zobrazí chyby validace pro jednu nebo více e-mailových adres přímo na obrazovce nebo do logu.
     *
     * @param EmailValidationManager $manager
     * @param array|string $emails
     * @return void
     */
    public static function displayErrors(EmailValidationManager $manager, array|string $emails): void
    {
        echo self::formatErrors($manager, $emails) . "\n";
    }
}
