<?php

use humhub\modules\user\models\Profile;
use humhub\modules\user\models\forms\Registration;
use humhub\modules\user\models\User;

return [
    'id' => 'humhub2civicrm',
    'class' => 'humhub\modules\humhub2civicrm\Module',
    'namespace' => 'humhub\modules\humhub2civicrm',
    'events' => [
        [
            'class' => Profile::class,
            'event' => Profile::EVENT_AFTER_UPDATE,
            'callback' => ['humhub\modules\humhub2civicrm\Events', 'onProfileUpdate'],
        ],
        [
            'class' => Registration::class,
            'event' => Registration::EVENT_AFTER_REGISTRATION,
            'callback' => ['humhub\modules\humhub2civicrm\Events', 'onUserRegistration'],
        ],
        [
            'class' => User::class,
            'event' => User::EVENT_AFTER_DELETE,
            'callback' => ['humhub\modules\humhub2civicrm\Events', 'onUserDelete'],
        ]
    ]
];
?>