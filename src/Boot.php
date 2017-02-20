<?php

use TMCms\Admin\AdminTranslations;
use TMCms\DB\SQL;
use TMCms\Files\Finder;
use TMCms\Log\Errors;
use TMCms\Routing\Structure;
use TMCms\Templates\Components;

defined('INC') or exit;

// Constants

$root_path_length = strlen(DIR_BASE);

// Root in browser
define('DIR_BASE_URL', '/' . substr(DIR_BASE, $root_path_length)); // Can be used to run App from under folder

// Backend url
define('DIR_CMS', DIR_BASE . 'cms/');
define('DIR_CMS_URL', '/' . substr(DIR_CMS, $root_path_length));

// File cache
define('DIR_CACHE', DIR_BASE . 'cache/');
define('DIR_CACHE_URL', '/' . substr(DIR_CACHE, $root_path_length));

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
define('DIR_FRONT_AJAX', DIR_FRONT . 'ajax/');
define('DIR_FRONT_AJAX_URL', '/' . substr(DIR_FRONT_AJAX, $root_path_length));

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
ignore_user_abort(1);

ini_set('display_errors', true);
ini_set('display_startup_errors', true);
ini_set('register_globals', '0');
ini_set('magic_quotes_gpc', '0');
ini_set('allow_url_fopen', '0');
ini_set('mysql.trace_mode', '0');

// Disable showing PHPSESSID in URL
if (session_status() != PHP_SESSION_ACTIVE) {
    ini_set('session.use_trans_sid', false);
}
ini_set('session.use_only_cookies', true); // Use Cookies only in headers
ini_set('session.entropy_file', '/dev/urandom');
ini_set('session.entropy_length', 32);
ini_set('session.hash_bits_per_character', 6);
ini_set('session.cookie_httponly', false); // We may need Cookies in JavaScript

// Global encoding
mb_internal_encoding('UTF-8');

// Always begin session
if (session_status() != PHP_SESSION_ACTIVE && !headers_sent()) {
    session_start();
}

