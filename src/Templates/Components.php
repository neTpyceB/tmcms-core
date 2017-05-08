<?php

namespace TMCms\Templates;

use TMCms\Admin\Structure\Entity\PageComponentEntity;
use TMCms\Admin\Structure\Entity\PageComponentCustomEntityRepository;
use TMCms\Admin\Structure\Entity\PageComponentHistory;
use TMCms\Admin\Structure\Entity\PageComponentHistoryRepository;
use TMCms\Admin\Structure\Entity\PageComponentEntityRepository;
use TMCms\Cache\Cacher;
use TMCms\Config\Settings;
use TMCms\Routing\Controller;

defined('INC') or exit;

/**
 * Class Components
 */
class Components
{
    /**
     * @var string regular expression to find all components that are like {%file_and_class:method|possible_modifier_one|possible_modifier_two%}
     */
    public static $preg_pattern = '/{%([a-zA-Z0-9-_]+):?([a-zA-Z0-9-_]*):?([a-zA-Z0-9-_]*)\|?([a-zA-Z0-9-_\|]*)\|?([a-zA-Z0-9-_\|]*)%}/';
    public static $preg_pattern_alternative = '/\[%([a-zA-Z0-9-_]+):?([a-zA-Z0-9-_]*):?([a-zA-Z0-9-_]*)\|?([a-zA-Z0-9-_\|]*)%\]/'; // Used in TWIG templates
    /**
     * @var array
     */
    private static $_init = [];
    /**
     * @var array
     */
    private static $_components = [];
    /**
     * @var bool pattern for finding components, can use ald versions saved in history
     */
    private static $_version = false;

    /**
     * @param string $file template file path
     * @return array
     */
    public static function outputForCms($file)
    {
        $html = file_get_contents($file);
        $parsed_components = self::parseForComponents($html);

        $components_classes_array = $parsed_components[1];
        $components_methods_array = $parsed_components[2];

        $components_array = [];

        foreach ($components_classes_array as $index => $class) {
            $components_array[$class][] = $components_methods_array[$index] ? $components_methods_array[$index] : 'index';
        }

        $elements = [];

        foreach ($components_array as $component_class => $component_methods) {
            if ($component_class == 'plugin') {

                foreach ($component_methods as $component_method) {

                    $elements[$component_class . '_' . $component_method] = [
                        'type'     => 'plugin',
                        'file'     => $component_class,
                        'class'    => $component_class . '_' . $component_method,
                        'elements' => [
                            'select_plugin' => [
                                'type'    => 'select',
                                'options' => ['' => '---'] + Plugin::getInstance()->getPluginFilePairs(),
                            ],
                        ],
                    ];
                }
            } else {
                require_once DIR_FRONT_CONTROLLERS . $component_class . '.php';

                $controller_class = ucfirst($component_class) . 'Controller';

                if (!class_exists($controller_class)) {
                    $controller_class = str_replace('_', '', $controller_class);
                }

                $elements_data = $controller_class::getComponents();

                $class_methods = get_class_methods($controller_class);

                foreach ($class_methods as $component_method) {
                    // Skip those methods that are not called for template
                    if (!isset($components_array[$component_class]) || !in_array($component_method, $components_array[$component_class])) {
                        continue;
                    }

                    $controller_method = 'getComponents_' . $component_method;

                    if (method_exists($controller_class, $controller_method)) {
                        $elements_data = array_merge($elements_data, $controller_class::$controller_method());
                    }

                    $elements[$component_class] = [
                        'type' => 'component',
                        'file' => $component_class,
                        'class' => $component_class,
                        'elements' => $elements_data
                    ];
                }
            }
        }

        return $elements;
    }

    /**
     * @param string $html
     * @return array
     */
    public static function parseForComponents($html)
    {
        $res = [];
        preg_match_all(self::$preg_pattern_alternative, $html, $res);
        if (!isset($res[0]) || !$res[0]) {
            preg_match_all(self::$preg_pattern, $html, $res);
        }
        return $res;
    }

    /**
     * @param string $component
     * @param string $class
     * @return string|null
     */
    public static function get($component, $class)
    {
        // Get all data for component class in one query
        if (!isset(self::$_init[$class])) {
            self::init($class);
        }

        $component = $class . '_' . $component;

        // Return selected component
        if (isset(self::$_components[$class][$component])) {
            return self::$_components[$class][$component];
        }

        return NULL;
    }

