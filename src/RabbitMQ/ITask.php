<?php

namespace TMCms\RabbitMQ;

interface ITask {
    public function run($params);
}