<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Http\Controllers;

use kirillbdev\WCUkrShipping\DB\Repositories\LegacyTtnRepository;
use kirillbdev\WCUSCore\Http\Contracts\ResponseInterface;
use kirillbdev\WCUSCore\Http\Controller;
use kirillbdev\WCUSCore\Http\Request;

class ToolsController extends Controller
{
    private const SYNC_LIMIT = 100;

    private LegacyTtnRepository $legacyTtnRepository;

    public function __construct(LegacyTtnRepository $legacyTtnRepository)
    {
        $this->legacyTtnRepository = $legacyTtnRepository;
    }

    public function syncLegacyTtn(Request $request): ResponseInterface
    {
        try {
            $result = $this->legacyTtnRepository->syncTtn(self::SYNC_LIMIT, (int)$request->get('cursor'));

            return $this->jsonResponse([
                'success' => true,
                'data' => [
                    'operation_success' => $result['synced'] < self::SYNC_LIMIT,
                    'synced' => $result['synced'],
                    'total' => $result['total'],
                    'cursor' => $result['lastId'],
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->jsonResponse([
                'success' => false,
                'error' => [
                    'code' => 0,
                    'message' => $e->getMessage(),
                ],
            ]);
        }
    }
}
