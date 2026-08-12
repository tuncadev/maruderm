<?php

namespace MarudermNovaPoshta\Service;

use MarudermNovaPoshta\Api\Client;
use MarudermNovaPoshta\Config;

class DivisionService
{
    private const CACHE_GROUP = 'maruderm_nova_poshta';

    public function __construct(
        private Client $client,
        private Config $config
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getSettlements(): array
    {
        $cacheKey = 'settlements_' . strtolower($this->config->getCountryCode());
        $cached = wp_cache_get($cacheKey, self::CACHE_GROUP);
        if (is_array($cached) && $cached !== []) {
            return $cached;
        }

        $settlementsMap = [];
        $page = 1;

        try {
            while ($page <= 80) {
                $response = $this->client->get('/divisions', [
                    'countryCodes' => [$this->config->getCountryCode()],
                    'divisionCategories' => ['Postomat', 'PostBranch', 'CargoBranch', 'PUDO'],
                    'statuses' => ['Working'],
                    'limit' => 100,
                    'page' => $page,
                ]);

                $items = isset($response['items']) && is_array($response['items']) ? $response['items'] : [];
                foreach ($items as $item) {
                    if (! is_array($item) || ! isset($item['settlement']) || ! is_array($item['settlement'])) {
                        continue;
                    }

                    $settlement = $item['settlement'];
                    $id = (int) ($settlement['id'] ?? 0);
                    $name = isset($settlement['name']) ? (string) $settlement['name'] : '';

                    if ($id <= 0 || $name === '') {
                        continue;
                    }

                    $regionName = '';
                    if (
                        isset($settlement['region'])
                        && is_array($settlement['region'])
                        && isset($settlement['region']['parent'])
                        && is_array($settlement['region']['parent'])
                    ) {
                        $regionName = (string) ($settlement['region']['parent']['name'] ?? '');
                    }

                    $settlementsMap[$id] = [
                        'id' => $id,
                        'name' => $name,
                        'region' => $regionName,
                    ];
                }

                $lastPage = (int) ($response['last_page'] ?? $page);
                if ($page >= $lastPage) {
                    break;
                }

                $page++;
            }
        } catch (\Throwable $exception) {
            error_log('[Maruderm Nova Poshta] getSettlements failed: ' . $exception->getMessage());
            return [];
        }

        $settlements = array_values($settlementsMap);
        usort($settlements, static function (array $left, array $right): int {
            return strcmp((string) $left['name'], (string) $right['name']);
        });

        wp_cache_set($cacheKey, $settlements, self::CACHE_GROUP, 12 * HOUR_IN_SECONDS);

        return $settlements;
    }

    /**
     * @return list<string>
     */
    public function getAreas(): array
    {
        $settlements = $this->getSettlements();
        $areas = [];

        foreach ($settlements as $settlement) {
            $region = isset($settlement['region']) ? trim((string) $settlement['region']) : '';
            if ($region !== '') {
                $areas[$region] = true;
            }
        }

        $areaList = array_keys($areas);
        sort($areaList, SORT_NATURAL | SORT_FLAG_CASE);

        return $areaList;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getSettlementsByArea(string $area): array
    {
        $area = trim($area);
        if ($area === '') {
            return $this->getSettlements();
        }

        $result = [];
        foreach ($this->getSettlements() as $settlement) {
            if (strcasecmp((string) ($settlement['region'] ?? ''), $area) === 0) {
                $result[] = $settlement;
            }
        }

        return $result;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getDivisionsBySettlement(int $settlementId): array
    {
        if ($settlementId <= 0) {
            return [];
        }

        $cacheKey = 'divisions_' . $settlementId;
        $cached = wp_cache_get($cacheKey, self::CACHE_GROUP);
        if (is_array($cached)) {
            return $cached;
        }

        $divisions = [];
        $page = 1;

        try {
            while ($page <= 20) {
                $response = $this->client->get('/divisions', [
                    'countryCodes' => [$this->config->getCountryCode()],
                    'settlementIds' => [$settlementId],
                    'divisionCategories' => ['Postomat', 'PostBranch', 'CargoBranch', 'PUDO'],
                    'statuses' => ['Working'],
                    'limit' => 100,
                    'page' => $page,
                ]);

                $items = isset($response['items']) && is_array($response['items']) ? $response['items'] : [];
                foreach ($items as $item) {
                    if (! is_array($item)) {
                        continue;
                    }

                    $id = (int) ($item['id'] ?? 0);
                    if ($id <= 0) {
                        continue;
                    }

                    $divisions[] = [
                        'id' => $id,
                        'number' => (string) ($item['number'] ?? ''),
                        'name' => (string) ($item['shortName'] ?? $item['name'] ?? ''),
                        'address' => (string) ($item['address'] ?? ''),
                        'category' => (string) ($item['divisionCategory'] ?? ''),
                        'status' => (string) ($item['status'] ?? ''),
                    ];
                }

                $lastPage = (int) ($response['last_page'] ?? $page);
                if ($page >= $lastPage) {
                    break;
                }

                $page++;
            }
        } catch (\Throwable $exception) {
            error_log('[Maruderm Nova Poshta] getDivisionsBySettlement failed: ' . $exception->getMessage());
            return [];
        }

        usort($divisions, static function (array $left, array $right): int {
            return strcmp((string) $left['number'], (string) $right['number']);
        });

        wp_cache_set($cacheKey, $divisions, self::CACHE_GROUP, 12 * HOUR_IN_SECONDS);

        return $divisions;
    }

    public function isDivisionInSettlement(int $divisionId, int $settlementId): bool
    {
        $divisions = $this->getDivisionsBySettlement($settlementId);
        foreach ($divisions as $division) {
            if ((int) ($division['id'] ?? 0) === $divisionId) {
                return true;
            }
        }

        return false;
    }
}
