<?php

namespace humhub\modules\humhub2civicrm\controllers;

use Yii;
use humhub\modules\admin\components\Controller;
use humhub\modules\humhub2civicrm\models\SettingsForm;

class ConfigController extends Controller
{
    public function actionIndex()
{
    $model = new SettingsForm();
    $model->loadDefaults();

    if ($model->load(Yii::$app->request->post()) && $model->validate()) {
        $connectionResult = $model->testConnection();

        if ($connectionResult === true) {
            $model->save();
            Yii::$app->session->setFlash('success', 'Settings saved and CiviCRM API is reachable.');
        } else {
            Yii::$app->session->setFlash('danger', "Settings not saved. API check failed: $connectionResult");
        }

        // ✅ Always redirect after setting flash
        return $this->redirect(['index']);
    }

    return $this->render('index', [
        'model' => $model,
    ]);
}
}