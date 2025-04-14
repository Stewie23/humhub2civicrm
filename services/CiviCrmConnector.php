<?php

namespace humhub\modules\humhub2civicrm\services;

use Yii;
use yii\httpclient\Client;

class CiviCrmConnector
{
    /**
     * Sends a contact to CiviCRM using the configured matcher profile.
     *
     * @param string $email
     * @return \yii\httpclient\Response|null
     */
    public static function sendProfile(string $email,$user)
    {
        $module = Yii::$app->getModule('humhub2civicrm');

        $url = $module->settings->get('apiUrl');
        $apiKey = $module->settings->get('apiKey');
        $siteKey = $module->settings->get('siteKey');

        if (!$url || !$apiKey || !$siteKey) {
            Yii::error('CiviCrmConnector: Missing API configuration.', 'humhub\modules\humhub2civicrm');
            return null;
        }

        $client = new Client(['transport' => 'yii\httpclient\CurlTransport']);

        // Build query string with json:= trick
        $queryString = http_build_query([
            'entity' => 'Contact',
            'action' => 'getorcreate',
            'json' => 1,
            'xcm_profile' => 'HumHubMatcher',
            'email' => $email,
            'api_key' => $apiKey,
            'key' => $siteKey,
        ]);

        // Add the special json:= key manually
        $finalUrl = rtrim($url, '?') . '?' . 'json:=' . '&' . $queryString;

        $response = $client->createRequest()
            ->setMethod('GET')
            ->setUrl($finalUrl)
            ->addHeaders([
                'Accept' => 'application/json',
                'User-Agent' => 'HumHubCiviBridge',
            ])
            ->send();

        if ($response->isOk) {
            $data = json_decode($response->content, true);
            
            if (isset($data['is_error']) && $data['is_error']) {
                Yii::error('CiviCRM application error: ' . $response->content, 'humhub\modules\humhub2civicrm');
            } else {
                Yii::error('CiviCRM success: ' . $response->content, 'humhub\modules\humhub2civicrm');
                $contactId = $data['id'];
                self::updateGroups($contactId, $user);
            }
        } else {
            Yii::error('CiviCRM HTTP error: ' . $response->content, 'humhub\modules\humhub2civicrm');
        }

        return $response;
    }

    private static function updateGroups($contactId, $user)
    {
        $profile = $user->profile;
        $module = Yii::$app->getModule('humhub2civicrm');
    
        $rawConfig = $module->settings->get('newsletters');
        $newsletterConfig = $rawConfig ? json_decode($rawConfig, true) : [];
    
        if (empty($newsletterConfig)) {
            Yii::error("No newsletter configuration found in settings.", 'humhub\modules\humhub2civicrm');
            return;
        }
    
        foreach ($newsletterConfig as $entry) {
            $field = $entry['field'] ?? null;
            $groupJoin = $entry['groupJoin'] ?? null;
            $groupLeave = $entry['groupLeave'] ?? null;
    
            if (!$field || !$groupJoin || !$groupLeave) {
                Yii::error("Skipping invalid config entry: " . var_export($entry, true), 'humhub\modules\humhub2civicrm');
                continue;
            }

            // 🔍 Check if profile field exists
            if (!isset($profile->{$field})) {
                Yii::error("HumHub profile field [$field] is missing or unset — check field name in module config.", 'humhub\modules\humhub2civicrm');
                continue;
            }
   
            $value = $profile->{$field} ?? null;
            Yii::error("Newsletter [$field]: " . var_export($value, true), 'humhub\modules\humhub2civicrm');
    
            if ($value) {
                Yii::error("Adding contact $contactId to group $groupJoin (field $field active)", 'humhub\modules\humhub2civicrm');
                self::sendGroupMembership($contactId, $groupJoin);
            } else {
                Yii::error("Removing contact $contactId from group $groupLeave (field $field inactive)", 'humhub\modules\humhub2civicrm');
                self::removeFromGroup($contactId, $groupLeave);
            }
        }
    }

    private static function sendGroupMembership($contactId, $groupId)
    {
        $module = Yii::$app->getModule('humhub2civicrm');
        $url = $module->settings->get('apiUrl');
        $apiKey = $module->settings->get('apiKey');
        $siteKey = $module->settings->get('siteKey');
    
        $client = new Client(['transport' => 'yii\httpclient\CurlTransport']);
    
        $params = [
            'entity' => 'GroupContact',
            'action' => 'create',
            'contact_id' => $contactId,
            'group_id' => $groupId,
            'api_key' => $apiKey,
            'key' => $siteKey,
            'json' => 1,
        ];
    
        Yii::error("CiviCRM GroupContact.create POST data: " . var_export($params, true), 'humhub\modules\humhub2civicrm');
    
        $response = $client->createRequest()
            ->setMethod('POST')
            ->setUrl($url)
            ->setData($params)
            ->addHeaders([
                'Accept' => 'application/json',
                'User-Agent' => 'HumHubCiviBridge',
            ])
            ->send();
    
        if ($response->isOk) {
            Yii::error('Group assignment response: ' . $response->content, 'humhub\modules\humhub2civicrm');
        } else {
            Yii::error('Group assignment failed: ' . $response->content, 'humhub\modules\humhub2civicrm');
        }
    }

    private static function removeFromGroup($contactId, $groupId)
    {
        $module = Yii::$app->getModule('humhub2civicrm');
        $url = $module->settings->get('apiUrl');
        $apiKey = $module->settings->get('apiKey');
        $siteKey = $module->settings->get('siteKey');

        $client = new Client(['transport' => 'yii\httpclient\CurlTransport']);

        $params = [
            'entity' => 'GroupContact',
            'action' => 'delete',
            'contact_id' => $contactId,
            'group_id' => $groupId,
            'api_key' => $apiKey,
            'key' => $siteKey,
            'json' => 1,
        ];

        Yii::error("CiviCRM GroupContact.delete POST data: " . var_export($params, true), 'humhub\modules\humhub2civicrm');

        $response = $client->createRequest()
        ->setMethod('POST')
        ->setUrl($url)
        ->setData($params)
        ->addHeaders([
            'Accept' => 'application/json',
            'User-Agent' => 'HumHubCiviBridge',
        ])
        ->send();

        if ($response->isOk) {
            Yii::error('Group removal response: ' . $response->content, 'humhub\modules\humhub2civicrm');
        } else {
        Yii::error('Group removal failed: ' . $response->content, 'humhub\modules\humhub2civicrm');
        }
    
    }

}