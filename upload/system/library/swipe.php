<?php
   class Swipe {
        public function __construct() {
            require_once( DIR_SYSTEM . 'library/swipe/vendor/autoload.php' );

            require_once( DIR_SYSTEM . 'library/swipe/includes/abstracts/abstract-swipego-client.php' );

            require_once( DIR_SYSTEM . 'library/swipe/includes/class-swipego-api.php' );

        }
    }
    new Swipe();

