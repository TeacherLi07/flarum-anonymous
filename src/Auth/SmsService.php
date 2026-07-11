<?php

/*
 * This file is part of flarum-anonymous.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace TeacherLi07\Anonymous\Auth;

use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Cache\Repository as Cache;

class SmsService
{
    protected $settings;
    protected $cache;

    public function __construct(SettingsRepositoryInterface $settings, Cache $cache)
    {
        $this->settings = $settings;
        $this->cache = $cache;
    }

    public function send(string $phone): void
    {
        if ($this->cache->has('sms:cooldown:' . $phone)) {
            throw new \RuntimeException('Send too frequently, please wait.');
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->cache->put('sms:code:' . $phone, $code, 300);
        $this->cache->put('sms:cooldown:' . $phone, true, 60);

        $this->sendViaAliyun($phone, $code);
    }

    public function verify(string $phone, string $code): bool
    {
        $cached = $this->cache->pull('sms:code:' . $phone);

        return $cached !== null && $cached === $code;
    }

    protected function sendViaAliyun(string $phone, string $code): void
    {
        $accessKeyId = $this->settings->get('anonymous.sms_access_key_id');
        $accessSecret = $this->settings->get('anonymous.sms_access_secret');
        $signName = $this->settings->get('anonymous.sms_sign_name');
        $templateCode = $this->settings->get('anonymous.sms_template_code');

        if (!$accessKeyId || !$accessSecret || !$signName || !$templateCode) {
            return;
        }

        $params = [
            'PhoneNumbers'  => $phone,
            'SignName'      => $signName,
            'TemplateCode'  => $templateCode,
            'TemplateParam' => json_encode(['code' => $code]),
        ];

        // Use Aliyun SMS API via HTTP request (no SDK dependency)
        // Documentation: https://help.aliyun.com/document_detail/101414.html
        $this->requestAliyunApi('SendSms', $params, $accessKeyId, $accessSecret);
    }

    protected function requestAliyunApi(string $action, array $params, string $accessKeyId, string $accessSecret): void
    {
        $params['AccessKeyId'] = $accessKeyId;
        $params['Action'] = $action;
        $params['Format'] = 'JSON';
        $params['SignatureMethod'] = 'HMAC-SHA1';
        $params['SignatureNonce'] = uniqid();
        $params['SignatureVersion'] = '1.0';
        $params['Timestamp'] = gmdate('Y-m-d\TH:i:s\Z');
        $params['Version'] = '2017-05-25';

        ksort($params);

        $query = [];
        foreach ($params as $key => $value) {
            $query[] = rawurlencode($key) . '=' . rawurlencode($value);
        }
        $queryString = implode('&', $query);

        $stringToSign = 'POST&' . rawurlencode('/') . '&' . rawurlencode($queryString);

        $signature = base64_encode(hash_hmac('sha1', $stringToSign, $accessSecret . '&', true));
        $signature = rawurlencode($signature);

        $url = 'https://dysmsapi.aliyuncs.com/?Signature=' . $signature . '&' . $queryString;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_exec($ch);
        curl_close($ch);
    }
}
