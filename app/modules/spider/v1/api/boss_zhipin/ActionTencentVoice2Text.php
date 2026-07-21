<?php

namespace modules\spider\v1\api\boss_zhipin;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use hammer\web\ActionBase;


class ActionTencentVoice2Text extends ActionBase
{
    protected $inputType = 'REQUEST';

    /**
     * @throws \Exception
     */
    public function run()
    {
        // 从 $_FILES 中获取上传的音频文件
        $audioFile = $_FILES['audio'] ?? null;
        if (!$audioFile || $audioFile['error'] !== UPLOAD_ERR_OK) {
            $errorCode = $audioFile['error'] ?? -1;
            $this->setMsg("音频文件上传失败, error: {$errorCode}")->outError();
        }

        // 获取腾讯云密钥
        $secretId  = getenv('TENCENT_SECRET_ID');
        $secretKey = getenv('TENCENT_SECRET_KEY');
        if (empty($secretId) || empty($secretKey)) {
            $this->setMsg("TENCENT_SECRET_ID 或 TENCENT_SECRET_KEY 未设置")->outError();
        }

        // 读取音频数据
        $audioData = file_get_contents($audioFile['tmp_name']);
        if ($audioData === false) {
            @unlink($audioFile['tmp_name']);
            $this->setMsg("读取音频文件失败")->outError();
        }
        $audioBase64 = base64_encode($audioData);
        $dataLen     = strlen($audioData);

        // 判断音频格式（wav / mp3 / webm）
        $ext = strtolower(pathinfo($audioFile['name'] ?? 'voice.webm', PATHINFO_EXTENSION));
        $voiceFormatMap = [
            'wav'  => 'wav',
            'mp3'  => 'mp3',
            'webm' => 'webm',
            'ogg'  => 'ogg',
            'amr'  => 'amr',
            'm4a'  => 'm4a',
        ];
        $voiceFormat = $voiceFormatMap[$ext] ?? 'webm';

        $host    = 'asr.tencentcloudapi.com';
        $service = 'asr';
        $region  = 'ap-guangzhou';
        $action  = 'SentenceRecognition';
        $version = '2019-06-14';
        $algorithm = 'TC3-HMAC-SHA256';
        $timestamp = time();
        $date      = gmdate('Y-m-d', $timestamp);

        // 1. 构建规范请求
        $httpRequestMethod = 'POST';
        $canonicalUri      = '/';
        $canonicalQueryStr = '';
        $canonicalHeaders  = "content-type:application/json\nhost:{$host}\nx-tc-action:{$action}\n";
        $signedHeaders     = 'content-type;host;x-tc-action';
        $payload = json_encode([
            'ProjectId'     => 0,
            'SubServiceType' => 2,
            'EngSerViceType' => '16k_zh',
            'SourceType'    => 1,
            'VoiceFormat'   => $voiceFormat,
            'UsrAudioKey'   => uniqid('audio_', true),
            'Data'          => $audioBase64,
            'DataLen'       => $dataLen,
        ]);
        $hashedPayload = hash('SHA256', $payload);

        $canonicalRequest = "{$httpRequestMethod}\n{$canonicalUri}\n{$canonicalQueryStr}\n{$canonicalHeaders}\n{$signedHeaders}\n{$hashedPayload}";

        // 2. 构建待签名字符串
        $credentialScope  = "{$date}/{$service}/tc3_request";
        $hashedCanonicalRequest = hash('SHA256', $canonicalRequest);
        $stringToSign = "{$algorithm}\n{$timestamp}\n{$credentialScope}\n{$hashedCanonicalRequest}";

        // 3. 计算签名
        $secretDate    = hash_hmac('SHA256', $date, 'TC3' . $secretKey, true);
        $secretService = hash_hmac('SHA256', $service, $secretDate, true);
        $secretSigning = hash_hmac('SHA256', 'tc3_request', $secretService, true);
        $signature     = hash_hmac('SHA256', $stringToSign, $secretSigning);

        // 4. 构建 Authorization
        $authorization = "{$algorithm} Credential={$secretId}/{$credentialScope}, SignedHeaders={$signedHeaders}, Signature={$signature}";

        try {
            $client = new Client(['timeout' => 30]);
            $response = $client->request('POST', "https://{$host}", [
                'headers' => [
                    'Authorization' => $authorization,
                    'Content-Type'  => 'application/json',
                    'Host'          => $host,
                    'X-TC-Action'   => $action,
                    'X-TC-Timestamp' => (string)$timestamp,
                    'X-TC-Version'  => $version,
                    'X-TC-Region'   => $region,
                ],
                'body' => $payload,
            ]);

            $body   = (string)$response->getBody();
            $result = json_decode($body, true);

            $text = '';
            if (isset($result['Response']['Result'])) {
                $text = trim($result['Response']['Result']);
            }

            @unlink($audioFile['tmp_name']);

            return ['text' => $text];
        } catch (GuzzleException $e) {
            @unlink($audioFile['tmp_name']);
            $this->setMsg("腾讯云语音识别失败: " . $e->getMessage())->outError();
        } catch (\Exception $e) {
            @unlink($audioFile['tmp_name']);
            $this->setMsg("腾讯云语音识别失败: " . $e->getMessage())->outError();
        }

        return ['text' => ''];
    }

    public function isDebug()
    {
        return true;
    }
}
