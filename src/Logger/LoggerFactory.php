<?php declare(strict_types=1);

namespace Lemonade\EmailValidator\Logger;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LogLevel;
use RuntimeException;

/**
 * LoggerFactory
 *
 * Třída pro vytvoření loggeru s podporou logování do souboru.
 */
class LoggerFactory
{
    /**
     * Vytváří instanci loggeru.
     *
     * @param string $logFile Cesta k logovacímu souboru.
     * @param string $channel Název logovacího kanálu.
     * @param string $logLevel Úroveň logování (např. DEBUG, INFO, WARNING).
     * @return Logger
     * @throws RuntimeException Pokud nelze vytvořit adresář nebo není zapisovatelný.
     */
    public static function createLogger(string $logFile, string $channel = 'email_validator', string $logLevel = LogLevel::DEBUG): Logger
    {
        // Získání adresáře z cesty k souboru
        $logDir = dirname($logFile);

        // Kontrola a vytvoření adresáře
        if (!is_dir($logDir)) {
            if (!mkdir($logDir, 0777, true) && !is_dir($logDir)) {
                throw new RuntimeException(sprintf('Cannot create directory "%s" for log file.', $logDir));
            }
        }

        // Ověření, zda je adresář zapisovatelný
        if (!is_writable($logDir)) {
            throw new RuntimeException(sprintf('Directory "%s" is not writable.', $logDir));
        }

        // Vytvoření loggeru
        $logger = new Logger($channel);
        $logger->pushHandler(new StreamHandler($logFile, $logLevel));

        return $logger;
    }
}
