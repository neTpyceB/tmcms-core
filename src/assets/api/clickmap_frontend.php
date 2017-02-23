<?php

use TMCms\Admin\Structure\Entity\PageClickmapRepository;

if (!isset($_GET['l'])) return;

$clickmap_points = new PageClickmapRepository();
$clickmap_points->setWherePageId((int)$_GET['l']);
$clickmap_points->addSimpleSelectFields(['x', 'y']);
?><div id="clickmap-overlay" style="position:fixed; top: 0; left: 0; width: 100%; height: 100%; background: #000; opacity: 0.1; z-index: 99998"></div>
<div id="clickmap-container" style="position: absolute; left: 0; top: 0; width: 100%; height: 100%; overflow: visible; z-index: 999950">
	<?php foreach($clickmap_points->getAsArrayOfObjectData() as $v): ?>
		<div style="left: <?=$v['x']-5?>px; top: <?=$v['y']-5?>px; position:absolute; width: 10px; height: 10px; background: red; z-index: 99999; border-radius: 5px; opacity: 0.05"></div>
	<?php endforeach; ?>
</div>