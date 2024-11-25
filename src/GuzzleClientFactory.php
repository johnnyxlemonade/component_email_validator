<?php declare(strict_types=1);

namespace Lemonade\EmailValidator;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Psr\Log\LogLevel;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use RuntimeException;
use Lemonade\EmailValidator\Logger\LoggerFactory;

/**
 * GuzzleClientFactory
 *
 * Tovární třída pro vytvoření instance GuzzleHttp\Client.
 * Poskytuje podporu logování HTTP požadavků a odpovědí,
 * využívá statický HandlerStack pro sdílení konfigurace mezi klienty
 * a nabízí flexibilitu při nastavování logování.
 */
class GuzzleClientFactory
{

    /**
     * @var HandlerStack|null Statická instance handler stacku pro sdílení middleware
     */
    private static ?HandlerStack $stack = null;

    /**
     * Vytvoří instanci GuzzleHttp\Client s možností logování a vlastního handler stacku.
     *
     * @param bool $enableLogging Určuje, zda bude povoleno logování HTTP požadavků a odpovědí.
     * @param string|null $logFile Cesta k logovacímu souboru. Pokud není zadána, použije se výchozí hodnota.
     * @param string $logLevel Úroveň logování (např. DEBUG, INFO, WARNING).
     * @return Client Návratová hodnota je instance GuzzleHttp\Client připravená k použití.
     */
    public static function createClient(bool $enableLogging = false, string $logFile = null, array $options = [], string $logLevel = 'DEBUG'): Client
    {

        // Pokud ještě nebyl stack inicializován, inicializuj ho
        if (self::$stack === null) {

            self::$stack = HandlerStack::create();

            if ($enableLogging) {

                $logFile = $logFile ?? getenv('GUZZLE_LOG_FILE') ?: __DIR__ . '/logs/guzzle_validation.log';

                // Vytvoření loggeru pomocí LoggerFactory
                $logger = LoggerFactory::createLogger($logFile, "guzzle_logger", $logLevel);

                // Přidání middleware pro logování
                self::$stack->push(
                    Middleware::log(
                        $logger,
                        new MessageFormatter("{method} {uri} HTTP/{version} {code} {res_header_Content-Length}")
                    )
                );

            }
        }

        // Sloučení výchozích a uživatelských možností
        $defaultOptions = [
            'handler' => self::$stack,
            'timeout' => 5.0,
            'verify' => false,
        ];

        return new Client(array_merge($defaultOptions, $options));
    }

}
