<?php
declare(strict_types=1);

namespace TMCms\Network;

use const IP_LONG;
use RuntimeException;
use function strtolower;
use TMCms\Admin\Tools\Entity\MaxMindGeoIpCountryEntityRepository;
use TMCms\Admin\Tools\Entity\MaxMindGeoIpRangeEntity;
use TMCms\Admin\Tools\Entity\MaxMindGeoIpRangeEntityRepository;
use TMCms\Cache\Cacher;
use TMCms\Files\FileSystem;

defined('INC') or exit;

/**
 * Class MaxMindGeoIP
 */
class MaxMindGeoIP
{
    /**
     * @param string $ip_long
     *
     * @return string
     */
    public static function getCountryCodeByLongIp($ip_long = IP_LONG): string
    {
        $country_code = '';

        // Get country by range
        $ranges = new MaxMindGeoIpRangeEntityRepository();
        $ranges->enableUsingCache(3600);
        $ranges->addSimpleSelectFields(['country_code']);
        $ranges->addWhereFieldIsHigherOrEqual('start', $ip_long);
        $ranges->addWhereFieldIsLowerOrEqual('end', $ip_long);
        $range = $ranges->getFirstObjectFromCollection();

        /** @var MaxMindGeoIpRangeEntity $range */
        if ($range) {
            $country_code = $range->getCode();
        }

        return strtolower($country_code);
    }

    /**
     * Updates values in DB from fetched file
     */
    public static function updateDatabase()
    {
        FileSystem::mkDir(DIR_TEMP . 'GeoIPCountryCSV');

        $file_to_extract = DIR_TEMP . 'GeoIPCountryCSV/GeoIPCountryCSV.zip';
        if (!file_exists($file_to_extract) || filemtime($file_to_extract) < NOW - 86400) {
            $f = @file_get_contents('http://geolite.maxmind.com/download/geoip/database/GeoIPCountryCSV.zip');
            if ($f === false) {
                throw new RuntimeException('Can not connect to MaxMind');
            }

            file_put_contents(DIR_TEMP . 'GeoIPCountryCSV/GeoIPCountryCSV.zip', $f);
            unset($f);
        }


        // Extract file
        $zip = new \ZipArchive;
        $zip->open($file_to_extract);
        $zip->extractTo(DIR_TEMP . 'GeoIPCountryCSV/');
        $zip->close();

        $ranges = new MaxMindGeoIpRangeEntityRepository;
        $countries = new MaxMindGeoIpCountryEntityRepository();

        // Create new tables
        $ranges->ensureDbTableExists();
        $countries->ensureDbTableExists();

        $ranges->deleteObjectCollection();
        $countries->deleteObjectCollection();

        $countries_sql = $ranges_sql = [];
        $fh = fopen(DIR_TEMP . 'GeoIPCountryCSV/GeoIPCountryWhois.csv', 'rb');

        while (($q = fgetcsv($fh, 4096, ',')) !== false) {
            if (!isset($q[4])) {
                continue;
            }

            $ranges_sql[] = '(NULL, "' . sql_prepare($q[4]) . '","' . sql_prepare($q[2]) . '","' . sql_prepare($q[3]) . '")';
            if (!isset($countries_sql[$q[4]])) {
                $countries_sql[$q[4]] = '(NULL, "' . $q[4] . '","' . sql_prepare($q[5]) . '")';
            }
        }
        fclose($fh);

        // Clean SQL works much faster and we now what values are
        q('INSERT INTO `cms_maxmind_geoip_c` VALUES ' . implode(',', $countries_sql), false);

        $q = [];
        $ql = 0;
        $so = count($ranges_sql);

        for ($i = 0; $i < $so; ++$i) {
            $l = strlen($ranges_sql[$i]);
            if ($ql + $l > 983040) {
                q('INSERT INTO `cms_maxmind_geoip_r` VALUES ' . implode(',', $q));
                $q = [];
                $ql = 0;
            }
            $q[] = $ranges_sql[$i];
            $ql += $l;
        }

        // If have anything to save
        if ($q) {
            q('INSERT INTO `cms_maxmind_geoip_r` VALUES ' . implode(',', $q));
        }

        q('OPTIMIZE TABLE `cms_maxmind_geoip_r`,`cms_maxmind_geoip_c`');

        Cacher::getInstance()->getDefaultCacher()->set('geo_ip_lmt', NOW);
    }
}
