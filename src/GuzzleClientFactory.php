<?php declare(strict_types=1);

namespace Lemonade\EmailValidator;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Psr\Log\LogLevel;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

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
     * @return Client Návratová hodnota je instance GuzzleHttp\Client připravená k použití.
     *
     * @example
     * // Vytvoření klienta bez logování
     * $client = GuzzleClientFactory::create();
     *
     * // Vytvoření klienta s logováním do specifického souboru
     * $client = GuzzleClientFactory::create(true, '/path/to/logfile.log');
     *
     * // Vytvoření klienta s logováním, kdy se cesta k logu vezme z proměnné prostředí
     * putenv('GUZZLE_LOG_FILE=/path/to/logfile.log');
     * $client = GuzzleClientFactory::create(true);
     */
    public static function createClient(bool $enableLogging = false, string $logFile = null, array $options = []): Client
    {

        // Pokud ještě nebyl stack inicializován, inicializuj ho
        if (self::$stack === null) {

            self::$stack = HandlerStack::create();

            if ($enableLogging) {

                $logFile = $logFile ?? getenv('GUZZLE_LOG_FILE') ?: __DIR__ . '/logs/guzzle_validation.log';

                // Kontrola, zda existuje složka pro logy, pokud ne, vytvoř ji
                if (!is_dir(dirname($logFile))) {
                    mkdir(dirname($logFile), 0777, true);
                }

                $logger = new Logger('guzzle_logger');
                $logger->pushHandler(new StreamHandler($logFile, LogLevel::DEBUG));

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
