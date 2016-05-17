<?php

namespace TMCms\App;

use TMCms\Cache\Cacher;
use TMCms\Config\Settings;
use TMCms\DB\QueryAnalyzer;
use TMCms\Files\Finder;
use TMCms\Log\FrontendLogger;
use TMCms\Routing\MVC;
use TMCms\Routing\Router;
use TMCms\Routing\Structure;
use TMCms\Strings\ExternalTemplater;
use TMCms\Strings\Optimize;
use TMCms\Templates\Components;
use TMCms\Templates\Page;
use TMCms\Templates\PageHead;
use TMCms\Templates\PageTail;
use TMCms\Templates\Plugin;
use TMCms\Templates\VisualEdit;
use TMCms\Traits\singletonInstanceTrait;

defined('INC') or exit;

/**
 * Front Application - site itself
 * Class Frontend
 */
class Frontend
{
    use singletonInstanceTrait;

    /**
     * @var MVC $mvc_instance
     */
    private $mvc_instance;
    /**
     * @var Router $router_iwnstance
     */
    private $router_instance;
    /**
     * @var string
     */
    private $html;
    /**
     * @var bool
     */
    private $use_html_file_without_parse = false;
    /**
     * @var bool
     */
    private $cached_page_html = false;


    private function __construct()
    {
        $this->init();

        $this->parse();
    }

    public function __destruct()
    {
        // Save all previous DB queries to analyzer if enabled (for debug and benchmarks)
        if (Settings::get('analyze_db_queries')) {
            QueryAnalyzer::getInstance()
                ->store();
        }
    }

    private function init()
    {
        // Check if allowed to open site in case of IP-restricted access
        $ips = Settings::get('allowed_ips');
        if ($ips) {

            // IPs are separated by newline
            $ips = explode("\n", $ips);
            // Remove empty lines and odd spaces
            foreach ($ips as & $v) {
                $v = trim($v);
            }

            // If still have IP in range and client is blocked - show error
            if ($ips && !in_array(IP, $ips)) {

                if (Settings::isFrontendLogEnabled()) {
                    FrontendLogger::getInstance()->err('IP forbidden');
                }

                if (!headers_sent()) {
                    header('HTTP/1.1 403 Forbidden');
                }
                die('Error 403. Forbidden');
            }
        }

        // This router may be custom. It must return compatible array
        $router = Router::getInstance();

        // Save page data fo future use
        $this->router_instance = $router;
    }

