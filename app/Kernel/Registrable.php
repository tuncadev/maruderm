<?php

declare( strict_types=1 );

namespace Maruderm\Kernel;

if ( ! defined( 'ABSPATH' ) ) {
    exit();
}

/**
 * Interface Registrable
 *
 * Should be implemented by all entities that can be registered (CPT, Taxonomy, etc).
 * The register method should be static for compatibility with static registration
 */
interface Registrable {
    /**
     * Registers the module with WordPress
     *
     * This method is invoked by Core to perform any necessary setup,
     * such as adding actions, filters, or registering GraphQL fields
     */
    public function register(): void;
}
