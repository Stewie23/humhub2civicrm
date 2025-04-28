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
        <?= $form->field($model, 'contactManagerProfile')->textInput(['maxlength' => true]) ?>

        <hr>
        <label>Deleted User</label>
        <p class="help-block" style="margin-top: -10px; margin-bottom: 15px;">
            Choose how to propagate user deletions from HumHub to CiviCRM.<br><br>
            <strong>Soft delete</strong> (default): Removes the user from all CiviCRM groups related to HumHub and adds them to a dedicated “Deleted Users” group for manual review.<br>
            <strong>Anonymize</strong>: Replaces personal information (name, email, phone) in CiviCRM, but keeps the contact for historical or reporting purposes.<br>
            <strong>Hard delete</strong>: Permanently deletes the contact from CiviCRM — use only if you're sure this data is no longer needed.
        </p>
        <?= $form->field($model, 'deleteAction')->dropDownList([
            'soft' => 'Soft delete (move contact to group)',
            'hard' => 'Hard delete (remove contact from CiviCRM)',
            'anonymize' => 'Anonymize contact (GDPR-safe)',
        ]) ?>

        <?= $form->field($model, 'deletedGroupId')->textInput()->hint('CiviCRM Group ID to assign when using soft delete.') ?>

        <hr>
        <label>Standard Profile Fields</label>
        <p class="help-block" style="margin-top: -10px; margin-bottom: 15px;">
            Select which standard profile fields should be included when sending user data to CiviCRM.
            These fields will be passed to the contact matcher during creation or update.
        </p>

        <?= $form->field($model, 'standardFields')->checkboxList([
            'firstname' => 'First Name',
            'lastname' => 'Last Name',
            'gender' => 'Gender',
            'phone_work' => 'Phone',
        ]) ?>

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
