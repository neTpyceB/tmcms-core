<?php

namespace TMCms\App;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use TMCms\Admin\Menu;
use TMCms\Admin\Updater;
use TMCms\Admin\Users;
use TMCms\Admin\Users\Entity\UserLog;
use TMCms\Config\Configuration;
use TMCms\Config\Settings;
use TMCms\Files\Finder;
use TMCms\Services\ServiceManager;
use TMCms\Log\App;
use TMCms\Log\Usage;
use TMCms\Network\Mailer;
use TMCms\Strings\Converter;
use TMCms\Templates\Page;
use TMCms\Templates\PageBody;
use TMCms\Templates\PageHead;
use TMCms\Templates\PageTail;

defined('INC') or exit;

/**
 * Front Application - site itself
 * Class Backend
 */
class Backend
{
    private $p;
    private $p_do;
    private $no_menu;
    private $content = '';

    public function __construct()
    {
        // Check background services
        if (isset($_GET['cms_is_running_background']) && $_GET['cms_is_running_background'] == 1) {
            ServiceManager::checkNeeded();
            exit;
        }

        // Try updating CMS
        if (isset($_GET['key']) && $_GET['key'] == Configuration::getInstance()->get('cms')['unique_key'] && count($_GET) === 2) {

            $updater = Updater::getInstance();

            // Update files from repo
            $updater->updateSourceCode(isset($_GET['branch']) ? $_GET['branch'] : NULL);

            // Update composer only if required
            if (isset($_GET['composer'])) {
                $updater->updateComposerVendors();
            }

            // Update database
            $updater->runMigrations();

            // Run PHPUnit tests
            $updater->runTests();

            // Output
            $out = $updater->getResult();
            $text = json_encode($out, JSON_FORCE_OBJECT);

            echo $text;

            if (stripos($text, 'Code Coverage Report') === false) {
                Mailer::getInstance()
                    ->setMessage('Did not find "Code Coverage Report" in updater response:<br><br>' . $text)
                    ->setRecipient(CMS_SUPPORT_EMAIL)
                    ->setSender(Settings::getCommonEmail(), Configuration::getInstance()->get('site')['name'] . ' - AutoUpdater')
                    ->setSubject('Error found during update')
                    ->send();
            }

            exit;
        }

        // Else - running usual Admin panel

        // Proceed with request
        $this->parseUrl();

        // Save log
        if (Users::getInstance()->isLogged() && Settings::get('save_back_access_log') && !IS_AJAX_REQUEST) {
            $users_log = new UserLog();
            $users_log->save();
        }

        // Init page data
        if (Users::getInstance()->isLogged()) {
            define('LNG', Users::getInstance()->getUserLng());
        }

        $this->sendHeaders();

        $this->prepareHead();

        $this->parseMenu();

        // Post-scripts
        Page::setTail(PageTail::getInstance());

        // Flush application log
        App::flushLog();

        $this->generateContent();
    }

    /**
     * Render page itself
     * @return string
     */
    public function __toString()
    {
        ob_start();

        /**
         * @var Menu $menu
         */
        $menu = (!Menu::getInstance()->isMenuEnabled() ? '' : Menu::getInstance());

        ?><div class="app">
        <?= $menu ? $menu->getMenuHeaderView() : '' ?>
        <section class="layout">
            <?= $menu ?>
            <section class="main-content">
                <div class="content-wrap">
                    <div class="wrapper custom_scrollbar">
                        <?= $this->content ?>
                    </div>
                </div>
                <a class="exit-offscreen"></a>
            </section>
        </section>
        </div><?php

        $html = ob_get_contents();

        Page::setBody(new PageBody($html));

        ob_clean();

        return Page::getHTML();
    }

