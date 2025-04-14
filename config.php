<?php

use humhub\modules\user\models\Profile;

return [
    'id' => 'humhub2civicrm',
    'class' => 'humhub\modules\humhub2civicrm\Module',
    'namespace' => 'humhub\modules\humhub2civicrm',
    'events' => [
        [
            'class' => Profile::class,
            'event' => Profile::EVENT_AFTER_UPDATE,
            'callback' => ['humhub\modules\humhub2civicrm\Events', 'onProfileUpdate'],
        ]
    ]
];
?>