<?php
declare(strict_types=1);

use TMCms\Admin\AdminTranslations;
use TMCms\Config\Settings;
use TMCms\DB\SQL;
use TMCms\Files\FileSystem;
use TMCms\Files\Finder;
use TMCms\Log\Errors;
use TMCms\Routing\MVC;
use TMCms\Routing\Structure;
use TMCms\Templates\Components;

defined('INC') or exit;

// Constants

$root_path_length = strlen(DIR_BASE);

// Root in browser
define('DIR_BASE_URL', '/' . substr(DIR_BASE, $root_path_length)); // Can be used to run App from under folder

// Backend url
if (!defined('DIR_CMS')) {
    define('DIR_CMS', DIR_BASE . 'cms');
}
define('DIR_CMS_URL', '/' . substr(DIR_CMS, $root_path_length));

// File cache
define('DIR_CACHE', DIR_BASE . 'cache/');
define('DIR_CACHE_URL', '/' . substr(DIR_CACHE, $root_path_length));

// File cache
define('DIR_IMAGE_CACHE', DIR_BASE . 'cache_img/');
define('DIR_IMAGE_CACHE_URL', '/' . substr(DIR_IMAGE_CACHE, $root_path_length));

// Configs
define('DIR_CONFIGS', DIR_BASE . 'configs/');

// Backend images
define('DIR_CMS_IMAGES', substr(__DIR__, $root_path_length - 1) . '/assets/images/');
define('DIR_CMS_IMAGES_URL', DIR_CMS_IMAGES);

// Backend scripts
define('DIR_CMS_SCRIPTS', substr(__DIR__, $root_path_length - 1) . '/assets/scripts/');
define('DIR_CMS_SCRIPTS_URL', DIR_CMS_SCRIPTS);

// Translations
define('DIR_CMS_TRANSLATIONS', substr(__DIR__, $root_path_length - 1) . '/assets/translations/');

// Project root
define('DIR_FRONT', DIR_BASE . 'project/');

// Ajax and API handlers
define('DIR_FRONT_API', DIR_FRONT . 'api/');
define('DIR_FRONT_API_URL', '/' . substr(DIR_FRONT_API, $root_path_length));

// Logs
define('DIR_FRONT_LOGS', DIR_FRONT . 'logs/');

// Project controllers
define('DIR_FRONT_CONTROLLERS', DIR_FRONT . 'controllers/');

// Project plugins
define('DIR_FRONT_PLUGINS', DIR_FRONT . 'plugins/');
define('DIR_FRONT_PLUGINS_URL', '/' . substr(DIR_FRONT_PLUGINS, $root_path_length));

// Project services
define('DIR_FRONT_SERVICES', DIR_FRONT . 'services/');
define('DIR_FRONT_SERVICES_URL', '/' . substr(DIR_FRONT_SERVICES, $root_path_length));

// Project templates
define('DIR_FRONT_TEMPLATES', DIR_FRONT . 'templates/');
define('DIR_FRONT_TEMPLATES_URL', '/' . substr(DIR_FRONT_TEMPLATES, $root_path_length));

// Project views
define('DIR_FRONT_VIEWS', DIR_FRONT . 'views/');

// Public folder for browser
define('DIR_PUBLIC', DIR_BASE . 'public/');
define('DIR_PUBLIC_URL', '/' . substr(DIR_PUBLIC, $root_path_length));

// Public project assets with css and js files
define('DIR_ASSETS', DIR_PUBLIC . 'assets/');
define('DIR_ASSETS_URL', '/' . substr(DIR_ASSETS, $root_path_length));

// Public project images
define('DIR_IMAGES', DIR_ASSETS . 'images/');
define('DIR_IMAGES_URL', '/' . substr(DIR_IMAGES, $root_path_length));

// Project custom modules
define('DIR_MODULES', DIR_FRONT . 'modules/');

// Project database scheme migrations
define('DIR_MIGRATIONS', DIR_FRONT . 'migrations/');
define('DIR_MIGRATIONS_URL', '/' . substr(DIR_MIGRATIONS, $root_path_length));

// Temporal storage folder
define('DIR_TEMP', DIR_BASE . 'temp/');
define('DIR_TEMP_URL', '/' . substr(DIR_TEMP, $root_path_length));

// Projects unit and coverage tests
define('DIR_TESTS', DIR_BASE . 'tests/');

