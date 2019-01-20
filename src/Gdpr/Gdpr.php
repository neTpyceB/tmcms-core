<?php

namespace TMCms\Gdpr;


use TMCms\Config\Settings;

class Gdpr
{
    /**
     * @return bool
     */
    public static function isOn(){
        $gdpr = Settings::get('gdpr');
        return !empty($gdpr['on']);
    }

    /**
     * @return bool
     */
    public static function isAllowedCookies(){
        if(self::isOn()){
            return !empty($_COOKIE['cookies']);
        }else{
            return true;
        }
    }

    /**
     * @return bool
     */
    public static function isAllowedSession(){
        return !ini_get("session.use_cookies") || self::isAllowedCookies();
    }

    /**
     * @param $name
     * @param string $value
     * @param int $expire
     * @param string $path
     */
    public static function setcookie($name ,$value = "" ,int $expire = 0, string $path = "/"){
        if(self::isAllowedCookies()){
            setcookie($name, $value, $expire, $path);
        }
    }

    /**
     * Starts session if allowed
     * @return bool
     */
    public static function session_start(){
        if(self::isAllowedSession()){
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            return true;
        }
        return false;
    }

    /**
     * Destroys session and session cookies if it is started and not allowed
     */
    public static function destroySessionIfNotAllowed(){
        if (!self::isAllowedSession() && ini_get("session.use_cookies") && session_status() == PHP_SESSION_ACTIVE) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
    }
}