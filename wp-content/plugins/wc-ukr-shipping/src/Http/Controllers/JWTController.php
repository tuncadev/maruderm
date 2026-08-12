<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Http\Controllers;

use kirillbdev\WCUkrShipping\Api\SmartyParcelWPApi;
use kirillbdev\WCUSCore\Http\Contracts\ResponseInterface;
use kirillbdev\WCUSCore\Http\Controller;
use kirillbdev\WCUSCore\Http\Request;

class JWTController extends Controller
{
    private SmartyParcelWPApi $api;

    public function __construct(SmartyParcelWPApi $api)
    {
        $this->api = $api;
    }

    public function issueToken(Request $request): ResponseInterface
    {
        try {
            $scope = [$this->getScopeByRole($request->get('role'))];
            $cacheKey = sprintf('smartyparcel_jwt_%s', md5(implode(',', $scope)));

            $cached = get_transient($cacheKey);
            if ($cached) {
                $cached = json_decode($cached, true);

                if ((int)$cached['expires_at'] > time()) {
                    return $this->jsonResponse([
                        'success' => true,
                        'data' => $cached,
                    ]);
                }
            }

            $response = $this->api->sendRequest('/v1/embedded/authx', [
                'scope' => $scope,
            ]);
            set_transient($cacheKey, json_encode($response), (int)$response['expires_at'] - time());

            return $this->jsonResponse([
                'success' => true,
                'data' => $response,
            ]);
        } catch (\Throwable $e) {
            return $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function getScopeByRole(string $role): string
    {
        switch ($role) {
            case 'admin':
                return '/platform';
            default:
                throw new \Exception('Unsupported role: ' . $role);
        }
    }
}
