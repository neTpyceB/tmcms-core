<?php

namespace TMCms\DB;

use Closure;

/**
 * Class TransactionFactory
 * @usage
 *
 * // Example 1
 * $transaction = TransactionFactory::getByLabel('retryable');
 * $closure = function() use ($sql) {
 * q($sql);
 * };
 *
 * if ($transaction->run($closure)->isOk()) {
 * echo "UPDATED -> " . $sql . PHP_EOL;
 * } else {
 * echo "NOT UPDATED -> " . $sql . PHP_EOL;
 * }
 *
 * // Example 2
 * $transaction = TransactionFactory::run(function() use ($sth) {
 * $sth->execute();
 * }, 'retryable');
 *
 * if (!$transaction->isOk()) {
 * throw $transaction->getLastError();
 * }
 *
 */
class TransactionFactory
{
    /**
     * @var array
     */
    private static $pre_configs = [
        'retryable' => [
            'retry_on_deadlock' => true,
            'sleep_on_retry' => true
        ]
    ];

    /**
     * @param Closure $closure
     * @param string|null $label
     * @return Transaction
     */
    public static function run(Closure $closure, $label = null)
    {
        return self::make($closure, $label, true);
    }

    /**
     * @param Closure $closure
     * @param string|null $label
     * @param bool $autorun
     * @return Transaction
     */
    public static function make(Closure $closure, $label = null, $autorun = false)
    {
        $transaction = self::getByLabel($label);
        $transaction->setClosure($closure);
        if ($autorun) {
            $transaction->run();
        }

        return $transaction;
    }

    /**
     * @return Transaction
     */
    public static function getDefault()
    {
        return self::getByLabel();
    }

    /**
     * @param string|null $label
     * @return Transaction
     */
    public static function getByLabel($label = null)
    {
        $config = (is_null($label) || !isset(self::$pre_configs[$label]) ? [] : self::$pre_configs[$label]);
        return new Transaction($config);
    }
}