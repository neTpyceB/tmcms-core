<?php

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

    use singletonOnlyInstanceTrait;

    /**
     * Notification using browser API
     * Get array of UserMessage
     * @param int $from_user_id sender user id
     * @param int $to_user_id recipient user id
     * @return array
     */
    public static function receiveMessages($from_user_id, $to_user_id = USER_ID)
    {
        // Direction A -> B
        $message_collection = new UsersMessageEntityRepository();
        $message_collection->setWhereFromUserId($from_user_id);
        $message_collection->setWhereToUserId($to_user_id);

        $messages = $message_collection->getAsArrayOfObjects();

        // Direction B -> A
        $message_collection = new UsersMessageEntityRepository();
        $message_collection->setWhereFromUserId($to_user_id);
        $message_collection->setWhereToUserId($from_user_id);

        // Combine messages
        $messages = array_merge($messages, $message_collection->getAsArrayOfObjects());

        // Sort by time
        usort($messages, function ($a, $b) {
            /** @var UsersMessageEntity $a */
            /** @var UsersMessageEntity $b */
            $a = $a->getTs();
            $b = $b->getTs();

            if ($a == $b) {
                return 0;
            }
            return ($a < $b) ? 1 : -1;
        });

        return $messages;
    }

    /**
     * Notification using green toastr alerts
     * @param string $text Text to be sent
     * @param int $to_user_id recipient user id
     * @param int $from_user_id sender user id
     * @return UsersMessageEntity that was sent
     */
    public static function sendGreenAlert($text, $to_user_id = USER_ID, $from_user_id = 0)
    {
        return self::sendMessage($text, $to_user_id, $from_user_id, 1);
    }

    /**
     * @param string $text Text to be sent
     * @param int $to_user_id recipient user id
     * @param int $from_user_id sender user id
     * @param int $notify_toastr
     * @return UsersMessageEntity that was sent
     */
    public static function sendMessage($text, $to_user_id = USER_ID, $from_user_id = 0, $notify_toastr = 0)
    {
        $message = new UsersMessageEntity();
        $message->setFromUserId($from_user_id);
        $message->setToUserId($to_user_id);
        $message->setMessage($text);
        /* setNotify =
         * 0 - def. browser notify
         * 1 - green
         * 2 - red
         * 3 - black
         */
        $message->setNotify((int)$notify_toastr);
        $message->save();

        return $message;
    }

    /**
     * Notification using red toastr alerts
     * @param string $text Text to be sent
     * @param int $to_user_id recipient user id
     * @param int $from_user_id sender user id
     * @return UsersMessageEntity that was sent
     */
    public static function sendRedAlert($text, $to_user_id = USER_ID, $from_user_id = 0)
    {
        return self::sendMessage($text, $to_user_id, $from_user_id, 2);
    }
    public static function sendBlackAlert($text, $to_user_id = USER_ID, $from_user_id = 0)
    {
        return self::sendMessage($text, $to_user_id, $from_user_id, 3);
    }

    public static function sendFlashAlert($message)
    {
        if (!isset($_SESSION[self::ALERT_SESSION_KEY])) {
            $_SESSION[self::ALERT_SESSION_KEY] = '';
        }

        // Get existing messages
        $messages = @json_decode($_SESSION[self::ALERT_SESSION_KEY]);

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