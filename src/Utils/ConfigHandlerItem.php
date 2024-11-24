<?php declare(strict_types=1);

namespace Lemonade\EmailValidator\Utils;

use InvalidArgumentException;
use Stringable;

/**
 * Třída ConfigHandlerItem
 *
 * Slouží k uchování konfigurace pro volání API.
 * Obsahuje vlastnosti pro URL, cestu, TTL (časovou platnost) a HTTP hlavičky.
 * Je immutabilní a bezpečně validuje vstupy při vytváření instance.
 *
 * Vlastnosti:
 * - URL (string): Adresa API.
 * - Cesta (string): Endpoint v rámci API.
 * - TTL (int): Doba platnosti konfigurace, výchozí 1800 sekund.
 * - Hlavičky (array<string, string>): HTTP hlavičky jako pole klíč-hodnota.
 *
 * Funkcionalita:
 * - Tovární metoda `fromArray` pro vytvoření validované instance.
 * - Validace vstupních dat (např. URL musí být platná, TTL kladné číslo).
 * - Implementace `Stringable` pro snadné ladění a logování obsahu instance.
 */
class ConfigHandlerItem implements Stringable
{
    public function __construct(
        protected readonly string $url,
        protected readonly string $path,
        protected readonly int $ttl = 1800,
        /**
         * @var array<string, string> Hlavičky ve formátu klíč-hodnota.
         */
        protected readonly array $headers = []
    ) {}

    /**
     * Vrátí URL.
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Vrátí cestu.
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Vrátí TTL.
     *
     * @return int
     */
    public function getTtl(): int
    {
        return $this->ttl;
    }

    /**
     * Vrátí hlavičky.
     *
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Tovární metoda pro vytvoření instance s validací.
     *
     * @param string $url
     * @param string $path
     * @param int $ttl
     * @param array<string, string> $headers
     * @return ConfigHandlerItem
     * @throws InvalidArgumentException Pokud vstupní parametry nejsou platné.
     */
    public static function fromArray(string $url, string $path, int $ttl = 1800, array $headers = []): ConfigHandlerItem
    {
        self::validateUrl($url);
        self::validatePath($path);
        self::validateTtl($ttl);
        self::validateHeaders($headers);

        return new self($url, $path, $ttl, $headers);
    }

    /**
     * Vrací obsah instance jako řetězec ve formátu JSON.
     *
     * @return string
     */
    public function __toString(): string
    {
        return json_encode([
            'url' => $this->url,
            'path' => $this->path,
            'ttl' => $this->ttl,
            'headers' => $this->headers,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Validuje URL.
     *
     * @param string $url
     * @throws InvalidArgumentException
     */
    private static function validateUrl(string $url): void
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException("Neplatná URL adresa: $url");
        }

        if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            throw new InvalidArgumentException("URL musí začínat 'http://' nebo 'https://': $url");
        }
    }

    /**
     * Validuje cestu.
     *
     * @param string $path
     * @throws InvalidArgumentException
     */
    private static function validatePath(string $path): void
    {
        if (empty(trim($path))) {
            throw new InvalidArgumentException("Cesta nesmí být prázdná.");
        }
    }

    /**
     * Validuje TTL.
     *
     * @param int $ttl
     * @throws InvalidArgumentException
     */
    private static function validateTtl(int $ttl): void
    {
        if ($ttl <= 0) {
            throw new InvalidArgumentException("TTL musí být kladné číslo. Hodnota: $ttl");
        }
    }

    /**
     * Validuje hlavičky.
     *
     * @param array<string, string> $headers
     * @throws InvalidArgumentException
     */
    private static function validateHeaders(array $headers): void
    {
        if (count($headers) !== count(array_unique(array_keys($headers)))) {
            throw new InvalidArgumentException("Hlavičky obsahují duplicitní klíče.");
        }

        foreach ($headers as $key => $value) {
            if (!is_string($key) || !is_string($value)) {
                throw new InvalidArgumentException("Hlavičky musí být pole ve formátu klíč-hodnota (řetězce).");
            }
        }
    }
}
