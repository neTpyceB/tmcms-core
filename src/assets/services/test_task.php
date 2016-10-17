<?php

sleep(rand(1, 3));

$data = NOW . 'First task<br>' . PHP_EOL;

file_put_contents(DIR_FRONT_LOGS . 'tasks.log', $data, FILE_APPEND);