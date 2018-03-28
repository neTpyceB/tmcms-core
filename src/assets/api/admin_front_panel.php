<?php

use TMCms\Admin\Users;
use TMCms\Config\Settings;

if (!Settings::get('admin_panel_on_site') || !Users::getInstance()->isLogged() || !isset($_GET['page_id'])) {
	die;
}

ob_start();
?>
<style>
	#admin_front_panel table {
		border-collapse: collapse;
	}
	#admin_front_panel td {
		border: 2px solid #000; padding: 3px;
	}
</style>
<div id="admin_front_panel" style="position: fixed; top: 0; left: 0; opacity: 0.9; width: 100%; height: 25px; background: #fff; z-index: 99998; font-size: 16px; font-family: Arial, sans-serif">
    <table cellpadding="0" cellspacing="0" style="position: absolute; top: 0; right: 0">
		<tr>
			<td style="text-align: right">
                <a href="<?= DIR_CMS_URL ?>?p=structure&do=edit_components&id=<?= $_GET['page_id'] ?>">Components</a>
            </td>
            <td style="text-align: right">
                <a href="<?= DIR_CMS_URL ?>?p=structure&do=customs&id=<?= $_GET['page_id'] ?>">Customs</a>
            </td>
            <td style="text-align: right">
                <a href="<?= DIR_CMS_URL ?>?p=structure&do=edit_page&id=<?= $_GET['page_id'] ?>">Properties</a>
            </td>
            <td style="text-align: right">
                <a href="/-/api/cms_from_panel/toggle_setting/clear_cache/">Clear cache</a>
            </td>
            <td style="text-align: right">
                <a href="/-/api/cms_from_panel/toggle_setting/debug_panel/">Debug panel</a>
            </td>
            <td style="text-align: right">
                <a href="/-/api/cms_from_panel/toggle_setting/admin_panel_on_site/">Turn off</a>
            </td>
            <td style="text-align: right">
                <a href="<?= DIR_CMS_URL ?>">Go to CMS</a>
			</td>
			<td style="text-align: right">
				<a href="<?= DIR_CMS_URL ?>?p=home&do=_exit" onclick="return confirm('<?= __('Are you sure?') ?>');">Logout</a>
			</td>
		</tr>
	</table>
</div>
<?
echo ob_get_clean(); die;
