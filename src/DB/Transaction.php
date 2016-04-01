<?php

namespace TMCms\DB;

use Closure;
use Exception;

class Transaction
{
    const ERR_CODE_SERIALIZATION = 1213;
    const ERR_CODE_WAITING = 1205;

    /**
     * @var array
     */
    protected $config = [
        'rollback_on_exception' => true,
        'retry_on_deadlock' => false,
        'max_retries' => 3,
        'sleep_on_retry' => false,
        'sleep_ms' => 1000
    ];

    /**
     * @var Closure
     */
    protected $closure = null;

    /**
     * @var int
     */
    protected $totalTries = 0;

    /**
     * @var bool
     */
    protected $ok = false;

    /**
     * @var Exception[]
     */
    protected $errors = [];

    /**
     * @param array $config
     * @param Closure $closure
     */
    public function __construct(array $config = [], Closure $closure = null)
    {
        $this->config = array_merge($this->config, $config);
        $this->setClosure($closure);
    }

    /**
     * @return bool
     */
    public function isOk()
    {
        return $this->ok;
    }

    /**
     * @return int
     */
    public function getTotalTries()
    {
        return $this->totalTries;
    }

    /**
     * @return bool
     */
    public function isRollbackOnExceptionEnabled()
    {
        return (bool)$this->config['rollback_on_exception'];
    }

    /**
     * @param bool $enabled
     * @return $this
     */
    public function setRollbackOnExceptionEnabled($enabled = true)
    {
        $this->config['rollback_on_exception'] = $enabled;
        return $this;
    }

    /**
     * @return bool
     */
    public function isRetryOnDeadlockEnabled()
    {
        return (bool)$this->config['retry_on_deadlock'];
    }

    /**
     * @param bool $enabled
     * @return $this
     */
    public function setRetryOnDeadlockEnabled($enabled = true)
    {
        $this->config['retry_on_deadlock'] = $enabled;
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxRetries()
    {
        return (int)$this->config['max_retries'];
    }

    /**
     * @param int $retries
     * @return $this
     */
    public function setMaxRetries($retries)
    {
        $this->config['max_retries'] = $retries;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSleepOnRetryEnabled()
    {
        return (bool)$this->config['sleep_on_retry'];
    }

    /**
     * @param bool $enabled
     * @return $this
     */
    public function setSleepOnRetryEnabled($enabled = true)
    {
        $this->config['sleep_on_retry'] = $enabled;
        return $this;
    }

    /**
     * @return int
     */
    public function getSleepTime()
    {
        return ((int)$this->config['sleep_ms']) * 1000;
    }

    /**
     * @param $time
     * @return $this
     */
    public function setSleepTime($time)
    {
        $this->config['sleep_ms'] = $time;
        return $this;
    }

    /**
     * @return callable
     */
    public function getClosure()
    {
        return $this->closure;
    }

    /**
     * @param Closure $closure
     * @return $this
     */
    public function setClosure(Closure $closure = null)
    {
        if (!is_null($closure)) {
            $this->closure = $closure;
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function begin()
    {
        if (SQL::isTransactionActive()) {
            SQL::startTransaction();
        }

        return $this;
    }

    /**
     * @param Closure $closure
     * @return $this
     * @throws TransactionClosureUndefinedException
     */
    public function executeClosure(Closure $closure = null)
    {
        $this->setClosure($closure);
        if (!is_null($this->closure)) {
            $this->closure->__invoke();
        } else {
            throw new TransactionClosureUndefinedException();
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function commit()
    {
        if (SQL::isTransactionActive()) {
            SQL::confirmTransaction();
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function rollback()
    {
        if (SQL::isTransactionActive()) {
            SQL::cancelTransaction();
        }

        return $this;
    }

    /**
     * @param Closure $closure
     * @return $this
     * @throws TransactionClosureUndefinedException
     */
    public function run(Closure $closure = null)
    {
        $retriesEnabled = $this->isRetryOnDeadlockEnabled();
        $maxTries = 1 + $this->getMaxRetries();
        $this->totalTries = 0;
        $this->errors = [];

        do {
            $this->totalTries++;
            if ($this->totalTries > 1 && $this->isSleepOnRetryEnabled()) {
                usleep($this->getSleepTime());
            }
            $this->ok = true;
            try {
                $this->begin();
                $this->executeClosure($closure);
                $this->commit();
            } catch (TransactionClosureUndefinedException $e) {
                throw $e;
            } catch (Exception $e) {
                $this->errors[] = $e;
                if ($this->isRollbackOnExceptionEnabled()) {
                    $this->rollback();
                }
                $this->handleException($e, $retriesEnabled,
                    ($this->totalTries == $maxTries));
                $this->ok = false;
            }
        } while (!$this->ok && $retriesEnabled &&
            $this->isDeadlock($this->getLastError()) &&
            $this->totalTries < $maxTries);

        return $this;
    }

    /**
     * @return Exception
     */
    public function getLastError()
    {
        $last = end($this->errors);
        if ($last === false) {
            $last = new Exception();
        }

        return $last;
    }

    /**
     * @return Exception[]
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @param Exception $e
     * @return bool
     */
    public function isDeadlock(Exception $e)
    {
        return in_array($e->getCode(),
            [static::ERR_CODE_SERIALIZATION, static::ERR_CODE_WAITING]
        );
    }

    /**
     * @param Exception $e
     * @param bool $retriesEnabled
     * @param bool $lastTry
     */
    protected function handleException(Exception $e, $retriesEnabled = false, $lastTry = false)
    {
        if ($this->isDeadlock($e)) {
            if ($retriesEnabled && !$lastTry) {
                return;
            }
        }

        // TODO check Exception is sent to Devs
    }

}

class TransactionClosureUndefinedException extends Exception
{
}