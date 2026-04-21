<?php

declare( strict_types=1 );

namespace Maruderm\Kernel;

if ( ! defined( 'ABSPATH' ) ) {
    exit();
}

/**
 * Class Application.
 *
 * Central registry for managing and initializing modules in the Maruderm theme.
 *
 * This class acts as a singleton to provide a single point of control for module
 * registration. It initializes modules listed in the configuration and calls
 * their register() methods to integrate them with WordPress.
 */
class Application {
    /**
     * Singleton instance of Application.
     *
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * Registry of class names implementing Registrable, stored as keys for O(1) lookup.
     *
     * @var array
     */
    private static array $registrable_classes = [];

    /**
     * Instances of registered modules.
     *
     * @var Registrable[]
     */
    private array $modules = [];

    /**
     * Private constructor to enforce singleton pattern.
     */
    private function __construct() {
        // Prevent direct instantiation
    }

    /**
     * Prevents cloning of the singleton instance.
     */
    private function __clone() {
        // Prevent cloning
    }

    /**
     * Returns the singleton instance of Application.
     *
     * @return self
     */
    public static function get_instance(): self {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Registers a class that implements the Registrable interface.
     *
     * This method is typically called by the Loadable trait to add a module to the
     * registry before initialization.
     *
     * @param string $class_name Fully qualified class name.
     */
    public static function register_class( string $class_name ): void {
        self::$registrable_classes[ $class_name ] = true;
    }

    /**
     * Initializes and registers all modules.
     *
     * Checks dependencies before proceeding with module initialization and registration.
     */
    public function init(): void {
        if ( Dependencies::check() ) {
            $this->initialize_modules()->register_all();
        }
    }

    /**
     * Initializes modules from the registered classes.
     *
     * Uses reflection to instantiate only concrete classes that implement Registrable.
     *
     * @return self The current instance of Application.
     */
    private function initialize_modules(): self {

        // Check if the class is already registered
        foreach ( array_keys( self::$registrable_classes ) as $class_name ) {
            try {

                // Skip if the class doesn't exist or is Application itself
                if ( $class_name === self::class || ! class_exists( $class_name ) ) {
                    continue;
                }

                // Use ReflectionClass to inspect the class properties
                $reflection = new \ReflectionClass( $class_name );

                // Skip abstract classes or interfaces or if it doesn't implement Registrable
                if ( $reflection->isAbstract() || $reflection->isInterface() || ! $reflection->implementsInterface( Registrable::class ) ) {
                    continue;
                }

                // Instantiate the class
                $instance = new $class_name();

                // Check if the instance is already registered
                if ( ! $this->has_module( $class_name ) ) {
                    $this->add( $instance );
                }
            } catch ( \Throwable $e ) {
                $message = "Failed to initialize module $class_name: " . $e->getMessage();
                error_log( $message );
            }
        }

        return $this;
    }

    /**
     * Adds a module instance to the registry.
     *
     * @param Registrable $module The module to add.
     *
     * @return self The current instance of Application.
     */
    public function add( Registrable $module ): self {
        $this->modules[] = $module;

        return $this;
    }

    /**
     * Registers all stored modules by calling their register() method.
     */
    private function register_all(): void {

        array_walk( $this->modules, fn( Registrable $module ) => $module->register() );
    }

    /**
     * Retrieves all registered modules.
     *
     * @return array An array of modules.
     */
    public function get_modules(): array {
        return $this->modules;
    }

    /**
     * Retrieves a registered module by its class name.
     *
     * @param string $module_class Fully qualified class name of the module.
     *
     * @return Registrable|null The module instance if found, null otherwise.
     */
    public function get_module( string $module_class ): ?Registrable {

        $found = array_filter( $this->modules, fn( $module ) => $module instanceof $module_class );

        return $found ? reset( $found ) : null;
    }

    /**
     * Checks if a specific module is registered.
     *
     * @param string $module_class Fully qualified class name of the module.
     *
     * @return bool True if the module is registered, false otherwise.
     */
    public function has_module( string $module_class ): bool {
        return $this->get_module( $module_class ) !== null;
    }

    /**
     * Checks if a module is active/registered in the application.
     *
     * @param string $module_name Module class name (short name like 'Assets' or fully qualified).
     *
     * @return bool True if the module is active, false otherwise.
     */
    public function is_module_active( string $module_name ): bool {
        // If it's already a fully qualified class name, check directly
        if ( str_contains( $module_name, '\\' ) ) {
            return $this->has_module( $module_name );
        }

        $module_class = "\\Maruderm\\Modules\\{$module_name}\\{$module_name}";
        if ( $this->has_module( $module_class ) ) {
            return true;
        }

        $module_manager_class = "\\Maruderm\\Modules\\{$module_name}\\{$module_name}Manager";
        if ( $this->has_module( $module_manager_class ) ) {
            return true;
        }

        return false;
    }

    /**
     * Renders an error notice in the WordPress admin interface.
     *
     * @param string $message The primary error message to display.
     * @param string $fix_message Optional. A suggested fix or additional information.
     *
     * @return void
     */
    public function render_error_notice( string $message, string $fix_message = '' ): void {
        $notice = '<div class="notice notice-error"><p>' . wp_kses_post( $message ) . '</p>';

        if ( ! empty( $fix_message ) ) {
            $notice .= '<p><b>' . wp_kses_post( $fix_message ) . '</b></p>';
        }

        $notice .= '</div>';

        error_log( $message . $fix_message );

        add_action(
            'admin_notices',
            function () use ( $notice ) {
                echo wp_kses_post( $notice );
            }
        );
    }

}
