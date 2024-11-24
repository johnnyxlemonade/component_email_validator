<?php declare(strict_types=1);

namespace Lemonade\EmailValidator\Utils;

/**
 * Třída ConfigHandler
 *
 * Slouží k uchování a správě více konfigurací typu ConfigHandlerItem.
 * Poskytuje metody pro přidávání a získávání konfigurací.
 *
 * Funkcionalita:
 * - Uchovává konfigurace v interním poli.
 * - Umožňuje dynamické přidávání nových konfigurací.
 * - Poskytuje přístup k uloženým konfiguracím pomocí metody `getConfig`.
 */
class ConfigHandler
{
    /**
     * @var ConfigHandlerItem[] Pole konfigurací
     */
    private array $config = [];

    /**
     * Přidá konfiguraci do seznamu.
     *
     * @param ConfigHandlerItem $item Instance ConfigHandlerItem představující konfiguraci.
     * @return void
     */
    public function addConfig(ConfigHandlerItem $item): void
    {

        $this->config[] = $item;
    }

    /**
     * Vrací všechny uložené konfigurace.
     *
     * @return ConfigHandlerItem[] Pole instancí ConfigHandlerItem.
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}
