<?php
declare(strict_types=1);

$start = NOW;

sleep(random_int(1, 3));

$finish = time();

$data = NOW . ': First task. Run for: ' . ($finish - $start) . ' seconds.' . PHP_EOL . PHP_EOL;

file_put_contents(DIR_FRONT_LOGS . 'tasks.log', $data, FILE_APPEND);