    private function parseUrl()
    {
        // Some pages do not have menu header nor items
        $this->no_menu = isset($_GET['nomenu']);

        /* Get P and P_DO - these are module and action shortcuts */
        if (!isset($_GET['p'])) {
            $_GET['p'] = 'home';
        }
        if (!isset($_GET['do'])) {
            $_GET['do'] = '_default';
        }

        // Render log=in form if if user is not auth-ed
        if (!Users::getInstance()->isLogged()) {
            $_GET['p'] = 'guest';
            $this->no_menu = true;
        }

        if (!defined('P')) {
            define('P', $_GET['p']);
        }
        if (!defined('P_DO')) {
            define('P_DO', $_GET['do']);
        }

        // Parse URL
        $path = [];
        if ((!$url = parse_url(SELF)) || !isset($url['path'])) {
            die('URL can not be parsed');
        }

        // Generate real path
        foreach (explode('/', $url['path']) as $pa) {
            if ($pa) {
                $path[] = $pa;
            }
        }

        // For non-rewrite hostings remove last file name
        if (end($path) === 'index.php') {
            array_pop($path);
        }

        if ($this->no_menu) {
            Menu::getInstance()->disableMenu();
        }

        // Log CMS usage
        Usage::getInstance()->add(P, P_DO);

        // Rewite $_GET in case constans defined not from params
        $_GET['p'] = P;
        $_GET['do'] = P_DO;

        $this->p = P;
        $this->p_do = P_DO;
    }

    private function sendHeaders()
    {
        // Do not send twice
        if (headers_sent()) {
            return;
        }

        // Set headers to disable cache
        header('Expires: Wed, 01 Jul 2005 08:50:08 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        if (!isset($_SERVER['HTTP_X_FLASH_VERSION'])) {
            header('Pragma: no-cache');
        } // HTTP/1.0
        header('Content-Type: text/html; charset=utf-8');
        if (strpos(USER_AGENT, 'MSIE') !== false) {
            header('Imagetoolbar: no');
        }
    }

