<?php
declare(strict_types=1);

use TMCms\Files\FileSystem;

$start = NOW;

sleep(2);

$finish = time();

$data = date('Y-m-d H:i:s', NOW) . ': Test service task copy. Run for: ' . ($finish - $start) . ' seconds.' . PHP_EOL . PHP_EOL;

FileSystem::mkDir(DIR_FRONT_LOGS);

file_put_contents(DIR_FRONT_LOGS . 'tasks.log', $data, FILE_APPEND);