    /**
     * Load all components for class in private cache variable
     * @param string $class
     */
    private static function init($class)
    {
        self::$_init[$class] = true;

        if (isset($_GET['cms_content_version']) && ctype_digit($_GET['cms_content_version'])) {
            self::$_version = (int)$_GET['cms_content_version'];
        }

        $cache_key = 'components_' . PAGE_ID . '_v_' . self::$_version;
        $res = NULL;
        if (Settings::isCacheEnabled()) {
            $res = Cacher::getInstance()->getDefaultCacher()->get($cache_key);
        }

        if (is_array($res)) { // Set from cache
            self::$_components[$class] = $res;
        } else { // Get from DB
            if (self::$_version) {
                // From history
                $components_collection = new PageComponentHistoryRepository();
                $components_collection->setWhereVersion(self::$_version);
            } else {
                // From current version
                $components_collection = new PageComponentEntityRepository();
            }
            $components_collection->setWherePageId(PAGE_ID);
            self::$_components[$class] = $components_collection->getPairs('data', 'component');

            // Get Custom components
            $customs = new PageComponentCustomEntityRepository();
            $customs->setWherePageId(PAGE_ID);
            $customs->setWhereComponent($class);
            $customs->addOrderByField('order');

            $custom_components_in_database = $customs->getAsArrayOfObjectData(true);
            foreach ($custom_components_in_database as $custom) {
                self::$_components[$class][$class . '_' . $custom['tab']][$custom['order']][$custom['name']] = $custom['value'];
            }

            // Make all custom components have all fields
            $custom_components_in_controller = self::getControllerCustomComponents($class);
            foreach ($custom_components_in_database as $custom) {
                // Set unavailable fields as empty
                foreach (self::$_components[$class][$class . '_' . $custom['tab']] as $order => $data) {
                    if (isset($custom_components_in_controller[$class . '_' . $custom['tab']])
                        && isset(self::$_components[$class][$class . '_' . $custom['tab']][$order])
                        && (
                            count(self::$_components[$class][$class . '_' . $custom['tab']][$order])
                            !=
                            count($custom_components_in_controller[$class . '_' . $custom['tab']]))
                    ) {
                        foreach ($custom_components_in_controller[$class . '_' . $custom['tab']] as $field_key => $field_value) {
                            if (!isset(self::$_components[$class][$class . '_' . $custom['tab']][$order][$field_key])) {
                                self::$_components[$class][$class . '_' . $custom['tab']][$order][$field_key] = ''; // Set empty field
                            }
                        }
                    }
                }

            }

            // Set any empty value to all non-existing components to avoid error in front
            foreach ($custom_components_in_controller as $custom_key => $custom_fields) {
                list($class, $custom_tab) = explode('_', $custom_key, 2);
                if (!isset(self::$_components[$class][$class . '_' . $custom_tab]) || !is_array(self::$_components[$class][$class . '_' . $custom_tab])) {
                    self::$_components[$class][$class . '_' . $custom_tab] = []; // Set empty array to iterate from
                }
            }

            // Save to cache
            if (Settings::isCacheEnabled()) {
                Cacher::getInstance()->getDefaultCacher()->set($cache_key, self::$_components[$class]);
            }
        }
    }

    private static function getControllerCustomComponents($class)
    {
        $controller_name = ucfirst($class) . 'Controller';
        if (!class_exists($controller_name)) {
            $file = DIR_FRONT_CONTROLLERS . $class . '.php';
            if (is_file($file)) {
                require_once $file;
            }
        }
        /** @var Controller $controller_name */

        $custom_components = [];
        foreach ($controller_name::getComponents() as $key => $component) {
            if (!isset($component['type']) || $component['type'] != 'custom') {
                continue;
            }

            $custom_components[$class . '_' . $key] = $component['fields'];
        }

        // Custom method names
        foreach (get_class_methods($controller_name) as $component_method) {
            $controller_method = 'getComponents_' . $component_method;

            if (method_exists($controller_name, $controller_method)) {
                foreach ($controller_name::$controller_method() as $key => $component) {
                    if (!isset($component['type']) || $component['type'] != 'custom') {
                        continue;
                    }

                    $custom_components[$class . '_' . $key] = $component['fields'];
                }
            }
        }

        return $custom_components;
    }

