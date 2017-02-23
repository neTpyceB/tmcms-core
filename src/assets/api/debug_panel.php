<?php
use TMCms\Cache\Cacher;

if (!isset($_GET['uid']) || strlen($_GET['uid']) !== 32) return;
$data = Cacher::getInstance()->getDefaultCacher()->get('debug_panel'. $_GET['uid']);
if (!$data) return;

$trace = '<table>';
foreach ($data['queries'] as $query_data) {
    $q = $query_data['query'];
    $trace .= '<tr><td colspan="3"><br><br>'. $query_data['backtrace'][0]['args'][0] .'<br><br></td></tr>';
    foreach ($query_data['backtrace'] as $tr) {
        if (!isset($tr['file'])) $tr['file'] = '';
        if (!isset($tr['line'])) $tr['line'] = '';
        if (!isset($tr['function'])) $tr['function'] = '';
        $trace .= '<tr><td>'. substr($tr['file'], strlen(DIR_BASE) - 1) .'</td><td>'. $tr['line'] .'</td><td>'. $tr['function'] . '</td></tr>';
    }
}
$trace .= '</table>';
ob_start();
?>

<style>
    #debug_panel td {padding: 2px 6px;}
</style>
<div id="debug_panel" style="opacity: 0.9; position: fixed; bottom: 0; left: 0; width: 100%; height: 70px; background: #fff; border-top: 2px solid #000; z-index: 99998; padding: 5px; font-size: 14px; font-family: Arial, sans-serif">
	<table style="font-size: 14px; font-family: Arial, sans-serif; width: 100%; border-collapse: collapse">
		<tr>
			<th style="padding: 0 20px 0 10px" align="left">Time taken</th>
			<th style="padding: 0 20px 0 10px" align="left">Memory usage</th>
			<th style="padding: 0 20px 0 10px" align="left">Queries (<?=$data['so_queries']?>):</th>
			<th style="padding: 0 20px 0 10px" align="left">Included Files (<?= count($data['included_files']) ?>)</th>
			<th style="padding: 0 20px 0 10px" align="left">Defined Constants (<?= count($data['defined_constants']) ?>)</th>
		</tr>
		<tr>
			<td style="padding: 0 20px 0 10px">
				TOTAL: <strong><?=round($data['total'], 4)?></strong> seconds<br>
				PHP: <strong><?=round($data['php'], 4)?></strong> seconds<br>
				DB: <strong><?=round($data['db'], 4)?></strong> seconds in <strong><?=$data['so_queries']?></strong> queries<br><br>
			</td>
			<td style="padding: 0 20px 0 10px">
				PHP at peak: <strong><?=$data['memory_peak']?></strong> KB<br>
				PHP current: <strong><?=$data['memory_current']?></strong> KB<br>
			</td>
			<td style="padding: 0 20px 0 10px">
				<div style="display: none; position: absolute; bottom: 80px; left: 10%; width: 80%; padding: 10px; border: 2px solid #000; background: #fff;" id="debug_panel_queries">
					<?= $trace ?>
				</div>
				<div style="color: #316ac5; cursor: pointer" onclick="jQuery('#debug_panel_queries').toggle()">Toggle view</div>
			</td>
			<td style="padding: 0 20px 0 10px">
				<div style="display: none; position: absolute; bottom: 80px; left: 10%; width: 80%; padding: 10px; border: 2px solid #000; background: #fff" id="debug_panel_included_files">
					<?=implode('<br>', $data['included_files']);?>
				</div>
				<div style="color: #316ac5; cursor: pointer" onclick="jQuery('#debug_panel_included_files').toggle()">Toggle view</div>
			</td>
			<td style="padding: 0 20px 0 10px">
				<div style="display: none; position: absolute; bottom: 80px; left: 10%; width: 80%; padding: 10px; border: 2px solid #000; background: #fff" id="debug_panel_defined_constants">
					<?=implode('<br>', $data['defined_constants']);?>
				</div>
				<div style="color: #316ac5; cursor: pointer" onclick="jQuery('#debug_panel_defined_constants').toggle()">Toggle view</div>
			</td>
		</tr>
	</table>
</div>
<script>
    jQuery('#debug_panel_queries, #debug_panel_included_files, #debug_panel_defined_constants').height(jQuery(window).height() * 0.7).css('overflow-y', 'scroll');
</script>
<?
echo ob_get_clean(); die;