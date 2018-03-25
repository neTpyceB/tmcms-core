<?php
declare(strict_types=1);

namespace TMCms\Config;

/**
 * Class Settings
 */
class Constants
{
    // Admin defaults
    const ADMIN_LANGUAGE_DEFAULT_SHORT = 'en';
    const ADMIN_LANGUAGE_DEFAULT_FULL = 'English';
    const ADMIN_CMS_NAME = 'The Modern CMS';
    const ADMIN_CMS_OWNER_COMPANY = 'SIA DEVP';
    const ADMIN_CMS_VERSION = '18.03';

    // Database
    const DB_CONNECT_DELAY = 500000;
    const DB_CONNECT_MAX_ATTEMPTS = 3;

    // Filesystem
    const FILESYSTEM_FILE_PERMISSIONS_DEFAULT = 0777;
    const FILESYSTEM_DIR_PERMISSIONS_DEFAULT = 0777;

    // Formatting
    const FORMAT_CMS_DATE_FORMAT = 'Y-m-d';
    const FORMAT_CMS_DATETIME_FORMAT = 'Y-m-d H:i';
    const FORMAT_DATABASE_MYSQL_DATETIME_FORMAT = 'YYYY-MM-DD HH:MM:SS';

    // SEO
    // Minimum match to search query from search engines to trigger aliases
    const SEO_REFERRER_SEARCH_ENGINE_KEYWORD_MIN_MATCH_PERCENT = 70;

    // Time periods
    const PERIOD_MINUTES_IN_SECONDS_10 = 600;
    const PERIOD_MINUTES_IN_SECONDS_20 = 1200;
    const PERIOD_DAY_IN_SECONDS = 86400;

    public static function initVariableConstants()
    {
        \define('DIR_ROOT_PATH_LENGTH', \strlen(DIR_BASE));

        // Root in browser
        \define('DIR_BASE_URL', '/' . substr(DIR_BASE, DIR_ROOT_PATH_LENGTH)); // Can be used to run App from under folder

        // Backend url
        if (!\defined('DIR_CMS')) {
            \define('DIR_CMS', DIR_BASE . 'cms');
        }
        \define('DIR_CMS_URL', '/' . substr(DIR_CMS, DIR_ROOT_PATH_LENGTH));

        // File cache
        \define('DIR_CACHE', DIR_BASE . 'cache/');
        \define('DIR_CACHE_URL', '/' . substr(DIR_CACHE, DIR_ROOT_PATH_LENGTH));

        // File cache
        \define('DIR_IMAGE_CACHE', DIR_BASE . 'cache_img/');
        \define('DIR_IMAGE_CACHE_URL', '/' . substr(DIR_IMAGE_CACHE, DIR_ROOT_PATH_LENGTH));

        // Configs
        \define('DIR_CONFIGS', DIR_BASE . 'configs/');

        // Vendor current folder
        \define('DIR_CMS_VENDOR', __DIR__ . '/..');

        // Backend images
        \define('DIR_CMS_IMAGES', substr(DIR_CMS_VENDOR, DIR_ROOT_PATH_LENGTH - 1) . '/assets/images/');
        \define('DIR_CMS_IMAGES_URL', DIR_CMS_IMAGES);

        // Backend scripts
        \define('DIR_CMS_SCRIPTS', substr(DIR_CMS_VENDOR, DIR_ROOT_PATH_LENGTH - 1) . '/assets/scripts/');
        \define('DIR_CMS_SCRIPTS_URL', DIR_CMS_SCRIPTS);

        // Translations
        \define('DIR_CMS_TRANSLATIONS', substr(DIR_CMS_VENDOR, DIR_ROOT_PATH_LENGTH - 1) . '/assets/translations/');

        // Project root
        \define('DIR_FRONT', DIR_BASE . 'project/');

        // Ajax and API handlers
        \define('DIR_FRONT_API', DIR_FRONT . 'api/');
        \define('DIR_FRONT_API_URL', '/' . substr(DIR_FRONT_API, DIR_ROOT_PATH_LENGTH));

        // Logs
        \define('DIR_FRONT_LOGS', DIR_FRONT . 'logs/');

        // Project controllers
        \define('DIR_FRONT_CONTROLLERS', DIR_FRONT . 'controllers/');

        // Project plugins
        \define('DIR_FRONT_PLUGINS', DIR_FRONT . 'plugins/');
        \define('DIR_FRONT_PLUGINS_URL', '/' . substr(DIR_FRONT_PLUGINS, DIR_ROOT_PATH_LENGTH));

        // Project services
        \define('DIR_FRONT_SERVICES', DIR_FRONT . 'services/');
        \define('DIR_FRONT_SERVICES_URL', '/' . substr(DIR_FRONT_SERVICES, DIR_ROOT_PATH_LENGTH));

        // Project templates
        \define('DIR_FRONT_TEMPLATES', DIR_FRONT . 'templates/');
        \define('DIR_FRONT_TEMPLATES_URL', '/' . substr(DIR_FRONT_TEMPLATES, DIR_ROOT_PATH_LENGTH));

        // Project views
        \define('DIR_FRONT_VIEWS', DIR_FRONT . 'views/');

        // Public folder for browser
        \define('DIR_PUBLIC', DIR_BASE . 'public/');
        \define('DIR_PUBLIC_URL', '/' . substr(DIR_PUBLIC, DIR_ROOT_PATH_LENGTH));

        // Public project assets with css and js files
        \define('DIR_ASSETS', DIR_PUBLIC . 'assets/');
        \define('DIR_ASSETS_URL', '/' . substr(DIR_ASSETS, DIR_ROOT_PATH_LENGTH));

        // Public project images
        \define('DIR_IMAGES', DIR_ASSETS . 'images/');
        \define('DIR_IMAGES_URL', '/' . substr(DIR_IMAGES, DIR_ROOT_PATH_LENGTH));

        // Project custom modules
        \define('DIR_MODULES', DIR_FRONT . 'modules/');

        // Project database scheme migrations
        \define('DIR_MIGRATIONS', DIR_FRONT . 'migrations/');
        \define('DIR_MIGRATIONS_URL', '/' . substr(DIR_MIGRATIONS, DIR_ROOT_PATH_LENGTH));

        // Temporal storage folder
        \define('DIR_TEMP', DIR_BASE . 'temp/');
        \define('DIR_TEMP_URL', '/' . substr(DIR_TEMP, DIR_ROOT_PATH_LENGTH));

        // Projects unit and coverage tests
        \define('DIR_TESTS', DIR_BASE . 'tests/');

        // If terminal is used
        \define('IS_CLI', PHP_SAPI === 'cli');

        // PHP_OS can be already set by environment
        if (!\defined('PHP_OS')) {
            $os = strtoupper(PHP_OS);
            if (strpos($os, 'WIN') === 0) {
                $os = 'Windows';
            } elseif ($os === 'LINUX' || $os === 'FREEBSD' || $os === 'DARWIN') {
                $os = 'Linux';
            } else {
                $os = 'Other';
            }
            \define('PHP_OS', $os);
        }
    }
}
