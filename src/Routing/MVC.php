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
     * @return string
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * @param string $controller
     * @return $this
     */
    public function setController($controller)
    {
        $this->controller = $controller;

        return $this;
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
     * Get data from controller
     *
     * @return $this
     */
    public function outputController()
    {
        // Do nothing
        if (!$this->controller) {
            return $this;
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

        // Execute controller method
        $data = self::$controllers[$this->controller]->$method($this->getModifiers());

        // Require file with data
        $method_file = DIR_FRONT_CONTROLLERS . $this->getComponentName() . '.' . $method . '.php';
        if (is_file($method_file)) {
            require_once $method_file;
        }
        // Require file with data in sub folder
        $method_file = DIR_FRONT_CONTROLLERS . $this->getComponentName() . '/' . $method . '.php';
        if (is_file($method_file)) {
            require_once $method_file;
        }

        // Merge returned data
        if ($data) {
            $this->data += $data;
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getModifiers()
    {
        return $this->modifiers;
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
     * Gets HTML code from view, passing all data generated within controller
     *
     * @return string
     */
    public function outputView()
    {
        // Can have Components without views
        if (!$this->view) {
            return '';
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

        // Execute view method
        $this->getCurrentViewObject()->$method($this->data);

        // Require file with data
        $method_file = DIR_FRONT_VIEWS . $this->getComponentName() . '.' . $method . '.php';
        if (is_file($method_file)) {
            require_once $method_file;
        }
        // Require file with data in sub folder
        $method_file = DIR_FRONT_VIEWS . $this->getComponentName() . '/' . $method . '.php';
        if (is_file($method_file)) {
            require_once $method_file;
        }
    }

    /**
     * @return View
     */
    public function getCurrentViewObject()
    {
        return self::$views[$this->view];
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
}