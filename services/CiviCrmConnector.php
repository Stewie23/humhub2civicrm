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
        $profileName = $module->settings->get('contactManagerProfile');

        if (!$url || !$apiKey || !$siteKey) {
            Yii::error('CiviCrmConnector: Missing API configuration.', 'humhub\modules\humhub2civicrm');
            return null;
        }

        $client = new Client(['transport' => 'yii\httpclient\CurlTransport']);

        $payload = [
            'entity' => 'Contact',
            'action' => 'getorcreate',
            'json' => 1,
            'xcm_profile' => $profileName,
            'email' => $email,
            'api_key' => $apiKey,
            'key' => $siteKey,
        ];
        
        // Add standard fields based on config
        $selectedFields = $module->settings->get('standardFields');
        $fields = $selectedFields ? json_decode($selectedFields, true) : [];
        
        $fieldMap = [
            'firstname' => 'first_name',
            'lastname' => 'last_name',
            'phone_work' => 'phone',
            'gender' => 'gender_id',
        ];
        
        $profile = $user->profile;

        $profile = $user->profile;

        foreach ($fields as $field) {
            $civiKey = $fieldMap[$field] ?? $field;
            $rawValue = $profile->getAttribute($field);
            Yii::info("Field [$field] raw value: " . var_export($rawValue, true), 'humhub\modules\humhub2civicrm');

        
            // Special mapping for gender keys
            if ($field === 'gender') {
                $value = match ($rawValue) {
                    'female' => 1,
                    'male' => 2,
                    'diverse' => 3,
                    default => null
                };
            } else {
                // General sanitization for user input fields
                $value = trim(strip_tags((string) $rawValue));
            }
        
            if (!empty($value)) {
                $payload[$civiKey] = $value;
            }
        }
        

        Yii::info('Sending CiviCRM POST payload: ' . json_encode($payload), 'humhub\modules\humhub2civicrm');

        $response = $client->createRequest()
            ->setMethod('POST')
            ->setUrl($url)
            ->setData($payload)
            ->addHeaders([
                'Accept' => 'application/json',
                'User-Agent' => 'HumHubCiviBridge',
            ])
            ->send();


        if ($response->isOk) {
            $data = json_decode($response->content, true);
            
            if (!is_array($data)) {
                Yii::error('CiviCRM response was not valid JSON: ' . $response->content, 'humhub\modules\humhub2civicrm');
                return $response;
            }
            
            if (!empty($data['is_error'])) {
                Yii::error('CiviCRM application error: ' . $response->content, 'humhub\modules\humhub2civicrm');
            } else {
                Yii::info('CiviCRM success: ' . $response->content, 'humhub\modules\humhub2civicrm');
            
            $contactId = $data['id'] ?? null;
                if (!$contactId) {
                    Yii::warning('CiviCRM response missing contact ID.', 'humhub\modules\humhub2civicrm');
                } else {
                    self::updateGroups($contactId, $user);
                }
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
        $email = $user->email;

        if (empty($email)) {
            Yii::error("User {$user->id} has no email address — cannot update mailing subscriptions.", 'humhub\modules\humhub2civicrm');
            return;
        }
    
        $rawConfig = $module->settings->get('newsletters');
        $newsletterConfig = $rawConfig ? json_decode($rawConfig, true) : [];
    
        if (empty($newsletterConfig)) {
            Yii::error("No newsletter configuration found in settings.", 'humhub\modules\humhub2civicrm');
            return;
        }
    
        foreach ($newsletterConfig as $entry) {
            $field = $entry['field'] ?? null;
            $groupJoin = $entry['groupJoin'] ?? null;
 
    
            if (!$field || !$groupJoin) {
                Yii::error("Skipping invalid config entry: " . var_export($entry, true), 'humhub\modules\humhub2civicrm');
                continue;
            }

            // 🔍 Check if profile field exists
            if (!isset($profile->{$field})) {
                Yii::error("HumHub profile field [$field] is missing or unset — check field name in module config.", 'humhub\modules\humhub2civicrm');
                continue;
            }
   
            $value = $profile->{$field} ?? null;
            Yii::info("Newsletter [$field]: " . var_export($value, true), 'humhub\modules\humhub2civicrm');
    
            if ($value) {
                // Check if already subscribed
                if (self::getGroupMembership($contactId, $groupJoin)) {
                    Yii::info("User [$email] already subscribed to group [$groupJoin], skipping subscription.", 'humhub\modules\humhub2civicrm');
                    return;
                    }
                // if not add to mailing subscription    
                Yii::info("Adding contact $contactId to group $groupJoin (field $field active)", 'humhub\modules\humhub2civicrm');
                self::sendMailingSubscription($email, $groupJoin);
            } else {
                Yii::info("Removing contact $contactId from group $groupJoin  (field $field inactive)", 'humhub\modules\humhub2civicrm');
                self::removeFromGroup($contactId, $groupJoin );
            }
        }
    }


    public static function getGroupMembership($contactId, $groupId)
    {
        $module = Yii::$app->getModule('humhub2civicrm');
        $url = $module->settings->get('apiUrl');
        $apiKey = $module->settings->get('apiKey');
        $siteKey = $module->settings->get('siteKey');

        $client = new Client(['transport' => 'yii\httpclient\CurlTransport']);

        $params = [
            'entity'      => 'GroupContact',
            'action'      => 'get',
            'contact_id'  => $contactId,
            'group_id'    => $groupId,
            'status'      => 'Added',
            'sequential'  => 1,
            'api_key'     => $apiKey,
            'key'         => $siteKey,
            'json'        => 1,
        ];

        Yii::info("CiviCRM GroupContact.get params: " . var_export($params, true), 'humhub\modules\humhub2civicrm');

        $response = $client->createRequest()
            ->setMethod('POST')
            ->setUrl($url)
            ->setData($params)
            ->addHeaders([
                'Accept' => 'application/json',
                'User-Agent' => 'HumHubCiviBridge',
            ])
            ->send();

        if (!$response->isOk) {
            Yii::error('Failed to query GroupContact.get: ' . $response->content, 'humhub\modules\humhub2civicrm');
            return false;
        }

        $data = json_decode($response->content, true);

        if (isset($data['count']) && $data['count'] > 0) {
            Yii::info("Contact $contactId is ALREADY a member of group $groupId", 'humhub\modules\humhub2civicrm');
            return true;
        } else {
            Yii::info("Contact $contactId is NOT a member of group $groupId", 'humhub\modules\humhub2civicrm');
            return false;
        }
    }

    private static function sendMailingSubscription($email, $groupId)
    {
        $module = Yii::$app->getModule('humhub2civicrm');
        $url = $module->settings->get('apiUrl');
        $apiKey = $module->settings->get('apiKey');
        $siteKey = $module->settings->get('siteKey');
    
        $client = new Client(['transport' => 'yii\httpclient\CurlTransport']);
    
        $params = [
            'entity'   => 'MailingEventSubscribe',
            'action'   => 'create',
            'group_id' => $groupId,
            'email'    => $email,
            'api_key'  => $apiKey,
            'key'      => $siteKey,
            'json'     => 1,
        ];
    
        Yii::info("CiviCRM MailingEventSubscribe.create POST data: " . var_export($params, true), 'humhub\modules\humhub2civicrm');
    
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
            Yii::info('Mailing subscription response: ' . $response->content, 'humhub\modules\humhub2civicrm');
        } else {
            Yii::info('Mailing subscription failed: ' . $response->content, 'humhub\modules\humhub2civicrm');
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
    
        Yii::info("CiviCRM GroupContact.create POST data: " . var_export($params, true), 'humhub\modules\humhub2civicrm');
    
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
            Yii::info('Group assignment response: ' . $response->content, 'humhub\modules\humhub2civicrm');
        } else {
            Yii::info('Group assignment failed: ' . $response->content, 'humhub\modules\humhub2civicrm');
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

        Yii::info("CiviCRM GroupContact.delete POST data: " . var_export($params, true), 'humhub\modules\humhub2civicrm');

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