    private function parse()
    {
        // If file is cached - return alredy generated HTML
        if (Settings::isCacheEnabled() && Settings::get('use_file_cache_for_all_pages')) {
            // Find in cache
            if (Settings::isCacheEnabled()) {
                $this->cached_page_html = Cacher::getInstance()
                    ->getDefaultCacher()
                    ->get('html_' . PATH_INTERNAL_MD5);
            }
            if ($this->cached_page_html) {
                if (Settings::isFrontendLogEnabled()) {
                    FrontendLogger::getInstance()->log('Loading cached HTML');
                }
                return;
            }
        }

        /* Prepare page for composing */

        // Read main template file content
        $this->readTemplateContent();

        // If we have external template engine like Twig or Smarty
        $this->processExternalTemplater();

        // If enabled parsing only HTML without system components
        if ($this->use_html_file_without_parse) {
            // No parse required
            return;
        }

        // Start Visual edit for drawing editable fields around system components - if enabled
        VisualEdit::getInstance()
            ->init();

        /* Start composing HTML page */

        // Prepend <head>
        Page::setHead(
            PageHead::getInstance()
                ->setTitle($this->router_instance->getPageData()['title'])
                ->setMetaKeywords($this->router_instance->getPageData()['keywords'])
                ->setMetaDescription($this->router_instance->getPageData()['description'])
        );

        // Script for sending JS errors if not disabled. System sends JS error to support email
        if (CFG_MAIL_ERRORS && Settings::isProductionState() && !Settings::get('do_not_send_js_errors')) {
            PageHead::getInstance()
                ->addJsUrl('send_error.js')
                ->addJS('register_js_error.ini(\'' . DIR_CMS_URL . '\');');
        }

        /* Start replacing template vars with appropriate component content */

        if (Settings::isCacheEnabled()) {
            if (Settings::isFrontendLogEnabled()) {
                FrontendLogger::getInstance()->log('Loading cached replaceable elements');
            }
            $cached_replaces = Cacher::getInstance()
                ->getDefaultCacher()
                ->get('template_elements_' . PATH_INTERNAL_MD5);
        } else {
            $cached_replaces = [];
        }

        // We need iteration to call all components called in components
        $no_more_elements = false;
        while (!$no_more_elements) {
            // Component replaces in templates from template ...
            if (!Settings::isProductionState() || !$cached_replaces || !isset($cached_replaces['elements'], $cached_replaces['replaces'])) {
                // Find which components are used in template
                $res = Components::parseForComponents($this->html);
                $so = count($res[0]);
                $elements = [];

                // Get elements for every component
                for ($i = 0; $i < $so; ++$i) {
                    $file = $res[1][$i]; // File with elements
                    $class = $file; // Class in file with elements
                    $method = $res[3][$i] ? $res[3][$i] : $res[2][$i]; // Method with element in class
                    // If method is not defined - call index
                    if (!$method) {
                        $method = 'index';
                    }

                    // Component may have modifier params in template that are farther pushed in elements
                    $modifiers = [];
                    if ($res[4][$i]) {
                        $modifiers = explode('|', $res[4][$i]);
                    }

                    $elements[] = ['file' => $file, 'class' => $class, 'method' => $method, 'modifiers' => $modifiers];
                }

                // Save in cache to prevent future parsing of the same template
                if (Settings::isCacheEnabled()) {
                    Cacher::getInstance()
                        ->getDefaultCacher()
                        ->set('template_elements_' . PATH_INTERNAL_MD5, [
                            'elements' => $elements,
                            'replaces' => $res
                        ]);
                }
            } else {
                // ... or set from cache
                $elements = $cached_replaces['elements'];
                $res = $cached_replaces['replaces'];
            }

            // No more elements found in HTML
            if (!$elements) {
                $no_more_elements = true;
            }

            // Replace component values in template
            $this->replaceElements($elements, $res);
        }

        // Append post-scripts before ending body tag
        Page::setTail(PageTail::getInstance());
    }

    /**
     * Read template content - file that is set for current requested page
     * @return string
     */
    private function readTemplateContent()
    {
        // Get page content if static file used
        if ($this->router_instance->getPageData()['html_file'] && file_exists(DIR_BASE . $this->router_instance->getPageData()['html_file'])) {
            if (Settings::isFrontendLogEnabled()) {
                FrontendLogger::getInstance()->log('Rendering simple HTML file');
            }

            ob_start();
            // Read file as is
            require_once DIR_BASE . $this->router_instance->getPageData()['html_file'];

            $this->html = ob_get_clean();
            // Prevent future component parse
            $this->use_html_file_without_parse = true;

            // Usual template read for parsing
        } elseif (is_file($this->router_instance->getPageData()['template_file'])) {

            if (Settings::isFrontendLogEnabled()) {
                FrontendLogger::getInstance()->log('Loading page using ccomponents');
            }

            // Get page content with components
            $this->html = file_get_contents($this->router_instance->getPageData()['template_file']);

            // Nothing found
        } else {
            $this->html = 'No template "' . $this->router_instance->getPageData()['template_file'] . '" for this page.';
        }
    }

    /**
     * File may be preprocessed with external template engine(-s) before parsing for components
     */
    private function processExternalTemplater()
    {
        $template_extension = pathinfo($this->router_instance->getPageData()['file'], PATHINFO_EXTENSION);

        if ($template_extension == 'twig') {
            if (Settings::isFrontendLogEnabled()) {
                FrontendLogger::getInstance()->log('Processing twig template');
            }
            $templater = new ExternalTemplater('twig');
            $this->html = $templater->processHtml($this->html, $this->router_instance->getPageData());
        }

        // Add any other template engines here - modify $this->html
    }

