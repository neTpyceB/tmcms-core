<?php

use TMCms\Admin\Structure\Entity\PageClickmap;
use TMCms\Admin\Structure\Entity\PageClickmapRepository;
use \TMCms\Strings\Converter;

if (!$_POST || !isset($_POST['x'], $_POST['y'], $_POST['l'])) exit;

$period = 3600; // 1 hour
$max_count = 600;
$ip = Converter::ip2long();

$clickmap_points = new PageClickmapRepository();
$clickmap_points->setWhereIpLong($ip);
$clickmap_points->addWhereFieldIsHigher('ts', NOW - $period);

$count_of_clicks_for_period = $clickmap_points->getCountOfObjectsInCollection();

if ($count_of_clicks_for_period < $max_count) {
    $click = new PageClickmap();
    $click
        ->loadDataFromArray([
            'x' => (int)$_POST['x'],
            'y' => (int)$_POST['y'],
            'page_id' => (int)$_POST['l'],
            'ip_long' => $ip,
        ])
        ->save();
}

exit;