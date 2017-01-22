<?php

namespace TMCms\Routing;

use TMCms\Templates\PageHead;
use TMCms\Templates\PageTail;

defined('INC') or exit;

/**
 * Class MVC
 */
class MVC
{
    /**
     * @var array
     */
    private static $controllers = [];
    /**
     * @var array
     */
    private static $views = [];

    /**
     * @var array
     */
    private $data = [];
    /**
     * @var string
     */
    private $component_name;
    /**
     * @var array
     */
    private $modifiers;
    /**
     * @var string
     */
    private $controller;
    /**
     * @var string $view
     */
    private $view;
    /**
     * @var string
     */
    private $method;
    /**
     * @var PageHead
     */
    private $page_head;
    /**
     * @var PageTail
     */
    private $page_tail;

    /**
     *
     */
    public function __construct()
    {
        $this->page_head = PageHead::getInstance();
        $this->page_tail = PageTail::getInstance();
    }

    /**
     * @return PageHead
     */
    public function getHead()
    {
        return $this->page_head;
    }

    /**
     * @return PageTail
     */
    public function getTail()
    {
        return $this->page_tail;
    }

    /**
     * @return Controller
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * @param string $controller
     */
    public function setController($controller)
    {
        $this->controller = $controller;
    }

    /**
     * @param string $view
     */
    public function setView($view)
    {
        $this->view = $view;
    }

    /**
     * This method may be accessed from Controller to set appropriate view.
     * View can be dynamically changed in Controller
     * @param string $method
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }

    /**
     * @return PageHead
     */
    public function getComponentName()
    {
        return $this->component_name;
    }

    /**
     * @param string $component_name
     */
    public function setComponentName($component_name)
    {
        $this->component_name = $component_name;
    }

    /**
     * Get data from controller
     */
    public function outputController()
    {
        // Do nothing
        if (!$this->controller) {
            return;
        }

        /**
         * @var Controller $controller
         */

        // We init every Controller only once
        if (!isset(self::$controllers[$this->controller])) {
            if (!class_exists($this->controller)) {
                $this->controller = str_replace('_', '', $this->controller);
            }

            $controller = new $this->controller($this);
            // Set up shared data
            $controller->setUp();
            self::$controllers[$this->controller] = $controller;
        }
        $method = $this->method;

        if (!method_exists(self::$controllers[$this->controller], $method)) {
            dump('Method "'. $method .'" in class "'. $this->controller .'" not found.');
        }

        // Require file with data
        $method_file = DIR_FRONT_CONTROLLERS . $this->getComponentName() . '.' . $method . '.php';
        if (is_file($method_file)) {
            require_once $method_file;
        }

        // Execute controller method
        $data = self::$controllers[$this->controller]->$method($this->getModifiers());

        // Merge returned data
        if ($data) {
            $this->data = $this->data + $data;
        }
    }

    /**
     * Gets HTML code from view, passing all data generated within controller
     */
    public function outputView()
    {
        // Can have Components without views
        if (!$this->view) {
            return;
        }

        /**
         * @var View $view
         */

        // We init every View only once
        if (!isset(self::$views[$this->view])) {
            if (!class_exists($this->view)) {
                $this->view = str_replace('_', '', $this->view);
            }

            $view = new $this->view($this);
            // Set up shared data
            $view->setUp();
            self::$views[$this->view] = $view;
        }
        $method = $this->method;

        if (!method_exists(self::$views[$this->view], $method)) {
            dump('Method "'. $method .'" in class "'. $this->view .'" not found.');
        }

        // Require file with data
        $method_file = DIR_FRONT_VIEWS . $this->getComponentName() . '.' . $method . '.php';
        if (is_file($method_file)) {
            require_once $method_file;
        }

        // Execute view method
        self::$views[$this->view]->$method($this->data);
    }

    /**
     * @param string $param
     * @param mixed $value
     */
    public function setParam($param, $value)
    {
        $this->data[$param] = $value;
    }

    /**
     * @param string $param
     * @return mixed
     */
    public function getParam($param)
    {
        return $this->data[$param];
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $modifiers
     * @return $this
     */
    public function setModifiers($modifiers)
    {
        $this->modifiers = $modifiers;

        return $this;
    }

    /**
     * @return array
     */
    public function getModifiers()
    {
        return $this->modifiers;
    }
}