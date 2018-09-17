<?php
declare(strict_types=1);

namespace TMCms\Admin;

use TMCms\Admin\Users\Entity\UsersMessageEntity;
use TMCms\Admin\Users\Entity\UsersMessageEntityRepository;
use TMCms\HTML\BreadCrumbs;
use TMCms\Traits\singletonOnlyInstanceTrait;

/**
 * Used to show user flash notifications
 * May be used to make user messaging one to another
 *
 * Class Messages
 * @package TMCms\Admin
 */
class Messages
{
    const ALERT_SESSION_KEY = 'cms_alerts';
    const ALERT_SESSION_KEY_FRONT = 'flash_alerts';
    const TOASTR_MESSAGE_COLOR_BROWSER = 0;
    const TOASTR_MESSAGE_COLOR_GREEN = 1;
    const TOASTR_MESSAGE_COLOR_RED = 2;
    const TOASTR_MESSAGE_COLOR_BLACK = 3;

    use singletonOnlyInstanceTrait;

    /**
     * Notification using browser API
     * Get array of UserMessage
     *
     * @param int $from_user_id sender user id
     * @param int $to_user_id   recipient user id
     *
     * @return array
     */
    public static function receiveMessages(int $from_user_id, int $to_user_id = USER_ID): array
    {
        // Direction A -> B
        $message_collection = new UsersMessageEntityRepository();
        $messages = $message_collection
            ->setWhereFromUserId($from_user_id)
            ->setWhereToUserId($to_user_id)
            ->getAsArrayOfObjects();

        // Direction B -> A
        $message_collection = new UsersMessageEntityRepository();
        $message_collection
            ->setWhereFromUserId($to_user_id)
            ->setWhereToUserId($from_user_id);

        // Combine messages
        $messages = array_merge($messages, $message_collection
            ->getAsArrayOfObjects());

        // Sort by time
        usort($messages, function($a, $b) {
            /** @var UsersMessageEntity $a */
            /** @var UsersMessageEntity $b */
            $a = $a->getTs();
            $b = $b->getTs();

            if ($a === $b) {
                return 0;
            }

            return ($a < $b) ? 1 : -1;
        });

        return $messages;
    }

    /**
     * @param string $text         Text to be sent
     * @param int    $to_user_id   recipient user id
     * @param int    $from_user_id sender user id
     * @param int    $notify_toastr
     *
     * @return UsersMessageEntity that was sent
     */
    public static function sendMessage(string $text, int $to_user_id = USER_ID, int $from_user_id = 0, int $notify_toastr = self::TOASTR_MESSAGE_COLOR_GREEN)
    {
        $message = new UsersMessageEntity();

        return $message
            ->setFromUserId($from_user_id)
            ->setToUserId($to_user_id)
            ->setMessage($text)
            ->setNotify($notify_toastr)
            ->save();
    }

    /**
     * Notification using green toastr alerts
     *
     * @param string $text         Text to be sent
     * @param int    $to_user_id   recipient user id
     * @param int    $from_user_id sender user id
     *
     * @return UsersMessageEntity that was sent
     */
    public static function sendGreenAlert(string $text, int $to_user_id = USER_ID, int $from_user_id = 0): UsersMessageEntity
    {
        return self::sendMessage($text, $to_user_id, $from_user_id, self::TOASTR_MESSAGE_COLOR_GREEN);
    }

    /**
     * Notification using red toastr alerts
     *
     * @param string $text         Text to be sent
     * @param int    $to_user_id   recipient user id
     * @param int    $from_user_id sender user id
     *
     * @return UsersMessageEntity that was sent
     */
    public static function sendRedAlert(string $text, int $to_user_id = USER_ID, int $from_user_id = 0): UsersMessageEntity
    {
        return self::sendMessage($text, $to_user_id, $from_user_id, self::TOASTR_MESSAGE_COLOR_RED);
    }

    /**
     * Notification using black toastr alerts
     *
     * @param string $text         Text to be sent
     * @param int    $to_user_id   recipient user id
     * @param int    $from_user_id sender user id
     *
     * @return UsersMessageEntity that was sent
     */
    public static function sendBlackAlert(string $text, int $to_user_id = USER_ID, int $from_user_id = 0): UsersMessageEntity
    {
        return self::sendMessage($text, $to_user_id, $from_user_id, self::TOASTR_MESSAGE_COLOR_BLACK);
    }

    /**
     * @param string $message
     */
    public static function sendFlashAlert(string $message)
    {
        $key = self::getInstance()->getFlashAlertsSessionKey();

        // Create Session key
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = '';
        }

        // Get existing messages
        $messages = self::getFlashAlerts();

        // Add new to the list
        $messages[] = $message;

        // Encode to make it string
        $_SESSION[$key] = json_encode($messages);
    }

    public static function flushSessionAlerts()
    {
        $key = self::getInstance()->getFlashAlertsSessionKey();

        // Add to alerts
        foreach (self::getFlashAlerts() as $message) {
            BreadCrumbs::getInstance()->addAlerts($message);
        }

        // Reset messages
        $_SESSION[$key] = '';
    }

    public function getFlashAlertsSessionKey(): string {
        return MODE === 'cms' ? self::ALERT_SESSION_KEY : self::ALERT_SESSION_KEY_FRONT;
    }

    public function getFlashAlerts() {
        $key = self::getInstance()->getFlashAlertsSessionKey();

        if (!isset($_SESSION[$key]) || !$_SESSION[$key]) {
            return [];
        }

        // Get existing messages
        $messages = @json_decode($_SESSION[$key]);
        if (!$messages || !is_array($messages)) {
            return [];
        }

        return $messages;
    }
}