// Send first headers
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff'); // Allow only named scripts (type=«text/javascript», type=«text/css»)
    header('X-Frame-Options: DENY');
    header('Strict-Transport-Security: max-age=expireTime'); // Enable SSL and use only it
    header_remove('X-Powered-By'); // Remove PHP version
}

// Prevent script abortion
ob_implicit_flush(0);
ignore_user_abort(true);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
ini_set('allow_url_fopen', '0');
ini_set('mysql.trace_mode', '0');

// Disable showing PHPSESSID in URL
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_trans_sid', '0');
}
ini_set('session.use_only_cookies', '1'); // Use Cookies only in headers
ini_set('session.entropy_file', '/dev/urandom');
ini_set('session.entropy_length', '32');
ini_set('session.hash_bits_per_character', '6');
ini_set('session.cookie_httponly', '0'); // We may need Cookies in JavaScript

// Global encoding
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
mb_http_input('UTF-8');
mb_language('uni');
mb_regex_encoding('UTF-8');

// Always begin session
if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
    session_start();
}

// Every time we start Session - give it a unique name for security
if (empty($_SESSION['__session_name_validated'])) {
    $random_cookie_name = function () {
        $length = random_int(16, 32);
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, strlen($characters) - 1)];
        }
        return $randomString;
    };
    ini_set('session.name', $random_cookie_name());
    $_SESSION['__session_name_validated'] = 1;
}

