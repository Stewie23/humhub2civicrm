<?php

use yii\widgets\ActiveForm;
use yii\helpers\Html;

/** @var $model humhub\modules\humhub2civicrm\models\SettingsForm */

$initialRowCount = is_array($model->newsletters) ? count($model->newsletters) : 1;
?>

<div class="panel panel-default">
    <div class="panel-heading">CiviCRM Connector Settings</div>
    <div class="panel-body">

        <?php $form = ActiveForm::begin(); ?>

        <?= $form->field($model, 'apiUrl')->textInput(['maxlength' => true]) ?>
        <?= $form->field($model, 'apiKey')->passwordInput(['maxlength' => true]) ?>
        <?= $form->field($model, 'siteKey')->passwordInput(['maxlength' => true]) ?>

        <hr>

        <label>Profile Fields to CiviCRM Group Mappings</label>
        <p class="help-block" style="margin-top: -10px; margin-bottom: 15px;">
            Each row maps a profile field (e.g. a checkbox) to CiviCRM group actions. 
            If the field is active (<code>true</code>), the contact is added to the “Join Group”. 
            If it is inactive (<code>false</code>), the contact is removed from the “Leave Group”.
            <br>
            If you don’t use advanced workflows in CiviCRM (e.g. double opt-in), you can enter the same group ID for both fields.
        </p>

        <div id="newsletter-rows">
            <?php foreach ($model->newsletters as $i => $entry): ?>
                <div class="newsletter-row form-inline" style="margin-bottom:10px;">
                    <input type="text" class="form-control" name="SettingsForm[newsletters][<?= $i ?>][field]"
                           value="<?= Html::encode($entry['field']) ?>" placeholder="Profile field" style="width: 30%;" />
                    <input type="text" class="form-control" name="SettingsForm[newsletters][<?= $i ?>][groupJoin]"
                           value="<?= Html::encode($entry['groupJoin']) ?>" placeholder="Join group ID" style="width: 20%; margin-left:5px;" />
                    <input type="text" class="form-control" name="SettingsForm[newsletters][<?= $i ?>][groupLeave]"
                           value="<?= Html::encode($entry['groupLeave']) ?>" placeholder="Leave group ID" style="width: 20%; margin-left:5px;" />
                    <button type="button" class="remove-newsletter-row btn btn-danger btn-sm" style="margin-left:5px;">&times;</button>
                </div>
            <?php endforeach; ?>
        </div>

        <button id="add-newsletter-btn" type="button" class="btn btn-default btn-sm">+ Add Newsletter</button>

        <hr>
        <?= Html::submitButton('Save', ['class' => 'btn btn-primary']) ?>

        <?php ActiveForm::end(); ?>
    </div>
</div>

<?php
// JavaScript for dynamic row addition/removal
$this->registerJs(<<<JS
    let rowIndex = $initialRowCount;

    function addNewsletterRow() {
        const container = $('#newsletter-rows');
        const html = `
            <div class="newsletter-row form-inline" style="margin-bottom:10px;">
                <input type="text" class="form-control" name="SettingsForm[newsletters][\${rowIndex}][field]" placeholder="Profile field" style="width: 30%;" />
                <input type="text" class="form-control" name="SettingsForm[newsletters][\${rowIndex}][groupJoin]" placeholder="Join group ID" style="width: 20%; margin-left:5px;" />
                <input type="text" class="form-control" name="SettingsForm[newsletters][\${rowIndex}][groupLeave]" placeholder="Leave group ID" style="width: 20%; margin-left:5px;" />
                <button type="button" class="remove-newsletter-row btn btn-danger btn-sm" style="margin-left:5px;">&times;</button>
            </div>`;
        container.append(html);
        rowIndex++;
    }

    // ✅ Global click handler for all remove buttons (existing + new)
    $(document).on('click', '.remove-newsletter-row', function() {
        $(this).closest('.newsletter-row').remove();
    });

    // ✅ Add row button
    $('#add-newsletter-btn').on('click', function() {
        addNewsletterRow();
    });
JS);
?>