// Every time we start Session - give it a unique name for security
if (empty($_SESSION['__session_name_validated'])) {
    $random_cookie_name = function () {
        $length = rand(16, 32);
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
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
if (!isset($_SERVER['REMOTE_ADDR']) || !preg_match('/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/', $_SERVER['REMOTE_ADDR'])) {
    $_SERVER['REMOTE_ADDR'] = '0.0.0.0';
}

// Check for legal URL
define('SELF', isset($_SERVER['REDIRECT_URL']) ? $_SERVER['REDIRECT_URL'] : $_SERVER['REQUEST_URI']);

// Deny incorrect urls
if (strlen(SELF) > 2000 || strpos(SELF, 'eval(') !== false || stripos(SELF, 'CONCAT') !== false || stripos(SELF, 'UNION+SELECT') !== false || stripos(SELF, 'base64') !== false) {
    header("HTTP/1.1 414 Request-URI Too Long");
    header("Status: 414 Request-URI Too Long");
    header("Connection: Close");
    exit('Wrong URL');
}

// Disabling magic quotes in case we have old server software
if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
    $process = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
    while (list($key, $val) = each($process)) {
        foreach ($val as $k => $v) {
            unset($process[$key][$k]);
            if (is_array($v)) {
                $process[$key][stripslashes($k)] = $v;
                $process[] = &$process[$key][stripslashes($k)];
            } else {
                $process[$key][stripslashes($k)] = stripslashes($v);
            }
        }
    }
    unset($process, $key, $val, $k, $v);
}

/* Constants */
define('IS_CLI', php_sapi_name() === 'cli');
define('HOST', mb_strtolower(trim($_SERVER['HTTP_HOST'])));
define('REF', isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : NULL);
define('QUERY', isset($_SERVER['REDIRECT_QUERY_STRING']) ? $_SERVER['REDIRECT_QUERY_STRING'] : $_SERVER['QUERY_STRING']);
define('SELF_WO_QUERY', rtrim(QUERY ? substr(SELF, 0, -strlen(QUERY) - 1) : SELF, '?'));
define('IP', $_SERVER['REMOTE_ADDR']);
define('IP_LONG', sprintf('%u', ip2long(IP)));
define('USER_AGENT', $_SERVER['HTTP_USER_AGENT']);
define('SERVER_IP', $_SERVER['SERVER_ADDR']);
define('NOW', $_SERVER['REQUEST_TIME']);
define('VISITOR_HASH', md5(IP . ':' . USER_AGENT));
define('REQUEST_METHOD', isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET');

// Website base url with protocol
$protocol = 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 's' : '');
define('BASE_URL', $protocol . '://' . HOST);
define('CFG_PROTOCOL', $protocol);

// This may be already defined in boot file specially for project
if (!defined('CFG_DOMAIN')) {
    define('CFG_DOMAIN', HOST);
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
define('REF_SE_KEYWORD_MIN_MATCH', 70); // Minimum match to search query from search engines to trigger quicklinks

// PHP_OS can be already set by environment
if (!defined('PHP_OS')) {
    define('PHP_OS', "Linux");
}
if (!defined('CFG_DB_SERVER')) {
    define('CFG_DB_SERVER', 'localhost');
}
if (!defined('CFG_AJAX_ROUTE')) {
    define('CFG_AJAX_ROUTE', 'ajax');
}
// Default git branch from which CMS is updated
if (!defined('CFG_GIT_BRANCH')) {
    define('CFG_GIT_BRANCH', 'master');
}

/* CMS */
define('CMS_VERSION', '16.12');
define('CMS_NAME', 'The Modern CMS');
define('CMS_DEVELOPERS', 'Vadims Petrusevs, neTpyceB, Bogdans Laidiņš, Armands Grundmanis');
define('CMS_OWNER_COMPANY', 'SIA DEVP');
define('CMS_SUPPORT_EMAIL', 'info@devp.eu'); // Support e-mail for errors, etc.
define('CMS_SITE', 'http://devp.eu/');
define('IS_AJAX_REQUEST', (int)isset($_REQUEST['ajax']) || stripos(SELF, '_ajax') === 0 || stripos(SELF, '/'. CFG_AJAX_ROUTE .'/') !== false || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'));

// Dates
define('Y', date('Y'));

/* Helper functions */

/**
 * @param string $str
 * @param bool $used_in_like
 * @return mixed|string
 */
function sql_prepare($str, $used_in_like = false)
{
    return SQL::sql_prepare($str, $used_in_like);
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

/**
 * @param mixed $data
 * @param bool $serialize
 * @param bool $clean
 */
function dump($data, $serialize = false, $clean = true)
{
    Errors::dump($data, $serialize, $clean);
}

/**
 * @param string $str
 */
function error($str)
{
    Errors::error($str);
}

/**
 * @param string $k
 * @param bool|mixed $lng
 * @param array $replaces
 * @param string $default
 * @return mixed|string|void
 */
function w($k, $lng = LNG, $replaces = [], $default = '', $no_cache = false)
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
function __($key)
{
    return AdminTranslations::getInstance()->getActualValueByKey($key);
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

    $go = $go != '' ? $go : '/';

    if ($additional_params) {
        if (stripos($go, '?') === false) {
            $go .= '?';
        } else {
            $go .= '&';
        }
        $go .= http_build_query($additional_params);
    }

    if (ob_get_contents()) ob_clean();
    if (!isset($_GET['ajax'])) {
        @header('Location: ' . $go, true, 301);
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

// Add assets and all other search folders for CMS
$length_of_include_path = $root_path_length - 1;
Finder::getInstance()
    ->addAssetsSearchPath(substr(__DIR__, $length_of_include_path) . '/assets/')
    ->addAssetsSearchPath(DIR_CMS_SCRIPTS)
    ->addAjaxSearchPath(substr(__DIR__, $length_of_include_path) . '/assets/ajax/')
    ->addPluginsSearchPath(substr(__DIR__, $length_of_include_path) . '/assets/cms_plugins/')
    ->addServicesSearchPath(substr(__DIR__, $length_of_include_path) . '/assets/services/')
    ->addTranslationsSearchPath(substr(__DIR__, $length_of_include_path) . '/assets/translations/')
;

unset($root_path_length, $length_of_include_path);