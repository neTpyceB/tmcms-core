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
}
