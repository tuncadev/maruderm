<?php

declare(strict_types=1);

namespace Maruderm\Account;

if (!defined('ABSPATH')) {
    exit();
}

/** Persists account delivery addresses and keeps WooCommerce's active shipping address in sync. */
final class AccountAddressService
{
    private const ADDRESSES_META_KEY = '_maruderm_delivery_addresses';
    private const SELECTED_META_KEY = '_maruderm_selected_delivery_address';
    private const DELIVERY_TYPE_META_KEY = '_maruderm_delivery_type';
    private const ALLOWED_TYPES = ['Нова пошта', 'Укрпошта', 'Кур’єрська доставка'];

    /** @return array<int, array{id: string, type: string, city: string, location: string, selected: bool}> */
    public function addressesForUser(int $userId): array
    {
        $stored = get_user_meta($userId, self::ADDRESSES_META_KEY, true);
        $addresses = is_array($stored) ? $this->normalizeAddresses($stored) : [];

        if ($addresses === []) {
            $address = $this->woocommerceAddress($userId);

            if ($address !== null) {
                $addresses[] = $address;
            }
        }

        $selectedId = (string) get_user_meta($userId, self::SELECTED_META_KEY, true);
        $hasSelected = false;

        foreach ($addresses as &$address) {
            $address['selected'] = $selectedId !== '' ? $address['id'] === $selectedId : !$hasSelected;
            $hasSelected = $hasSelected || $address['selected'];
        }
        unset($address);

        return $addresses;
    }

    /** @return array{id: string, type: string, city: string, location: string, selected: bool} */
    public function add(int $userId, string $type, string $city, string $location): array
    {
        $type = in_array($type, self::ALLOWED_TYPES, true) ? $type : '';
        $city = sanitize_text_field($city);
        $location = sanitize_text_field($location);

        if ($userId <= 0 || $type === '' || $city === '' || $location === '') {
            throw new \InvalidArgumentException('Заповни всі поля нової адреси.');
        }

        $addresses = $this->addressesForUser($userId);
        $address = [
            'id' => wp_generate_uuid4(),
            'type' => $type,
            'city' => $city,
            'location' => $location,
            'selected' => true,
        ];

        foreach ($addresses as &$existing) {
            $existing['selected'] = false;
        }
        unset($existing);
        array_unshift($addresses, $address);

        update_user_meta($userId, self::ADDRESSES_META_KEY, $addresses);
        update_user_meta($userId, self::SELECTED_META_KEY, $address['id']);
        update_user_meta($userId, self::DELIVERY_TYPE_META_KEY, $type);
        $this->syncWooCommerceAddress($userId, $city, $location);

        return $address;
    }

    private function syncWooCommerceAddress(int $userId, string $city, string $location): void
    {
        if (!class_exists('\WC_Customer')) {
            return;
        }

        $customer = new \WC_Customer($userId);
        $customer->set_shipping_city($city);
        $customer->set_shipping_address_1($location);
        $customer->set_shipping_country('UA');
        $customer->save();
    }

    /** @return array{id: string, type: string, city: string, location: string, selected: bool}|null */
    private function woocommerceAddress(int $userId): ?array
    {
        $city = (string) get_user_meta($userId, 'shipping_city', true);
        $location = (string) get_user_meta($userId, 'shipping_address_1', true);

        if ($city === '') {
            $city = (string) get_user_meta($userId, 'billing_city', true);
        }
        if ($location === '') {
            $location = (string) get_user_meta($userId, 'billing_address_1', true);
        }
        if ($city === '' || $location === '') {
            return null;
        }

        $type = (string) get_user_meta($userId, self::DELIVERY_TYPE_META_KEY, true);

        return [
            'id' => 'woocommerce',
            'type' => in_array($type, self::ALLOWED_TYPES, true) ? $type : 'Кур’єрська доставка',
            'city' => sanitize_text_field($city),
            'location' => sanitize_text_field($location),
            'selected' => true,
        ];
    }

    /** @return array<int, array{id: string, type: string, city: string, location: string, selected: bool}> */
    private function normalizeAddresses(array $stored): array
    {
        $addresses = [];

        foreach ($stored as $address) {
            if (!is_array($address)) {
                continue;
            }

            $id = sanitize_text_field((string) ($address['id'] ?? ''));
            $type = sanitize_text_field((string) ($address['type'] ?? ''));
            $city = sanitize_text_field((string) ($address['city'] ?? ''));
            $location = sanitize_text_field((string) ($address['location'] ?? ''));

            if ($id === '' || !in_array($type, self::ALLOWED_TYPES, true) || $city === '' || $location === '') {
                continue;
            }

            $addresses[] = compact('id', 'type', 'city', 'location') + ['selected' => false];
        }

        return $addresses;
    }
}
