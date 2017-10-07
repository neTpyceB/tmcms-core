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
        // Create Session key
        if (!isset($_SESSION[self::ALERT_SESSION_KEY])) {
            $_SESSION[self::ALERT_SESSION_KEY] = '';
        }

        // Get existing messages
        $messages = @json_decode($_SESSION[self::ALERT_SESSION_KEY]);
        if (!$messages) {
            $messages = [];
        }

        // Add new to the list
        $messages[] = $message;

        // Encode to make it string
        $_SESSION[self::ALERT_SESSION_KEY] = json_encode($messages);
    }

    public static function flushSessionAlerts()
    {

        if (!isset($_SESSION[self::ALERT_SESSION_KEY]) || !$_SESSION[self::ALERT_SESSION_KEY]) {
            return;
        }

        // Get existing messages
        $messages = @json_decode($_SESSION[self::ALERT_SESSION_KEY]);
        if (!$messages || !is_array($messages)) {
            return;
        }

        // Add to alerts
        foreach ($messages as $message) {
            BreadCrumbs::getInstance()->addAlerts($message);
        }

        // Reset messages
        $_SESSION[self::ALERT_SESSION_KEY] = '';
    }
}