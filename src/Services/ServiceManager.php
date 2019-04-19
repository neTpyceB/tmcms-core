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
        ignore_user_abort(true);
        ini_set('memory_limit', '1G');

        $services_collection = new ServiceEntityRepository;
        $services_collection
            ->setWhereRunning(0)
            ->setWhereAutoStart(1);

        /** @var ServiceEntity $service_entity */
        foreach ($services_collection->getAsArrayOfObjects() as $service_entity) {
//            if (NOW - $service_entity->getLastTs() >= $service_entity->getPeriod()) {
                self::run($service_entity->getId());
//            }
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

        if (!$file || !\file_exists(DIR_BASE . $file)) {
            return false;
        }

        $service_entity->setRunning(1);
        $service_entity->save();

        // No bckground processes
        if (!function_exists('pcntl_fork')) {
            // Run service file
            require DIR_BASE . $file;
            // Set service is done
            self::setDone($id);

            return true;
        }

        // Run as background process
        $pid = pcntl_fork();
        SQL::getInstance()->connect();

        if ($pid == -1) {
            echo $pid . ": Could not start background service for " . $file . "\n";
            // Could not fork
        } else if ($pid) {
            // We are the parent, doing nothing
        } else {
            echo "Running ". $file . "\n";
            // Run service file
            require DIR_BASE . $file;
            // Set service is done
            self::setDone($id);
            exit("Finished ". $file . "\n");
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
