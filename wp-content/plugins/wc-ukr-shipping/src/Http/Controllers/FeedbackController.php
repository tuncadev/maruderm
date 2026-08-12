<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Http\Controllers;

use kirillbdev\WCUSCore\Http\Controller;
use kirillbdev\WCUSCore\Http\Request;

class FeedbackController extends Controller
{
    private const FEEDBACK_API_URL = 'https://wp-api.smartyparcel.com/v1/feedbacks';

    public function store(Request $request)
    {
        try {
            $meta = get_file_data(WC_UKR_SHIPPING_PLUGIN_ENTRY, ['Version' => 'Version']);
            $response = wp_remote_post(self::FEEDBACK_API_URL, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'timeout' => 5,
                'body' => json_encode([
                    'app_name' => 'wc-ukr-shipping',
                    'app_version' => $meta['Version'] ?? 'undefined',
                    'store_url' => home_url(),
                    'reason' => $request->get('reason'),
                    'message' => $request->get('message'),
                ]),
            ]);
        } catch (\Throwable $e) {
            // safe
        }

        // Cache 1 hour
        set_transient('wcus_last_feedback', time(), 3600);

        return $this->jsonResponse([
            'success' => true
        ]);
    }
}