    /**
     * Get one component data by page id and name
     * @param $component
     * @param $page_id
     * @return string
     */
    public static function getComponentByPageId($component, $page_id)
    {
        $page_id = (int)$page_id;
        $component = sql_prepare($component);

        $cache_key = 'components_' . $page_id . '_c_' . $component;
        $res = NULL;

        if (Settings::isCacheEnabled()) {
            $res = Cacher::getInstance()->getDefaultCacher()->get($cache_key);

            // Set from cache
            if ($res) {
                return $res;
            }
        }

        // Get from DB
        $res = NULL;
        /** @var PageComponentHistory $page */
        $page = PageComponentEntityRepository::findOneEntityByCriteria([
            'page_id' => $page_id,
            'component' => $component
        ]);

        if ($page) {
            $res = $page->getData();
        }

        // Save to cache
        if (Settings::isCacheEnabled()) {
            Cacher::getInstance()->getDefaultCacher()->set($cache_key, $res);
        }

        return $res;
    }

    public static function getComponentsByPageId($page_id)
    {
        $page_id = (int)$page_id;

        $cache_key = 'components_' . $page_id;

        if (Settings::isCacheEnabled()) {
            $res = Cacher::getInstance()->getDefaultCacher()->get($cache_key);

            // Set from cache
            if ($res) {
                return $res;
            }
        }

        // Get from DB
        $res = [];
        /** @var PageComponentHistory $page */
        $page = PageComponentEntityRepository::findAllEntitiesByCriteria([
            'page_id' => $page_id
        ]);

        $page_arr = [];

        if ($page) {

            foreach ($page as $k => $v):
                /** @var PageComponentEntity $v */
                $page_arr[$v->getComponent()] = $v->getData();
            endforeach;

            $res = $page_arr;
        }

        // Save to cache
        if (Settings::isCacheEnabled()) {
            Cacher::getInstance()->getDefaultCacher()->set($cache_key, $res);
        }


        return $res;
    }

    public static function getCustomComponentsByPageId($page_id)
    {
        // Get Custom components
        $customs = new PageComponentCustomEntityRepository();
        $customs->setWherePageId($page_id);
        $customs->addOrderByField('order');

        $custom_components_in_database = $customs->getAsArrayOfObjectData(true);
        $res = [];
        foreach ($custom_components_in_database as $custom) {
            $class = $custom['component'];
            $res[$class][$class . '_' . $custom['tab']][$custom['order']][$custom['name']] = $custom['value'];
        }

        // Make all custom components have all fields
        foreach ($res as $class => $component) {
            $custom_components_in_controller = self::getControllerCustomComponents($class);
            foreach ($custom_components_in_database as $custom) {
                $class = $custom['component'];
                // Set unavailable fields as empty
                foreach ($res[$custom['component']][$custom['component'] . '_' . $custom['tab']] as $order => $data) {
                    if (isset($custom_components_in_controller[$custom['component'] . '_' . $custom['tab']])
                        && isset($res[$custom['component']][$class . '_' . $custom['tab']][$order])
                        && (
                            count($res[$class][$class . '_' . $custom['tab']][$order])
                            !=
                            count($custom_components_in_controller[$class . '_' . $custom['tab']]))
                    ) {
                        foreach ($custom_components_in_controller[$class . '_' . $custom['tab']] as $field_key => $field_value) {
                            if (!isset($res[$class][$class . '_' . $custom['tab']][$order][$field_key])) {
                                $res[$class][$class . '_' . $custom['tab']][$order][$field_key] = ''; // Set empty field
                            }
                        }
                    }
                }
            }
            // Set any empty value to all non-existing components to avoid error in front
            foreach ($custom_components_in_controller as $custom_key => $custom_fields) {
                list($class, $custom_tab) = explode('_', $custom_key, 2);
                if (!isset($res[$class][$class . '_' . $custom_tab]) || !is_array($res[$class][$class . '_' . $custom_tab])) {
                    $res[$class][$class . '_' . $custom_tab] = []; // Set empty array to iterate from
                }
            }
        }

        return $res;

    }
}