    /**
     * Replaces component parts with it's real data set in admin panel
     * @param array $elements
     * @param array $res
     * @return string
     */
    private function replaceElements($elements, $res)
    {
        $replaces = [];

        // Some components may be disabled for current page
        $disabled_components = Settings::get('disablable_components') ? Structure::getDisabled() : [];

        // May be another MVC class that implements required features
        $this->mvc_instance = new MVC();

        // Change elements to its' real data
        while (list($k, $v) = each($elements)) {

            // Skip disabled
            if (in_array($v['class'], $disabled_components)) {
                $replaces[$res[0][$k]] = '';
            } else {
                // Usual replace
                $replaces[$res[0][$k]] = $this->callReplace($v);
            }

        }
        // Replace data with its' component variables in template
        if ($replaces) {
            reset($replaces);
            while (list($k, $v) = each($replaces)) {
                $this->html = str_replace($k, $v, $this->html);
            }
        }
    }

    /**
     * Replace one component value with real data set in admin panel for requested page
     * No check for file existance in it method - for speeding up processing.
     * Anyway, you will receive standard php error if requested file is not found.
     * @param array $component
     * @return string
     */
    private function callReplace($component)
    {
        ob_start();

        // Get component from Controller
        if ($component['file'] == 'plugin') {
            // Reusable plugin found in placeholder

            $selected_plugin_file = Plugin::getSelectedPluginValue($component['class']);

            if ($selected_plugin_file) {
                // Require file with plugin class
                $file_with_plugin = Finder::getInstance()
                    ->searchForRealPath($selected_plugin_file, Finder::TYPE_PLUGINS);
                require_once DIR_BASE . $file_with_plugin;

                $plugin_class_name = str_replace('.php', '', $selected_plugin_file);
                $plugin_class_name = str_replace('plugin', 'Plugin', $plugin_class_name); // Just in case

                /** @var Plugin $plugin_object */
                $plugin_object = new $plugin_class_name;
                $plugin_object->render();
            }

        } else {
            // Usual component, try Front site folder
            // Controller
            $this->mvc_instance->setComponentName($component['class']);
            $this->mvc_instance->setModifiers($component['modifiers']);
            $class = ucfirst($component['class']);

            $file = DIR_FRONT_CONTROLLERS . $component['file'] . '.php';
            if (is_file($file)) {
                require_once $file;
                $model_class = $class . 'Controller';
                $this->mvc_instance->setController($model_class);
            }

            // View
            $file = DIR_FRONT_VIEWS . $component['file'] . '.php';
            if (is_file($file)) {
                require_once $file;
                $view_class = $class . 'View';
                $this->mvc_instance->setView($view_class);
            }

            $this->mvc_instance->setMethod($component['method']);

            $this->mvc_instance->outputController();
            $this->mvc_instance->outputView();
        }

        return trim(ob_get_clean());
    }

