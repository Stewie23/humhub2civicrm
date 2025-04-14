<?php

namespace humhub\modules\humhub2civicrm\models;

use Yii;
use yii\base\Model;

class SettingsForm extends Model
{
    /**
     * Civi Api Settings + ExtendendContactMatcher
     */
    public $apiUrl;
    public $apiKey;
    public $siteKey;
    public $contactManagerProfile;

    /**
     * Settings for Contact Deletion
     */
    public $deleteAction; // 'soft', 'hard', or 'anonymize'
    public $deletedGroupId; // optional, used if deleteAction == 'soft'

    
    /**
     * Default Field Information to bring to civi
     */
    public $standardFields = [];

    /**
     * Dynamic newsletter configurations:
     * Each item = ['field' => 'infobrief_optin', 'groupJoin' => '111', 'groupLeave' => '112']
     */
    public $newsletters = [];

    public function rules()
    {
        return [
            [['apiUrl', 'apiKey', 'siteKey','contactManagerProfile','deleteAction', 'deletedGroupId'], 'string'],
            [['apiUrl'], 'url'],
            ['deleteAction', 'in', 'range' => ['soft', 'hard', 'anonymize']],
            [['newsletters', 'standardFields'], 'safe'],
        ];
    }

    public function loadDefaults()
    {
        $settings = Yii::$app->getModule('humhub2civicrm')->settings;

        $this->apiUrl = $settings->get('apiUrl');
        $this->apiKey = $settings->get('apiKey');
        $this->siteKey = $settings->get('siteKey');
        $this->contactManagerProfile = $settings->get('contactManagerProfile');

        $this->deleteAction = $settings->get('deleteAction') ?: 'soft';
        $this->deletedGroupId = $settings->get('deletedGroupId');

        $fields = $settings->get('standardFields');
        $this->standardFields = $fields ? json_decode($fields, true) : [];

        $json = $settings->get('newsletters');
        $this->newsletters = $json ? json_decode($json, true) : [];

        // Ensure at least one empty row for UI
        if (empty($this->newsletters)) {
            $this->newsletters[] = ['field' => '', 'groupJoin' => '', 'groupLeave' => ''];
        }
    }

    public function save()
    {
        $settings = Yii::$app->getModule('humhub2civicrm')->settings;

        $settings->set('apiUrl', $this->apiUrl);
        $settings->set('apiKey', $this->apiKey);
        $settings->set('siteKey', $this->siteKey);
        $settings->set('contactManagerProfile', $this->contactManagerProfile);

        $settings->set('standardFields', json_encode(array_values($this->standardFields)));

        $settings->set('deleteAction', $this->deleteAction);
        $settings->set('deletedGroupId', $this->deletedGroupId);

        // Clean and encode newsletter mappings
        $cleaned = array_filter($this->newsletters, function ($entry) {
            return is_array($entry) && !empty($entry['field']);
        });
        
        $settings->set('newsletters', json_encode(array_values($cleaned)));
    }

    public function testConnection()
{
    $client = new \yii\httpclient\Client(['transport' => 'yii\httpclient\CurlTransport']);

    $params = [
        'entity'   => 'Contact',
        'action'   => 'get',
        'email'    => 'nobody@nowhere.test',  // dummy email
        'api_key'  => $this->apiKey,
        'key'      => $this->siteKey,
        'json'     => 1,
    ];

    try {
        $response = $client->createRequest()
            ->setMethod('POST')
            ->setUrl($this->apiUrl)
            ->setData($params)
            ->addHeaders(['Accept' => 'application/json'])
            ->send();

        if (!$response->isOk) {
            return 'CiviCRM did not respond correctly.';
        }

        $data = json_decode($response->content, true);
        if (!empty($data['is_error'])) {
            return 'CiviCRM API error: ' . ($data['error_message'] ?? 'Unknown error');
        }

        return true;
    } catch (\Throwable $e) {
        return 'Connection error: ' . $e->getMessage();
    }
}
}