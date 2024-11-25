<?php declare(strict_types=1);

namespace Lemonade\EmailValidator\Validators;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\Utils;
use Lemonade\EmailValidator\Utils\ConfigHandler;
use Lemonade\EmailValidator\Utils\ConfigHandlerItem;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * Třída SpamDatabaseValidator
 *
 * Slouží k validaci e-mailových adres proti externím spamovým databázím.
 */
class SpamDatabaseValidator implements ValidatorInterface
{
    public function __construct(
        protected readonly ConfigHandler $config,
        protected readonly Client $client,
        protected readonly CacheItemPoolInterface $cache,
        protected readonly ?LoggerInterface $logger = null
    ) {}

    /**
     * Validuje e-mailovou adresu proti více spamovým databázím.
     *
     * @param string $email E-mailová adresa, která má být validována.
     * @return bool Vrací `false`, pokud je e-mail nalezen v jakékoli databázi, jinak `true`.
     */
    public function validate(string $email): bool
    {
        $promises = [];

        foreach ($this->config->getConfig() as $config) {
            $promises[] = $this->checkEmailInSpamDatabaseAsync($email, $config);
        }

        $results = Utils::settle($promises)->wait();

        // Logování výsledků
        $this->logger?->debug('API Results', ['results' => $results]);

        // Zkontrolujeme, zda všechna API selhala
        $allRejected = array_reduce($results, fn($carry, $result) => $carry && $result['state'] === 'rejected', true);

        if ($allRejected) {

            $this->logger?->error('SpamDatabaseValidator: All API requests failed', [
                'email' => $email,
            ]);

            return true; // Fallback na validní e-mail
        }

        // Kontrolujeme, zda některé API označilo e-mail jako spam
        foreach ($results as $result) {
            if ($result['state'] === 'fulfilled' && $result['value'] === true) {
                return false; // Spam nalezen
            }
        }

        return true; // E-mail není označen jako spam
    }

    /**
     * Provádí asynchronní HTTP požadavek na ověření e-mailu v jedné databázi.
     *
     * @param string $email E-mailová adresa, která má být validována.
     * @param ConfigHandlerItem $config Konfigurace konkrétní databáze.
     * @return PromiseInterface Vrací promise, která se vyhodnotí na `true`/`false` podle výsledku validace.
     */
    private function checkEmailInSpamDatabaseAsync(string $email, ConfigHandlerItem $config): PromiseInterface
    {
        $cacheKey = md5($config->getUrl() . $email);
        $cacheItem = null; // Inicializace na null

        try {
            $cacheItem = $this->cache->getItem($cacheKey);

            if ($cacheItem->isHit()) {
                $this->logger?->info('SpamDatabaseValidator: Cache hit', ['email' => $email, 'cacheKey' => $cacheKey]);
                return new FulfilledPromise($cacheItem->get());
            }
        } catch (Exception|InvalidArgumentException $e) {
            $this->logger?->error('SpamDatabaseValidator: Cache error', [
                'exception' => $e,
                'email' => $email,
            ]);
        }

        $url = str_replace('{email}', urlencode($email), $config->getUrl());
        $options = ['headers' => $config->getHeaders()];

        return $this->client->getAsync($url, $options)
            ->then(
                function ($response) use ($cacheItem, $cacheKey, $config) {
                    $body = (string) $response->getBody();
                    $result = $this->parseApiResponse($body, $config);

                    // Uložení výsledku do cache
                    if ($cacheItem !== null) {
                        $ttl = $config->getTtl() ?: 3600;
                        $cacheItem->set($result);
                        $cacheItem->expiresAfter($ttl);
                        $this->cache->save($cacheItem);
                    }

                    return $result;
                },
                function (RequestException $e) use ($url) {

                    return false; // Defaultní návratová hodnota při chybě
                }
            );
    }

    /**
     * Zpracovává JSON odpověď z API a kontroluje, zda je e-mail označen jako spam.
     *
     * @param string $responseBody JSON odpověď z API.
     * @param ConfigHandlerItem $config Konfigurace API (např. cesta k datům).
     * @return bool Vrací `true`, pokud je e-mail označen jako spam, jinak `false`.
     */
    private function parseApiResponse(string $responseBody, ConfigHandlerItem $config): bool
    {
        $response = json_decode($responseBody, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger?->warning('SpamDatabaseValidator: Invalid JSON response', [
                'responseBody' => $responseBody,
            ]);
            return false;
        }

        if ($config->getPath()) {
            $value = $response;

            foreach (explode('.', $config->getPath()) as $key) {
                if (isset($value[$key])) {
                    $value = $value[$key];
                } else {
                    return false;
                }
            }

            return (bool) $value;
        }

        return false;
    }

    /**
     * Vrací koeficient chyby pro SpamDatabaseValidator.
     *
     * @return float Hodnota 1.0, která označuje kritickou závažnost pro spamové e-maily.
     */
    public function getErrorCoefficient(): float
    {
        return 1.0;
    }
}
