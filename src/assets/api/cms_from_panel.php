<?php

use TMCms\Admin\Users;
use TMCms\Cache\Cacher;
use TMCms\Config\Settings;

if (!Settings::get('admin_panel_on_site') || !Users::getInstance()->isLogged()) {
    die;
}

if (API_ACTION == 'toggle_setting') {
    switch (API_ID) {
        case 'clear_cache':
            // Clear cache for data
            Cacher::getInstance()->clearAllCaches();

            break;

        case 'enable_visual_edit':
            // Clear cache for data
            Settings::getInstance()->set('enable_visual_edit', !Settings::get('enable_visual_edit'));

            break;

        case 'debug_panel':
            // Clear cache for data
            Settings::getInstance()->set('debug_panel', !Settings::get('debug_panel'));

            break;

        case 'admin_panel_on_site':
            // Clear cache for data
            Settings::getInstance()->set('admin_panel_on_site', !Settings::get('admin_panel_on_site'));

            break;
    }
}

back();