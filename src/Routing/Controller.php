<?php

namespace TMCms\Routing;

use TMCms\Templates\Components;

defined('INC') or exit;

/**
 * Class CmsController
 */
class Controller extends MVC
{
    /**
     * @var array
     */
    public static $components = array();
    /**
     * @var MVC
     */
    protected $mvc_instance;

    /**
     * @param MVC $mvc
     */
    public function __construct(MVC $mvc)
    {
        $this->mvc_instance = $mvc;
    }

    /**
     * Overwrite this method in required Controller class
     * @return array
     */
    public static function getComponents()
    {
        return [];
        /* EXAMPLE
        return [
            'title' = [], // Means default type text
            'text' => [
                'type' => 'textarea', // textarea | text
    			'edit' => 'files' // wysiwyg | pages | files
            ]
        ];
        */
    }

    /**
     * @param string $name
     * @return string
     */
    public function getComponentValue($name)
    {
        return Components::get($name, $this->getMvc()->getComponentName());
    }

    /**
     *
     * @return MVC
     */
    protected function getMvc()
    {
        return $this->mvc_instance;
    }

    /**
     * This function is called first after Controller creation - use it to load shared data
     */
    public function setUp() {

    }

    /**
     * Check that modifier in charge is set in template
     * @param string $modifier
     * @return bool
     */
    public function checkModifier($modifier) {
        return in_array($modifier, $this->getMvc()->getModifiers());
    }
}
