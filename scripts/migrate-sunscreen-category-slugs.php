<?php

if (!defined('ABSPATH')) {
    exit(1);
}

$migrations = [
    461 => [
        'name' => 'Сонцезахисний догляд',
        'parent' => 0,
        'old_slug' => 'gunes-bakim-urunleri',
        'new_slug' => 'sonczezahysnyj-doglyad',
    ],
    474 => [
        'name' => 'Дитячі сонцезахисні креми',
        'parent' => 461,
        'old_slug' => 'cocuk-gunes-kremleri',
        'new_slug' => 'dytyachi-sonczezahysni-kremy',
    ],
    475 => [
        'name' => 'Догляд після засмаги',
        'parent' => 461,
        'old_slug' => 'gunes-sonrasi-bakim',
        'new_slug' => 'doglyad-pislya-zasmagy',
    ],
    473 => [
        'name' => 'Засоби для засмаги',
        'parent' => 461,
        'old_slug' => 'bronzlastiricilar',
        'new_slug' => 'zasoby-dlya-zasmagy',
    ],
    477 => [
        'name' => 'Сонцезахисні креми для обличчя',
        'parent' => 461,
        'old_slug' => 'yuz-gunes-kremleri',
        'new_slug' => 'sonczezahysni-kremy-dlya-oblychchya',
    ],
    476 => [
        'name' => 'Сонцезахисні креми для тіла',
        'parent' => 461,
        'old_slug' => 'vucut-gunes-kremleri',
        'new_slug' => 'sonczezahysni-kremy-dlya-tila',
    ],
];

$pending = [];

foreach ($migrations as $termId => $migration) {
    $term = get_term($termId, 'product_cat');

    if (!$term instanceof WP_Term) {
        WP_CLI::error(sprintf('Expected product category term %d was not found.', $termId));
    }

    if ($term->name !== $migration['name'] || (int) $term->parent !== $migration['parent']) {
        WP_CLI::error(sprintf('Product category term %d does not match the expected identity.', $termId));
    }

    if (!in_array($term->slug, [$migration['old_slug'], $migration['new_slug']], true)) {
        WP_CLI::error(sprintf('Product category term %d has unexpected slug %s.', $termId, $term->slug));
    }

    $conflict = get_term_by('slug', $migration['new_slug'], 'product_cat');

    if ($conflict instanceof WP_Term && (int) $conflict->term_id !== $termId) {
        WP_CLI::error(sprintf('Target slug %s is already assigned to term %d.', $migration['new_slug'], $conflict->term_id));
    }

    if ($term->slug === $migration['old_slug']) {
        $pending[$termId] = $migration;
    }
}

$updated = [];

foreach ($pending as $termId => $migration) {
    $result = wp_update_term($termId, 'product_cat', ['slug' => $migration['new_slug']]);

    if (is_wp_error($result)) {
        foreach (array_reverse($updated, true) as $updatedTermId => $updatedMigration) {
            wp_update_term($updatedTermId, 'product_cat', ['slug' => $updatedMigration['old_slug']]);
        }

        WP_CLI::error(sprintf('Could not update term %d: %s', $termId, $result->get_error_message()));
    }

    $updated[$termId] = $migration;
}

foreach ($migrations as $termId => $migration) {
    $term = get_term($termId, 'product_cat');

    if (!$term instanceof WP_Term || $term->slug !== $migration['new_slug']) {
        WP_CLI::error(sprintf('Post-migration verification failed for term %d.', $termId));
    }
}

WP_CLI::success(sprintf('Verified six sunscreen category slugs; updated %d.', count($updated)));
