<?php

namespace kirillbdev\WCUkrShipping\Http\Controllers;

use kirillbdev\WCUkrShipping\DB\OptionsRepository;
use kirillbdev\WCUSCore\Http\Contracts\ResponseInterface;
use kirillbdev\WCUSCore\Http\Controller;
use kirillbdev\WCUSCore\Http\Request;

if ( ! defined('ABSPATH')) {
    exit;
}

class OptionsController extends Controller
{
    /**
     * @param Request $request
     *
     * @return ResponseInterface
     */
    public function save($request)
    {
        parse_str($request->get('data'), $data);

        $optionsRepository = new OptionsRepository();
        $optionsRepository->save($data);

        return $this->jsonResponse([
            'success' => true,
            'data' => [
                'message' => __('Settings saved successfully!', 'wc-ukr-shipping-i18n'),
            ]
        ]);
    }
}