// Http auth for PHP-CGI mode
if (isset($_SERVER['HTTP_AUTHORIZATION']) && !isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
    $tmp = explode(':', base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
    if (isset($tmp[1])) {
        list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':', base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
    }
}

// Ini with required keys
if (!isset($_SERVER['SERVER_ADDR'])) {
    $_SERVER['SERVER_ADDR'] = '127.0.0.1';
}
if (!isset($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST'] = '';
}
if (!isset($_SERVER['REQUEST_TIME'])) {
    $_SERVER['REQUEST_TIME'] = time();
}
if (!isset($_SERVER['HTTP_REFERER'])) {
    $_SERVER['HTTP_REFERER'] = '';
}
if (!isset($_SERVER['HTTP_ACCEPT_ENCODING'])) {
    $_SERVER['HTTP_ACCEPT_ENCODING'] = '';
}
if (!isset($_SERVER['QUERY_STRING'])) {
    $_SERVER['QUERY_STRING'] = '';
}
if (!isset($_SERVER['HTTP_USER_AGENT'])) {
    $_SERVER['HTTP_USER_AGENT'] = '';
}
if (!isset($_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = '/';
}
if (!isset($_SERVER['REMOTE_ADDR']) || !preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $_SERVER['REMOTE_ADDR'])) {
    $_SERVER['REMOTE_ADDR'] = '0.0.0.0';
}

// Check for legal URL
define('SELF', $_SERVER['REDIRECT_URL'] ?? $_SERVER['REQUEST_URI']);

// Deny incorrect urls
if (strlen(SELF) > 2000 || strpos(SELF, 'eval(') !== false || stripos(SELF, 'CONCAT') !== false || stripos(SELF, 'UNION+SELECT') !== false || stripos(SELF, 'base64') !== false) {
    header('HTTP/1.1 414 Request-URI Too Long');
    header('Status: 414 Request-URI Too Long');
    header('Connection: Close');
    exit('Wrong URL');
}

/* Constants */
define('IS_CLI', PHP_SAPI === 'cli');
define('HOST', mb_strtolower(trim($_SERVER['HTTP_HOST'])));
define('REF', $_SERVER['HTTP_REFERER'] ?? NULL);
define('QUERY', $_SERVER['REDIRECT_QUERY_STRING'] ?? $_SERVER['QUERY_STRING']);
define('SELF_WO_QUERY', rtrim((string)(QUERY ? substr(SELF, 0, -strlen(QUERY) - 1) : SELF), '?'));
define('IP', $_SERVER['REMOTE_ADDR']);
define('IP_LONG', sprintf('%u', ip2long(IP)));
define('USER_AGENT', $_SERVER['HTTP_USER_AGENT']);
define('SERVER_IP', $_SERVER['SERVER_ADDR']);
define('NOW', $_SERVER['REQUEST_TIME']);
define('VISITOR_HASH', md5(IP . ':' . USER_AGENT));
define('REQUEST_METHOD', $_SERVER['REQUEST_METHOD'] ?? 'GET');

// Website base url with protocol
define('IS_SSL', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']);
define('CFG_PROTOCOL', 'http' . (IS_SSL ? 's' : ''));
define('BASE_URL', CFG_PROTOCOL . '://' . HOST);

// This may be already defined in boot file specially for project
if (!defined('CFG_DOMAIN')) { // Host to connect to
    define('CFG_DOMAIN', HOST);
}
if (!defined('CFG_SESSION_KEEP_ALIVE_SECONDS')) { // Log-out after that time
    define('CFG_SESSION_KEEP_ALIVE_SECONDS', 1200); // 20 minutes
}

date_default_timezone_set('Europe/Riga');

// Settings
define('CFG_DB_CONNECT_DELAY', 500000);
define('CFG_DB_MAX_CONNECT_ATTEMPTS', 3);
define('CFG_CMS_DATE_FORMAT', 'Y-m-d');
define('CFG_CMS_DATETIME_FORMAT', 'Y-m-d H:i');
define('CFG_MYSQL_DATETIME_FORMAT', 'YYYY-MM-DD HH:MM:SS');
define('CFG_MIN_PHP_VERSION_REQUIRED', '5.5');
define('CFG_MAIL_ERRORS', 1); // Send or not errors
define('CFG_DEFAULT_FILE_PERMISSIONS', 0777);
define('CFG_DEFAULT_DIR_PERMISSIONS', 0777);
define('REF_SE_KEYWORD_MIN_MATCH', 70); // Minimum match to search query from search engines to trigger aliases

// PHP_OS can be already set by environment
if (!defined('PHP_OS')) {
    $os = strtoupper(PHP_OS);
    if (strpos($os, 'WIN') === 0) {
        $os = 'Windows';
    } elseif ($os === 'LINUX' || $os === 'FREEBSD' || $os === 'DARWIN') {
        $os = 'Linux';
    } else {
        $os = 'Other';
    }
    define('PHP_OS', $os);
    unset($os);
}
if (!defined('CFG_DB_SERVER')) {
    define('CFG_DB_SERVER', 'localhost');
}
if (!defined('CFG_API_ROUTE')) {
    define('CFG_API_ROUTE', 'api');
}
// Default git branch from which CMS is updated
if (!defined('CFG_GIT_BRANCH')) {
    define('CFG_GIT_BRANCH', 'master');
}

/* CMS */
define('CMS_VERSION', '17.07');
define('CMS_NAME', 'The Modern CMS');
define('CMS_OWNER_COMPANY', 'SIA DEVP');
if (!defined('CMS_SUPPORT_EMAIL')) {
    define('CMS_SUPPORT_EMAIL', 'support@devp.eu'); // Support e-mail for errors, etc.
}
if (!defined('CMS_SITE')) {
    define('CMS_SITE', 'http://devp.eu/');
}
define('IS_AJAX_REQUEST', (int)isset($_REQUEST['ajax']) || stripos(SELF, '_ajax') === 0 || stripos(SELF, '/' . CFG_API_ROUTE . '/') !== false || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'));

// Dates
define('Y', date('Y'));

//=== Helper functions

/**
 * @param string $str
 * @param bool $used_in_like
 * @return mixed|string
 */
function sql_prepare($str, $used_in_like = false)
{
    return SQL::sql_prepare((string)$str, $used_in_like);
}

/**
 * @param string $q
 * @param bool $protect
 * @param bool $returnID
 * @return PDOStatement | string
 */
function q($q, $protect = false, $returnID = false)
{
    return SQL::getInstance()->sql_query($q, $protect, $returnID);
}

/**
 * @param string $q
 * @param bool $protect
 * @return array
 */
function q_assoc($q, $protect = true)
{
    return SQL::q_assoc($q, $protect);
}

/**
 * @param string $q
 * @param bool $protect
 * @return Iterator
 */
function q_assoc_iterator($q, $protect = true)
{
    return SQL::q_assoc_iterator($q, $protect);
}

/**
 * @param string $q
 * @return array | bool
 */
function q_assoc_row($q)
{
    return SQL::q_assoc_row($q);
}

/**
 * @param string $q
 * @return array
 */
function q_assoc_id($q)
{
    return SQL::q_assoc_id($q);
}

/**
 * @param string $q
 * @param bool $protect
 * @return array
 */
function q_pairs($q, $protect = true)
{
    return SQL::getInstance()->q_pairs($q, $protect);
}

/**
 * @param string $tbl
 * @param string $where
 * @return bool
 */
function q_check($tbl, $where = NULL)
{
    return SQL::q_check($tbl, $where);
}

/**
 * @param string $q
 * @return string
 */
function q_value($q)
{
    return SQL::q_value($q);
}

function q_column($q, $column=0, $protected=true)
{
    return SQL::q_column($q, $column, $protected);
}

/**
 * @param mixed $data
 * @param bool $clean
 */
function dump($data, $clean = true)
{
    Errors::dump($data, $clean);
}

/**
 * @param string $str
 */
function error(string $str)
{
    Errors::error($str);
}

/**
 * @param string $k
 * @param bool|mixed $lng
 * @param array $replaces
 * @param string $default
 * @param bool $no_cache
 * @return string
 */
function w($k, $lng = LNG, array $replaces = [], $default = '', $no_cache = false)
{
    return Structure::getWord($k, $lng, $replaces, $default, $no_cache);
}

/**
 * @param string $component
 * @param bool $class
 * @return null
 */
function c($component, $class = false)
{
    return Components::get($component, $class);
}

/**
 * Function getText - translations for Admin panel
 * @param $key
 * @return string
 */
function __(string $key)
{
    return AdminTranslations::getInstance()->getActualValueByKey($key);
}

function render($class, $method = 'index')
{
    $mvc = new MVC();
    $mvc->setMethod($method);

    $controller_class = ucfirst($class) . 'Controller';
    require_once DIR_FRONT_CONTROLLERS . $class . '.php';
    $mvc->setController($controller_class);

    $view_class = ucfirst($class) . 'View';
    require_once DIR_FRONT_VIEWS . $class . '.php';
    $mvc->setView($view_class);

    return $mvc->outputController()->outputView();
}

/**
 * @param string $go URL
 * @param array $additional_params to add or change in URL
 * @param bool $skip_auto_redirect do not go to ref from forms
 */
function go($go, array $additional_params = [], $skip_auto_redirect = false)
{
    if (isset($_POST['cms_go_after_submit']) && !$skip_auto_redirect) {
        $go = explode('&', $go) + explode('&', $_POST['cms_go_after_submit']);
        $go = implode('&', $go);
    }

    $go = $go !== '' ? $go : '/';

    if ($additional_params) {
        if (strpos($go, '?') === false) {
            $go .= '?';
        } else {
            $go .= '&';
        }
        $go .= http_build_query($additional_params);
    }

    if (ob_get_contents()) {
        ob_clean();
    }
    if (!isset($_GET['ajax'])) {
        header('Location: ' . $go, true, 301);
    }
    exit;
}

/**
 * Goes back in browser
 * @param array $additional_params to add or change in URL
 */
function back(array $additional_params = [])
{
    go(REF, $additional_params);
}

// Init Settings
Settings::getInstance()->init();

// Add assets and all other search folders for CMS
$length_of_include_path = $root_path_length - 1;
Finder::getInstance()
    ->addAssetsSearchPath(substr(__DIR__, $length_of_include_path) . '/assets/')
    ->addAssetsSearchPath(DIR_CMS_SCRIPTS)
    ->addApiSearchPath(substr(__DIR__, $length_of_include_path) . '/assets/api/')
    ->addPluginsSearchPath(substr(__DIR__, $length_of_include_path) . '/assets/cms_plugins/')
    ->addServicesSearchPath(substr(__DIR__, $length_of_include_path) . '/assets/services/')
    ->addTranslationsSearchPath(substr(__DIR__, $length_of_include_path) . '/assets/translations/')
;
unset($root_path_length, $length_of_include_path);

//=== Check middleware is required to register
// Backend middleware
require_once __DIR__ . '/assets/middleware.php';

// Frontend middleware
$middleware_runner_path = DIR_FRONT . 'middleware.php';
if (file_exists($middleware_runner_path)) {
    require_once $middleware_runner_path;
}

/**
 * Run every autoload file from every module
 */
function runAutoloadFiles()
{
    FileSystem::mkDir(DIR_MODULES);
    foreach (scandir(DIR_MODULES, SCANDIR_SORT_NONE) as $module_dir) {
        // Skip hidden
        if ($module_dir[0] === '.') {
            continue;
        }

        $autoload_file = DIR_MODULES . $module_dir . '/autoload.php';

        if (is_file($autoload_file)) {
            require_once $autoload_file;
        }
    }
}