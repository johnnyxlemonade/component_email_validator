<?php declare(strict_types=1);

namespace Lemonade\EmailValidator\Validators;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\Utils;
use Lemonade\EmailValidator\Utils\ConfigHandler;
use Lemonade\EmailValidator\Utils\ConfigHandlerItem;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Psr\Cache\InvalidArgumentException;
use Exception;

/**
 * Třída SpamDatabaseValidator
 *
 * Slouží k validaci e-mailových adres pomocí externích spamových databází.
 * Umožňuje ověřit, zda e-mailová adresa není označena jako spam pomocí HTTP API volání.
 *
 * Funkcionalita:
 * - Načítá konfigurace API prostřednictvím třídy `ConfigHandler`.
 * - Provádí asynchronní HTTP požadavky na ověření e-mailu v různých databázích.
 * - Využívá kešování výsledků pro zlepšení výkonu a minimalizaci opakovaných dotazů.
 * - Loguje chyby při HTTP požadavcích, chybné odpovědi nebo selhání při zpracování.
 *
 * Použití:
 * - Třída je vhodná pro scénáře, kde je třeba validovat e-mailovou adresu vůči známým spamovým zdrojům.
 * - Může být použita v e-commerce, SaaS aplikacích nebo jiných systémech vyžadujících ochranu před spamem.
 *
 * Konstruktor:
 * - `ConfigHandler`: Obsahuje konfigurace API (např. URL a hlavičky).
 * - `Client`: Guzzle klient pro provádění HTTP požadavků.
 * - `CacheItemPoolInterface`: Implementace PSR-6 pro kešování výsledků.
 * - `LoggerInterface|null`: Volitelný logger pro logování chyb a událostí.
 *
 * Metody:
 * - `validate(string $email): bool`: Ověřuje, zda e-mailová adresa není ve spamové databázi.
 * - `getErrorCoefficient(): float`: Vrací koeficient závažnosti pro tento validátor (1.0 pro kritickou závažnost).
 *
 * Zpracování výsledků:
 * - Pokud je e-mail nalezen v jakékoli databázi, validace vrací `false`.
 * - Pokud všechna API selžou, validace vrací výchozí hodnotu `true` jako fallback.
 *
 * Logování:
 * - Varování nebo chyby při HTTP požadavcích (např. neplatná odpověď, selhání DNS).
 * - Informace o nalezených spamových adresách.
 *
 * Příklady:
 * - Platné e-maily: `user@example.com` (není označen jako spam v žádné databázi).
 * - Neplatné e-maily: `spam@spammer.com` (označen jako spam ve více databázích).
 */
class SpamDatabaseValidator implements ValidatorInterface
{
    /**
     * Inicializuje validátor s povinnou konfigurací, klientem a cache.
     *
     * @param ConfigHandler $config Konfigurace API (URL a další metadata).
     * @param Client $client Guzzle klient pro HTTP požadavky.
     * @param CacheItemPoolInterface $cache Implementace PSR-6 pro kešování výsledků.
     * @param LoggerInterface|null $logger Volitelný logger pro logování chyb a událostí.
     */
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

        // Čekání na výsledky všech asynchronních požadavků
        $results = Utils::settle($promises)->wait();

        // Kontrola, zda všechna API selhala
        $allRejected = array_reduce($results, fn($carry, $result) => $carry && $result['state'] === 'rejected', true);

        if ($allRejected) {
            $this->logger?->error('SpamDatabaseValidator: All API requests failed', [
                'email' => $email,
            ]);
            return true; // Fallback na validní e-mail
        }

        // Kontrola výsledků jednotlivých požadavků
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
                    $this->logger?->error('SpamDatabaseValidator: HTTP request failed', [
                        'exception' => $e,
                        'url' => $url,
                    ]);
                    return false;
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
