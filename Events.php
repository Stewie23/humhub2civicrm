<?php

namespace humhub\modules\humhub2civicrm;

use Yii;
use humhub\modules\humhub2civicrm\services\CiviCrmConnector;

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

    public static function onUserDelete($event)
    {
        $user = $event->sender;
        CiviCrmConnector::handleUserDeletion($user->email, $user);
    }

    
}
?>