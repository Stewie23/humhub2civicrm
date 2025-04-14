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
}
?>