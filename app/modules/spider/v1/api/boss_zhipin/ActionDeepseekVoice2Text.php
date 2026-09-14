<?php

namespace modules\spider\v1\api\boss_zhipin;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use hammer\web\ActionBase;


class ActionDeepseekVoice2Text extends ActionBase
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

        // 获取 DEEPSEEK_API_KEY
        $apiKey = getenv('DEEPSEEK_API_KEY');
        $apiKey='sk-59b8ed4bd3ee4fddb31f86222afff87c';
        if (empty($apiKey)) {
            $this->setMsg("DEEPSEEK_API_KEY 未设置")->outError();
        }

        try {
            $client = new Client([
                'timeout' => 60,
            ]);

            // 使用 GuzzleHttp 的 multipart 上传，避免手动构造 multipart body
            $response = $client->request('POST',
                //'https://api.deepseek.com/v1/audio/transcriptions',
                //'https://api.deepseek.com/v1/audio/transcribe',
                'https://api.deepseek.com/v1/speech/recognize',

                [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                ],
                'multipart' => [
                    [
                        'name'     => 'file',
                        'contents' => fopen($audioFile['tmp_name'], 'r'),
                        'filename' => $audioFile['name'] ?: 'voice.webm',
                    ],
                    [
                        'name'     => 'model',
                        'contents' => 'whisper-1',
                    ],
                    [
                        'name'     => 'language',
                        'contents' => 'zh',
                    ],
                    [
                        'name'     => 'response_format',
                        'contents' => 'json',
                    ],
                ],
            ]);

            $body = (string)$response->getBody();
            $result = json_decode($body, true);

            $text = '';
            if (json_last_error() === JSON_ERROR_NONE && isset($result['text'])) {
                $text = trim($result['text']);
            }

            @unlink($audioFile['tmp_name']);

            return ['text' => $text];
        } catch (GuzzleException $e) {
            @unlink($audioFile['tmp_name']);
            $this->setMsg("DeepSeek 语音识别失败: " . $e->getMessage())->outError();
        } catch (\Exception $e) {
            @unlink($audioFile['tmp_name']);
            $this->setMsg("DeepSeek 语音识别失败: " . $e->getMessage())->outError();
        }

        return ['text' => ''];
    }

    public function isDebug()
    {
        return true;
    }
}
