<?php declare(strict_types=1);

namespace Lemonade\EmailValidator\Logger;

use Monolog\Logger;
use Psr\Log\LogLevel;
use Monolog\Handler\StreamHandler;

/**
 * Třída LoggerFactory
 *
 * Poskytuje tovární metodu pro vytvoření instance loggeru.
 * Používá Monolog jako knihovnu pro zpracování logů a ukládá je do souboru.
 *
 * Funkcionalita:
 * - Vytváří logger s definovaným kanálem a úrovní logování.
 * - Umožňuje nastavit vlastní soubor pro ukládání logů a název kanálu.
 *
 * Použití:
 * - Třída je vhodná pro vytváření loggerů v kontextu validace e-mailů nebo jiných aplikací.
 * - Umožňuje snadnou integraci Monologu bez opakování kódu.
 *
 * Konstruktor:
 * - Třída neobsahuje konstruktor, protože poskytuje pouze statickou metodu `createLogger`.
 *
 * Metody:
 * - `createLogger(string $logFile, string $channel = 'email_validator'): Logger`
 *   - Vytváří instanci loggeru s určeným souborem a kanálem.
 *   - Výchozí název kanálu je `email_validator`.
 *
 * Příklad použití:
 * ```php
 * use Lemonade\EmailValidator\Logger\LoggerFactory;
 *
 * $logger = LoggerFactory::createLogger('/path/to/logfile.log', 'custom_channel');
 * $logger->info('This is a test log entry.');
 * ```
 *
 * Výstup:
 * - Logovací záznamy jsou ukládány do zadaného souboru, např. `/path/to/logfile.log`.
 */
class LoggerFactory
{
    /**
     * Vytváří instanci loggeru.
     *
     * @param string $logFile Cesta k souboru pro ukládání logů.
     * @param string $channel Název kanálu loggeru.
     * @return Logger
     */
    public static function createLogger(string $logFile, string $channel = 'email_validator'): Logger
    {
        $logger = new Logger($channel);
        $logger->pushHandler(new StreamHandler($logFile, LogLevel::DEBUG));

        return $logger;
    }
}
