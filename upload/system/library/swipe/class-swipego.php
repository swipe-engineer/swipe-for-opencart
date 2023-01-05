<?php


if ( !class_exists( 'Swipego' ) ) {
    define( 'SWIPEGO_FILE', __FILE__ );
    define( 'SWIPEGO_URL', plugin_dir_url( SWIPEGO_FILE ) );
    define( 'SWIPEGO_PATH', plugin_dir_path( SWIPEGO_FILE ) );
    define( 'SWIPEGO_VERSION', '1.0.0' );

    class Swipego {

        // Load dependencies
        public function __construct() {
            require_once( SWIPEGO_PATH . 'vendor/autoload.php' );
            require_once( SWIPEGO_PATH . 'includes/abstracts/abstract-swipego-client.php' );
            require_once( SWIPEGO_PATH . 'includes/class-swipego-api.php' );

        }

    }
    new Swipego();
}
