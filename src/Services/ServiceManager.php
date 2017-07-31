<?php
declare(strict_types=1);

namespace TMCms\Services;

use TMCms\DB\SQL;
use TMCms\Files\Finder;
use TMCms\Services\Entity\ServiceEntityRepository;
use TMCms\Services\Entity\ServiceEntity;

defined('INC') || exit;

/**
 * Class ServiceManager
 */
class ServiceManager
{
    // Check if any of background service needs to be run
    public static function checkNeeded(): void
    {
        set_time_limit(0);
        ignore_user_abort(1);
        ini_set('memory_limit', '1G');

        $services_collection = new ServiceEntityRepository;
        $services_collection
            ->setWhereRunning(0)
            ->setWhereAutoStart(1);

        /** @var ServiceEntity $service_entity */
        foreach ($services_collection->getAsArrayOfObjects() as $service_entity) {
            if (NOW - $service_entity->getLastTs() >= $service_entity->getPeriod()) {
                self::run($service_entity->getId());
            }
        }
    }

    /**
     * Run service once
     *
     * @param $id
     *
     * @return bool
     *
     */
    public static function run($id): bool
    {
        $id = abs((int)$id);
        /** @var ServiceEntity $service_entity */
        $service_entity = ServiceEntityRepository::findOneEntityById($id);

        if (!$service_entity || $service_entity->getRunning()) {
            return false;
        }

        $file = Finder::getInstance()->searchForRealPath($service_entity->getFile() . '.php', Finder::TYPE_SERVICES);
        if (!$file) {
            return false;
        }

        $service_entity->setRunning(1);
        $service_entity->save();

        if (function_exists('pcntl_fork')) {
            $pid = pcntl_fork();
            SQL::getInstance()->connect();
            switch ($pid) {
                case -1:    // pcntl_fork() failed
                    break; // Could not fork
                case 0:    // you're in the new (child) process
                    require $file; // Run service file
                    self::setDone($id); // Set service is done
                    break;
                default:  // you're in the main (parent) process in which the script is running
                    break;
            }
        } else {
            require DIR_BASE . $file; // Run service file
            self::setDone($id); // Set service is done
        }

        return true;
    }

    public static function setDone($id): bool
    {
        $service_entity = new ServiceEntity($id);
        $service_entity
            ->setRunning(0)
            ->setLastTs($service_entity->getLastTs() + floor((NOW - $service_entity->getLastTs()) / $service_entity->getPeriod()) * $service_entity->getPeriod())
            ->save();

        return true;
    }
}