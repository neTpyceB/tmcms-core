<?php

use TMCms\Routing\Interfaces\IMiddleware;

class ThrottleMiddleware implements IMiddleware
{
    public function run(array $params = [])
    {
        if (!isset($params['limit'])) {
            return;
        }

        $can_proceed = true;

        $params['limit'];
        $timeout = 60;
        $interval = 60;
        $throttleKey = 'middleware_throttle_key';

        if (isset($_SESSION[$throttleKey]['allowed'])) {
            $timeLeft = NOW - $_SESSION[$throttleKey]['allowed'];
            if ($timeLeft < 0) {
                // No more attempts within given time
                $can_proceed = false;
            } else {
                unset($_SESSION[$throttleKey]);
                $_SESSION[$throttleKey]['pass'] = 1;
                $_SESSION[$throttleKey]['setAt'] = NOW;

                if ($_SESSION[$throttleKey]['pass'] > $params['limit']) {
                    $_SESSION[$throttleKey]['allowed'] = NOW + ($timeout);
                }
            }
        } else {
            if (!isset($_SESSION[$throttleKey]['setAt'])) {
                $_SESSION[$throttleKey]['setAt'] = NOW;
            } else {
                if (NOW > ($_SESSION[$throttleKey]['setAt'] + $interval)) {
                    unset($_SESSION[$throttleKey]);
                    $_SESSION[$throttleKey]['setAt'] = NOW;
                    $_SESSION[$throttleKey]['pass'] = 0;
                }
            }

            if (isset($_SESSION[$throttleKey]['pass'])) {
                $_SESSION[$throttleKey]['pass']++;
            } else {
                $_SESSION[$throttleKey]['pass'] = 1;
            }

            if ($_SESSION[$throttleKey]['pass'] > $params['limit']) {
                $_SESSION[$throttleKey]['allowed'] = NOW + ($timeout);
            }
        }

        $attempts_left = $params['limit'] - $_SESSION[$throttleKey]['pass'];
        $seconds_left = $interval;
        if (isset($_SESSION[$throttleKey]['allowed'])) {
            $seconds_left = $_SESSION[$throttleKey]['allowed'] - NOW;
        }

        // Should ban or not
        header('X-RateLimit-Limit: ' . ($params['limit'] * 60)); // header shown requests per hour
        header('X-RateLimit-Remaining: ' . $attempts_left);
        header('X-RateLimit-Reset: ' . (NOW + $seconds_left));
        if (!$can_proceed) {
            die;
        }
    }
}