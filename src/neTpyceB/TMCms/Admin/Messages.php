<?php

namespace neTpyceB\TMCms\Admin;

use neTpyceB\TMCms\Admin\Users\Object\UsersMessage;
use neTpyceB\TMCms\Admin\Users\Object\UsersMessageRepository;
use neTpyceB\TMCms\Traits\singletonOnlyInstanceTrait;

/**
 * Used to show user flash notifications
 * May be used to make user messaging one to another
 *
 * Class Messages
 * @package neTpyceB\TMCms\Admin
 */
class Messages
{
    use singletonOnlyInstanceTrait;

    /**
     * @param string $text Text to be sent
     * @param int $to_user_id recipient user id
     * @param int $from_user_id sender user id
     * @return UsersMessage that was sent
     */
    public static function sendMessage($text, $to_user_id = USER_ID, $from_user_id = 0)
    {
        $message = new UsersMessage();
        $message->setFromUserId($from_user_id);
        $message->setToUserId($to_user_id);
        $message->setMessage($text);
        $message->save();

        return $message;
    }

    /**
     * Get array of UserMessage
     * @param int $from_user_id sender user id
     * @param int $to_user_id recipient user id
     * @return array
     */
    public static function receiveMessages($from_user_id, $to_user_id = USER_ID)
    {
        // Direction A -> B
        $message_collection = new UsersMessageRepository();
        $message_collection->setWhereFromUserId($from_user_id);
        $message_collection->setWhereToUserId($to_user_id);

        $messages = $message_collection->getAsArrayOfObjects();

        // Direction B -> A
        $message_collection = new UsersMessageRepository();
        $message_collection->setWhereFromUserId($to_user_id);
        $message_collection->setWhereToUserId($from_user_id);

        // Combine messages
        $messages = array_merge($messages, $message_collection->getAsArrayOfObjects());

        // Sort by time
        usort($messages, function ($a, $b) {
            /** @var UsersMessage $a */
            /** @var UsersMessage $b */
            $a = $a->getTs();
            $b = $b->getTs();

            if ($a == $b) {
                return 0;
            }
            return ($a < $b) ? 1 : -1;
        });

        return $messages;
    }
}