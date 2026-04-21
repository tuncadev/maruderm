<?php

declare( strict_types=1 );

namespace Maruderm\Kernel;

use RuntimeException;

if ( ! defined( 'ABSPATH' ) ) {
    exit();
}

/**
 * Trait Loadable.
 *
 * Provides a reusable method for modules to register themselves with Application.
 * This trait simplifies the process of adding a module to the system by
 * encapsulating the registration logic.
 */
trait Loadable {

    /**
     * Registers the current class with Application.
     *
     * This static method is called at the end of each module file to ensure
     * it is added to the Application registry for later initialization.
     *
     * @throws RuntimeException If Application class is not available.
     */
    final public static function load(): void {
        if ( ! class_exists( Application::class ) ) {
            throw new RuntimeException( 'Application class is not available for module registration' );
        }
        Application::register_class( static::class );
    }
}
