<?php

namespace TMCms\Strings;

use Illuminate\View\Factory;
use Philo\Blade\Blade;
use TMCms\Files\FileSystem;
use Twig_Autoloader;
use Twig_Environment;
use Twig_Loader_Filesystem;

class ExternalTemplater
{
    private $templater = 'twig';

    public function __construct($templater = 'twig')
    {
        $this->templater = $templater;

        return $this;
    }

    public function processHtml($html, array $page_data)
    {
        //=== Use twigphp/Twig
        if ($this->templater == 'twig') {
            FileSystem::mkDir(DIR_CACHE . 'twig');
            Twig_Autoloader::register();
            $loader = new Twig_Loader_Filesystem(dirname($page_data['template_file']));
            $envParams = [
                'cache'       => DIR_CACHE . 'twig',
                'debug'       => true,
                'auto_reload' => true,
            ];

            $twig = new Twig_Environment($loader, $envParams);
            $html = $twig->render(pathinfo($page_data['template_file'], PATHINFO_BASENAME));
        }

        //=== Use philo/laravel-blade
        if ($this->templater == 'blade') {
            $file_parts = explode('/', $page_data['file']);

            FileSystem::mkDir(DIR_CACHE . 'blade');
            $blade = new Blade(DIR_FRONT_TEMPLATES . $file_parts[0], DIR_CACHE . 'blade');
            /** @var Factory $view */
            $view = $blade->view();
            $html = $view->make(str_replace('.blade.php', '', $file_parts[1]))->render();
        }

        return $html;
    }
}