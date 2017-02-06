<?php
namespace \DavidFricker\DataAbstracter;

// connection should be made in constructer
class ContentProvider implements InterfaceContentProvider {
     public static function init() {
        static $instance = null;

        if ($instance === null) {
            $instance = new ContentProvider();
        }

        return $instance;
    }

    /**
     * Set a private __construct to stop instaces being created leading to more than one instance of this class
     */
    private function __construct() {}
}