    /**
     * Data for HTML <head> generation
     */
    private function prepareHead()
    {
        $config = Configuration::getInstance();
        // Favicon url
        $favicon = !empty($config->get('cms')['favicon']) ? $config->get('cms')['favicon'] : DIR_CMS_IMAGES_URL . 'logo_square.png';

        // Prepare page HTML for head
        PageHead::getInstance()
            ->addHtmlTagAttributes('lang="en" class="no-js"')
            ->setTitle((P_DO !== '_default' ? Converter::symb2Ttl(P_DO) . ' / ' : '') . Converter::symb2Ttl(P) . ' / ' . $config->get('site')['name'] . ' / ' . CMS_NAME . ' v. ' . CMS_VERSION)
//            ->setFavicon($favicon)
//            ->addMeta('name=' . CMS_NAME . ' - ' . $config->get('site')['name'] . '; action-uri=http://' . CFG_DOMAIN . '/cms/; icon-uri=http://' . DIR_CMS_IMAGES_URL . 'logo_square.png', 'msapplication-task')
        ;

        // Core - main design
        PageHead::getInstance()
            ->addCssUrl('css/font-awesome.css')
            ->addCssUrl('css/metronic/simple-line-icons.css')
            ->addCssUrl('bootstrap/css/bootstrap.min.css')
//            ->addCssUrl('css/metronic/components.css')
//            ->addCssUrl('css/metronic/layout.css')
//            ->addCssUrl('css/metronic/darkblue.css')
            ->addCssUrl('css/themify-icons.css')
            ->addCssUrl('css/animate.min.css')
            ->addCssUrl('css/skins/palette.css')
            ->addCssUrl('css/fonts/font.css')
            ->addCssUrl('css/main.css')
            ->addJsUrl('plugins/modernizr.js')
            ->addCssUrl('css.css')
        ;

        // Only for auth-ed users
        if (Users::getInstance()->isLogged()) {
            PageHead::getInstance()
                ->addCssUrl('print_css.css', 'print')
                ->addJsUrl(DIR_CMS_SCRIPTS_URL . 'jquery-2.1.0.min.js')
                ->addJsUrl(DIR_CMS_SCRIPTS_URL . 'jquery.form.min.js')// Ajaxify forms
                ->addJsUrl('js/jquery.bpopup.min.js')// Popup modals
                ->addJs('var cms_data = {};') // Required for global data
                ->addJs('cms_data.cfg_domain="' . CFG_DOMAIN . '"') // Required for notifications
                ->addJs('cms_data.site_name="' . $config->get('site')['name'] . '"') // Required for notifications
                ->addJsUrl('cms_js.js')
                ->addJsUrl(DIR_CMS_SCRIPTS_URL . 'scripts.js')
                ->addJsUrl('plupload/plupload.full.min.js')
            ;

            // Script for sending JS errors
            if (CFG_MAIL_ERRORS && Settings::isProductionState() && !Settings::get('do_not_send_js_errors')) {
                PageHead::getInstance()
//                    ->addJsUrl('send_error.js')
//                    ->addJS('register_js_error.ini(\'' . DIR_CMS_URL . '\');')
                ;
            }

            PageTail::getInstance()
                ->addCssUrl('context_menu/menu.css')
                ->addCssUrl('plugins/toastr/toastr.min.css')
                ->addCssUrl('plugins/chosen/chosen.min.css') // Beautify selects
                ->addJsUrl('context_menu/menu.js') // Context menu
                ->addJsUrl('bootstrap/js/bootstrap.js')
                ->addJsUrl('plugins/jquery.slimscroll.min.js')
                ->addJsUrl('plugins/jquery.easing.min.js') // Animations
                ->addJsUrl('plugins/appear/jquery.appear.js')
                ->addJsUrl('plugins/jquery.placeholder.js')
                ->addJsUrl('plugins/fastclick.js')
                ->addJsUrl('js/offscreen.js')
                ->addJsUrl('js/main.js')
                ->addJsUrl('js/buttons.js')
                ->addJsUrl('plugins/toastr/toastr.min.js') // Notifications
                ->addJsUrl('js/notifications.js')
                ->addJsUrl('plugins/chosen/chosen.jquery.min.js')
                ->addJsUrl('plugins/chosen/chosen.order.jquery.js')
                ->addJsUrl('ckeditor/ckeditor.js') // Wysiwyg
                ->addJsUrl('plugins/parsley.min.js') // Input validation
            ;
        }

        // Search for custom css
        $custom_css_url = DIR_ASSETS_URL . 'cms.css';
        if (file_exists(DIR_BASE . $custom_css_url)) {
//            PageHead::getInstance()->addCssUrl($custom_css_url);
        } else {
//            PageHead::getInstance()->addCustomString('<!--Create file "'. $custom_css_url .'" if you wish to use custom css file-->');
        }

        // Set head for page
        Page::setHead(PageHead::getInstance());
    }

    private function parseMenu()
    {
        $menu = Menu::getInstance();

        // Add CMS modules
        foreach (['home', 'structure', 'users', 'tools'] as $line) {
            $line = trim($line);
            if (!$line) {
                continue;
            }

            // Show menu item only if current user have access to it
            if ($line && Users::getInstance()->checkAccess($line, '_default')) {
                $menu->addMenuItem($line);
            }
        }

        // Add project custom modules
        $file = DIR_FRONT . 'menu.php';
        if (file_exists($file)) {
            foreach (file($file) AS $line) {
                $line = trim($line);
                // Skip empty lines
                if (!$line) {
                    continue;
                }

                // Skip if commented
                if ($line[0] == '#') {
                    continue;
                }

                // Get key and short representation if have
                if (strpos($line, ':') !== false) {
                    list($name, $representation) = explode(':', $line);
                } else {
                    $name = $representation = $line;
                }

                // Add menu item if have access to page
                if ($name && Users::getInstance()->checkAccess($name, '_default')) {
                    $menu->addMenuItem($name, $representation);
                }
            }
        }

        // Add translations if have project-related files
        Finder::getInstance()->addTranslationsSearchPath(DIR_FRONT . 'translations/');
    }

