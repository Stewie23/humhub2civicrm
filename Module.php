<?php

namespace humhub\modules\humhub2civicrm;

use yii\helpers\Url;
use humhub\modules\user\models\Profile;
use Yii;
use yii\base\Event;
use humhub\components\Module as BaseModule;

class Module extends BaseModule
{
    public function init()
    {
        parent::init();

        Yii::getLogger()->dispatcher->targets[] = new \yii\log\FileTarget([
            'logFile' => Yii::getAlias('@humhub/modules/humhub2civicrm/runtime/civicrm.log'),
            'levels' => ['info', 'error', 'warning'],
            'categories' => ['humhub\modules\humhub2civicrm'],
            'logVars' => [],
            'maxFileSize' => 1024, // in KB
            'maxLogFiles' => 5,
        ]);

    }

    public function disable()
    {
        // Clean up settings when module is deactivated
        $settings = $this->settings;
        $settings->delete('apiUrl');
        $settings->delete('apiKey');
        $settings->delete('siteKey');
        $settings->delete('newsletters');

        return parent::disable();
    }

    public function getConfigUrl()
    {
        return \yii\helpers\Url::to(['/humhub2civicrm/config']);
    }
}
?>