<?php

namespace TMCms\Log;

use TMCms\Config\Settings;
use TMCms\Log\Entity\AdminUsageEntity;
use TMCms\Log\Entity\AdminUsageEntityRepository;

/**
 * Class Usage
 * @package TMCms\Log
 */
class Usage
{
    /**
     * @var Usage
     */
    private static $instance;

    /** @var array */
    private $usage = [];

    /**
     * Singleton
     */
    private function __construct()
    {

    }

    /**
     * @return Usage
     */
    public static function getInstance()
    {
        return self::$instance ? self::$instance : (self::$instance = new self);
    }

    /**
     * @param string $class
     * @param string $function
     */
    public function add($class = P, $function = P_DO)
    {
        if (!isset($this->usage[$class])) {
            $this->usage[$class] = [];
        }
        if (!isset($this->usage[$class][$function])) {
            $this->usage[$class][$function] = 0;
        }

        $this->usage[$class][$function]++;
    }

    /**
     *
     */
    public function __destruct()
    {
        if (Settings::get('do_not_log_cms_usage')) return;

        // This is required for db autocreate
        new AdminUsageEntityRepository;

        foreach ($this->usage as $class_name => $class) {
            foreach ($class as $function_name => $count) {
                $usage = AdminUsageEntityRepository::findOneEntityByCriteria([
                    'function_class' => $class_name,
                    'function_name' => $function_name,
                ]);
                if (!$usage) {
                    $usage = new AdminUsageEntity();
                    $usage->setFunctionClass($class_name);
                    $usage->setFunctionName($function_name);
                }

                $usage->setCounter($usage->getCounter() + $count);
                $usage->save();
            }
        }
    }
}