    private function generateContent()
    {
        ob_start();

        // Set top path
        // TODO add to menu class and keep one main instance - autocreate two crubms and can add more or rewrite
//        echo BreadCrumbs::getInstance()
//            ->addCrumb(__(ucfirst(P)))
//            ->addCrumb(__(ucfirst(P_DO)))
//        ;

        // Requesting P page
        $method = false;

        $call_object = false;
        // Find in classes under Vendor - Modules
        $real_class = Converter::to_camel_case(P);
        $class = '\TMCms\Modules\\' . $real_class . '\Cms' . $real_class;
        if (!class_exists($class)) {
            // Not vendor module - check main CMS admin object
            $class = '\TMCms\Admin\\' . $real_class . '\Cms' . $real_class;
            $call_object = true;
            if (!class_exists($class)) {
                // Search for exclusive module CMS pages created in Project folder for this individual site
                $file_path = DIR_MODULES . strtolower($real_class) . '/' . 'Cms' . $real_class . '.php';
                if (file_exists($file_path)) {
                    require_once $file_path;

                    // Check for module itself
                    $file_path = DIR_MODULES . strtolower($real_class) . '/' . 'Module' . $real_class . '.php';
                    if (file_exists($file_path)) {
                        require_once $file_path;
                    }
                    // Require all objects class files
                    $objects_path = DIR_MODULES . strtolower($real_class) . '/Entity/';
                    if (file_exists($objects_path)) {
                        foreach (array_diff(scandir($objects_path), ['.', '..']) as $object_file) {
                            require_once $objects_path . $object_file;
                        }
                    }

                    // CmsClass
                    $real_class = Converter::to_camel_case(P);
                    $class = '\TMCms\Modules\\' . $real_class . '\Cms' . $real_class;
                }
            }
        }

        // Try autoload PSR-0 or PSR-4
        if (!class_exists($class)) {
            $class = 'TMCms\Modules\\' . $real_class . '\Cms' . $real_class;
        }

        // Try to find the right directory of requested class
        if (!class_exists($class)) {
            $class_name = 'Cms' . $real_class;

            $directory_iterator = new RecursiveDirectoryIterator(DIR_MODULES);
            $iterator = new RecursiveIteratorIterator($directory_iterator);

            foreach ($iterator as $file) {
                if ($file->getFilename() == $class_name . '.php') {
                    $module_path = $file->getPathInfo()->getPathName();
                    $module_name = $file->getPathInfo()->getFilename();

                    $module_directory_iterator = new RecursiveDirectoryIterator($module_path);
                    $module_iterator = new RecursiveIteratorIterator($module_directory_iterator);

                    foreach ($module_iterator as $module_file) {
                        $module_file_directory = $module_file->getPathInfo()->getFilename();
                        $module_file_name = $module_file->getFileName();

                        if (!in_array($module_file_name, ['.', '..']) and in_array($module_file_directory, [$module_name, 'Entity'])) {
                            require_once $module_file->getPathName();
                        }
                    }

                    $class = implode('\\', ['\TMCms', 'Modules', $module_name, $class_name]);

                    break;
                }
            }
        }

        // Still no class
        if (!class_exists($class)) {
            dump('Requested class "' . $class . '" not found');
        }

        // Check existence of requested method
        if (class_exists($class)) {
            $call_object = true;

            // Check requested method exists or set default
            if (method_exists($class, P_DO)) {
                $method = P_DO;
            } else {
                $method = '_default';
            }

            // Final check we have anything to run
            if (!method_exists($class, $method)) {
                dump('Method "' . $method . '" not found in class "' . $class . '"');
            }
        }

        // Check user's permission
        if (!Users::getInstance()->checkAccess(P, $method)) {
            error('You do not have permissions to access this page ("' . P . ' - ' . $method . '")');
            die;
        }

        // Call required method
        if ($call_object) {
            $obj = new $class;
            $obj->{$method}();
        } else {
            call_user_func([$class, $method]);
        }

        $this->content = ob_get_clean();
    }
}