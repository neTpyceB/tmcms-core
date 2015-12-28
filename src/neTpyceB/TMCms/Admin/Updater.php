<?php

namespace neTpyceB\TMCms\Admin;

use neTpyceB\TMCms\Admin\Entity\MigrationEntity;
use neTpyceB\TMCms\Admin\Entity\MigrationEntityRepository;
use neTpyceB\TMCms\Cache\Cacher;
use neTpyceB\TMCms\DB\SQL;
use neTpyceB\TMCms\Files\FileSystem;
use neTpyceB\TMCms\Traits\singletonOnlyInstanceTrait;

defined('INC') or exit;

/**
 * Used for updating project with git pull and database migrations.
 * Project is auto-updated after every git push if proper POST hook exists
 * Hook should go to site/cms?key=CFG_KEY&branch=master
 *
 * @package neTpyceB\TMCms\Admin
 */
class Updater
{
    use singletonOnlyInstanceTrait;

    /**
     * @var int result of update script
     */
    private $result = 1;

    /**
     * @var string text from output
     */
    private $result_message = [];

    /**
     * Updates files from git repository
     * Make sure user have access to repository and command is run as this user
     *
     * @param string $branch
     * @return $this
     */
    public function updateSourceCode($branch = CFG_GIT_BRANCH)
    {
        // Check that name is not any other command
        if (!FileSystem::checkFileName($branch)) {
            return $this;
        }

        // To use this command owner of folder and git repository must be the same as web user
        exec('git reset --hard origin/' . $branch . ' 2>&1; git pull -v origin ' . $branch . ' 2>&1', $out);
        if ($out) {
            $this->result_message[] = $out;
        }

        // Clear all caches - may be required to show fresh data
        Cacher::getInstance()->clearAllCaches();

        return $this;
    }

    /**
     * Updates files from composer
     */
    public function updateComposerVendors()
    {
        chdir(DIR_BASE);

        exec('COMPOSER_HOME="' . substr(DIR_BASE, 0, -1) . '" php composer.phar -v update 2>&1', $out);
        if ($out) {
            $this->result_message[] = $out;
        }

        // Clear all caches - may be required to show fresh data
        Cacher::getInstance()->clearAllCaches();

        return $this;
    }

    /**
     * Run DB migrations from migration .sql files
     * @return int count of applied migrations
     */
    public function runMigrations()
    {
        // Check we havy any
        if (!file_exists(DIR_MIGRATIONS)) {
            return 0;
        }

        // Check we have DB structure and any migration applied
        $migrated_files = [];
        if (SQL::getTables()) {
            // Have DB already?
            $migrations = new MigrationEntityRepository();
            $migrated_files = $migrations->getPairs('filename', 'filename');
        }

        $existing_files = FileSystem::scanDirs(DIR_MIGRATIONS);
        if (!$existing_files) {
            return 0;
        }

        $to_migrate = [];
        // Filters migrations that are already done
        foreach ($existing_files as $file) {
            if (isset($migrated_files[$file['name']])) {
                continue;
            }

            $to_migrate[] = $file['name'];
        }

        // Run new migrations
        foreach ($to_migrate as $file) {
            $this->runMigrationFile($file);

            $this->setMigrationFileAsCompleted($file);
        }

        return count($to_migrate);
    }

    /**
     * Run one migration .sql file
     * @param string $filename
     * @return $this
     */
    private function runMigrationFile($filename)
    {
        SQL::startTransaction();

        // Force SQL run without errors
        @q(file_get_contents(DIR_MIGRATIONS . $filename));

        SQL::confirmTransaction();

        return $this;
    }

    /**
     * Set migration .sql file as already finished
     * @param string $filename
     * @return $this
     */
    private function setMigrationFileAsCompleted($filename)
    {
        $migration = new MigrationEntity();
        $migration->setFilename(sql_prepare($filename));
        @$migration->save();

        return $this;
    }

    /**
     * Run PHPUnit tests from console
     */
    public function runTests()
    {
        chdir(DIR_BASE);
        exec('tests/run_tests.sh 2>&1', $out);

        $this->result_message[] = $out;

        return $this;
    }

    /**
     * Get result of all migrations and updates
     * @return array result of update and migrations
     */
    public function getResult()
    {
        return [
            'result' => $this->result,
            'message' => $this->result_message
        ];
    }
}