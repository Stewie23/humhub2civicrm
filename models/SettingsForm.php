<?php

namespace humhub\modules\humhub2civicrm\models;

use Yii;
use yii\base\Model;

class SettingsForm extends Model
{
    public $apiUrl;
    public $apiKey;
    public $siteKey;
    public $contactManagerProfile;

    /**
     * Dynamic newsletter configurations:
     * Each item = ['field' => 'infobrief_optin', 'groupJoin' => '111', 'groupLeave' => '112']
     */
    public $newsletters = [];

    public function rules()
    {
        return [
            [['apiUrl', 'apiKey', 'siteKey','contactManagerProfile'], 'string'],
            [['apiUrl'], 'url'],
            ['newsletters', 'safe'], // We'll validate each row manually
        ];
    }

    public function loadDefaults()
    {
        $settings = Yii::$app->getModule('humhub2civicrm')->settings;

        $this->apiUrl = $settings->get('apiUrl');
        $this->apiKey = $settings->get('apiKey');
        $this->siteKey = $settings->get('siteKey');
        $this->contactManagerProfile = $settings->get('contactManagerProfile');

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

        // Clean and encode newsletter mappings
        $cleaned = array_filter($this->newsletters, function ($entry) {
            return !empty($entry['field']);
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