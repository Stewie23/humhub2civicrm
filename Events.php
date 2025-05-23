<?php

namespace humhub\modules\humhub2civicrm;

use Yii;
use yii\base\Event;
use humhub\modules\humhub2civicrm\services\CiviCrmConnector;
use humhub\modules\user\events\UserEvent;

class Events
{
    private static $alreadyLogged = false;

    public static function onProfileUpdate($event)
    {
        if (self::$alreadyLogged) {
            return;
        }
        self::$alreadyLogged = true;

        $profile = $event->sender;
        $user = $profile->user;

        CiviCrmConnector::sendProfile($user->email,$user);
    }

    public static function onUserRegistration($event)
    {
        $user = $event->identity; // this is the actual User object
        CiviCrmConnector::sendProfile($user->email, $user);
    }

    public static function onUserSoftDelete(UserEvent $event)
    {
        $user = $event->user; // this is the User model
        CiviCrmConnector::handleUserDeletion($user->email, $user);
    }  

    public static function onUserHardDelete(Event $event)
    {
        $user = $event->sender;
        CiviCrmConnector::handleUserDeletion($user->email, $user);
    }   
}
?>