    /**
     * Print processed page template with all data
     * @return string
     */
    public function __toString()
    {
        // If content is is rendered from cache
        if (Settings::isCacheEnabled() && $this->cached_page_html) {
            return $this->cached_page_html;
        }

        // Using clickmap script for client click tracking
        if (Settings::get('clickmap')) {
            // Show map on page
            if (isset($_GET['cms_view_clickmap'])) {
                // Load script to show clickmap container
                PageTail::getInstance()->addJsUrl('clickmap_frontend.js');
                PageHead::getInstance()->addJs('cms_page_id = ' . PAGE_ID);
            } else {
                // Just saving clicks - request scripts for registering clicks
                PageTail::getInstance()->addJsUrl('clickmap_register.js');
                PageHead::getInstance()->addJs('cms_page_id = ' . PAGE_ID);
            }
        }

        // Require js for Visual editor
        if (VisualEdit::getInstance()->isEnabled()) {
            PageHead::getInstance()->addJsUrl('visual_edit.js');
            PageHead::getInstance()->addJs('cms_page_id = "' . PAGE_ID . '"');
        }

        // Render HTML
        ob_start();

        // Static page from file
        if ($this->use_html_file_without_parse) {
            echo $this->html;
        } else {
            // Parse content

            // Hide e-mails from bots
            if (strpos($this->html, '@') !== false && preg_match_all('`\<a([^>]+)href\=\"mailto\:([^">]+)\"([^>]*)\>(.+)\<\/a\>`ismU', $this->html, $matches)) {
                PageHead::getInstance()->addJsUrl('email_rewrite.js');
                $matches[5] = [];
                // Replace emails in content with script calls
                foreach ($matches[0] as $k => $v) {
                    // No email?
                    if (isset($matches[5][$v])) {
                        continue;
                    }

                    // No @ symbol?
                    $s = explode('@', $matches[2][$k]);
                    if (count($s) !== 2) {
                        continue;
                    }

                    // No zone?
                    $domain1 = explode('.', $s[1]);
                    $s = $s[0];
                    if (count($domain1) < 2) {
                        continue;
                    }

                    // Now can replace
                    $domain0 = array_pop($domain1);
                    $s = '<script>rewem2nortex("' . preg_replace('/\sclass=\"(.+)\"/', '\1', $matches[3][$k]) . '","' . $s . '","' . implode('.', $domain1) . '","' . $domain0 . '"';
                    if ($matches[2][$k] !== $matches[4][$k]) {
                        $s .= ',"' . trim(str_replace(['@', '.'], ['"+"@"+"', '"+"."+"'], preg_replace('`\<([a-z])`', '<"+"\\1', str_replace('"', '\"', $matches[4][$k])))) . '"';
                    }
                    $s .= ');</script>';

                    $matches[5][$v] = $s;
                }
                $matches = $matches[5];

                // Replace found emails with scripts in content
                $this->html = str_replace(array_keys($matches), $matches, $this->html);
            }

            // For developers using git - site version from latest git commit, add to last meta tag
            if (function_exists('exec')) {
                $output = [];
                exec('git log -1 --pretty=format:\'%h (%ci)\' --abbrev-commit', $output);
                if ($output && isset($output[0])) {
                    PageHead::getInstance()->addMeta($output[0], 'X-Version');
                }
            }

            // Page with components itself
            $this->outputHead();
            // Put body tag if not found in template
            if (!strpos($this->html, '<body')) { // No trailing bracket ! may have class
                $classes = PageHead::getInstance()->getBodyCssClasses();
                echo '<body' . ($classes ? ' class="' . implode(' ', $classes) . '"' : '') . '>';
            }

            // Main page content
            $this->outputHtml();

            // Post-scripts
            $this->outputTail();

            // Put closing body tag if not found in template
            if (!strpos($this->html, '</body>')) {
                echo '</body>';
            }

            echo '</html>';
        }

        $html = ob_get_clean();


        // HTML optimization in rendered content
        if (Settings::get('optimize_html')) {
            $html = Optimize::HTML($html);
        }

        // Put in cache
        if (Settings::get('use_file_cache_for_all_pages') && Settings::isCacheEnabled()) {
            Cacher::getInstance()
                ->getDefaultCacher()
                ->set('html_' . PATH_INTERNAL_MD5, $html);
        }

        // Encode ff browser supports gzip
        if (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false) {
            $html = gzencode($html, 6); // 6 is ok with speed and compression rate
            header('Content-Encoding: gzip');
        }

        // Set cache headers for one hour
        if (Settings::isCacheEnabled() && !headers_sent()) {
            header("Cache-Control: max-age=2592000");
            header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + 3600));
        }

        return $html;
    }

    /**
     * Request content for head tag
     */
    private function outputHead()
    {
        echo $this->mvc_instance->getHead();
    }

    private function outputHtml()
    {
        echo $this->html;
    }

    private function outputTail()
    {
        echo $this->mvc_instance->getTail();
    }
}