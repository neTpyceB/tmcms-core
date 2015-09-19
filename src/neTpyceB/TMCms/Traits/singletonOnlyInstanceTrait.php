<?php
namespace neTpyceB\TMCms\Traits;

defined ('INC') or exit;

/**
 * Trait singletonInstanceTrait means that class CAN ONLY be used as singleton to get created instance.
 * Should be used in one-per-run classes
 * @package neTpyceB\TMCms\Traits
 */
trait singletonOnlyInstanceTrait {
    use singletonInstanceTrait;

    /**
     * This will prevent of creation multiple instances
     */
    private function __construct() {

    }
}