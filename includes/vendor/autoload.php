<?php
/**
 * Lightweight autoloader for bundled vendor libraries.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

spl_autoload_register(
    static function ( $class ) {
        if ( 0 !== strpos( $class, 'Dompdf\\' ) ) {
            return;
        }

        $relative = substr( $class, strlen( 'Dompdf\\' ) );
        $path     = __DIR__ . '/dompdf/' . str_replace( '\\', '/', $relative ) . '.php';

        if ( file_exists( $path ) ) {
            require_once $path;
        }
    }
);
