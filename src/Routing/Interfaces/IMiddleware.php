<?php
/**
 * Updated by neTpyceB [devp.eu] at 2017.4.2
 */

namespace TMCms\Routing\Interfaces;

interface IMiddleware
{
    public function run(array $params = []);
}