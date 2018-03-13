<?php

namespace TMCms\Log;

use TMCms\Cache\Cacher;
use TMCms\Strings\UID;

defined('INC') or exit;

/**
 * Class Stats
 */
class Stats
{
    /**
     * @var array
     */
    private static $db_queries = array();
    /**
     * @var array
     */
    private static $exec_times = array();

    /**
     * @param array $times
     */
    public static function setDbTimes(array $times)
    {
        self::$exec_times = $times;
    }

    /**
     * @param array $query
     */
    public static function addQuery(array $query)
    {
        if (isset($query['backtrace'])) {
            foreach ($query['backtrace'] as & $v) {
                unset($v['object']);
            }
        }

        self::$db_queries[] = $query;
    }

    public static function getView()
    {
        global $start_microtime;
        $tt = microtime(1) - $start_microtime;
        $db_tt = 0;

        foreach (self::$db_queries as $db_query) {
            $db_tt += $db_query['time'];
        }
        $so_queris = count(self::$db_queries);

        $base_dir_l = strlen(rtrim(DIR_BASE, '/'));
        $inc_f = array();

        foreach (get_included_files() as $v) {
            $inc_f[] = substr($v, $base_dir_l);
        }

        $def_c = get_defined_constants(true);
        $def_c = $def_c['user'];
        $def_c = array_filter($def_c, function ($value) {
            return is_scalar($value);
        });

        if (isset($def_c['CFG_DB_LOGIN'])) {
            $def_c['CFG_DB_LOGIN'] = '***';
        }
        if (isset($def_c['CFG_DB_PASSWORD'])) {
            $def_c['CFG_DB_PASSWORD'] = '***';
        }
        if (isset($def_c['CFG_DB_NAME'])) {
            $def_c['CFG_DB_NAME'] = '***';
        }
        if (isset($def_c['CFG_DB_SERVER'])) {
            $def_c['CFG_DB_SERVER'] = '***';
        }

        foreach ($def_c as $k => $v) {
            $def_c[$k] = $k . '=' . $v;
        }

        $data = array(
            'total' => round($tt, 4),
            'php' => round($tt - $db_tt, 4),
            'db' => round($db_tt, 4),
            'so_queries' => $so_queris,
            'memory_peak' => round(memory_get_peak_usage() / 1024),
            'memory_current' => round(memory_get_usage() / 1024),
            'queries' => self::$db_queries,
            'included_files' => $inc_f,
            'defined_constants' => $def_c
        );

        // Generate unique UID
        $uid = UID::uid32();
        while (Cacher::getInstance()->getDefaultCacher()->get('debug_panel_' . $uid)) {
            $uid = UID::uid32();
        }
        Cacher::getInstance()->getDefaultCacher()->set('debug_panel' . $uid, $data, 3600);

        // For frontend we may add jQuery, for cms not required
        if (MODE === 'site'):
            ?><script src="/vendor/devp-eu/tmcms-core/src/assets/jquery-2.1.0.min.js"></script><?php
        endif; ?>
        <script>
            $.ajax({
                url: '/-/<?= CFG_API_ROUTE ?>/debug_panel?uid=<?=$uid?>',
                success: function (data) {
                    $('body').append(data);
                }
            });
        </script>
        <?php
    }
}
