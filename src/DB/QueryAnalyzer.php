<?php

namespace TMCms\DB;

use TMCms\DB\Entity\DbQueryAnalyzerEntity;
use TMCms\DB\Entity\DbQueryAnalyzerEntityRepository;
use TMCms\Traits\singletonOnlyInstanceTrait;

defined('INC') or exit;

/**
 * Class QueryAnalyzer
 * Used to show query log and give some tips on speed and indexes
 */
class QueryAnalyzer
{
    use singletonOnlyInstanceTrait;

    /**
     * @var array
     * Saved on run-time
     */
    private $_runtime_queries = [];
    private $already_saved = false;

    /**
     * Save all queries to DB
     */
    public function store()
    {
        if (!$this->_runtime_queries || $this->already_saved) {
            // No queries to save or already saved
            return;
        }

        // Prevent recursive saves
        $this->already_saved = true;

        $path = defined('PATH') ? PATH : (defined('PATH_INTERNAL') ? PATH_INTERNAL : '');

        $objects = [];
        foreach ($this->_runtime_queries as $query) {
            $query_obj = new DbQueryAnalyzerEntity;
            $query_obj->loadDataFromArray([
                'hash' => md5($query['query']),
                'query' => $query['query'],
                'tt' => round($query['run_time'], 3),
                'path' => $path,
            ]);

            $objects[] = $query_obj;
        }

        // Reset queue
        $this->_runtime_queries = [];

        // Save collected objects
        if ($objects) {
            $query_repository = new DbQueryAnalyzerEntityRepository;
            $query_repository->setCollectedObjects($objects);
            $query_repository->save(); // Save all objects
        }
    }

    /**
     * Add run-time query for latest save to DB
     * @param string $q
     * @param int $time_taken
     */
    public function addQuery($q, $time_taken)
    {
        $this->_runtime_queries[] = ['query' => $q, 'run_time' => $time_taken];
    }
}