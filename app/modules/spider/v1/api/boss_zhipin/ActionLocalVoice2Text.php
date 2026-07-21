<?php

namespace modules\spider\v1\api\boss_zhipin;

use hammer\web\ActionBase;


class ActionLocalVoice2Text extends ActionBase
{
    protected $inputType = 'REQUEST';

    /**
     * @throws \Exception
     */
    public function run()
    {
        // 从 $_FILES 中获取上传的音频文件
        $audioFile = $_FILES['audio'] ?? null;
        if (!$audioFile || $audioFile['error'] !== UPLOAD_ERR_OK)
        {
            $errorCode = $audioFile['error'] ?? -1;
            $this->setMsg("音频文件上传失败, error: {$errorCode}")->outError();
        }

        $localApiUrl = 'http://localhost:8212/transcribe';

        // 使用 curl 上传文件到本地转写服务
        $ch   = curl_init();
        $file = $audioFile['tmp_name'];
        //  $file='/mnt/e/wsl/qwen_env/1.wav';//奇怪 莫名其妙的就不行了
        curl_setopt_array($ch, [
            CURLOPT_URL            => $localApiUrl,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => [
                'file' => curl_file_create($file, mime_content_type($file) ?: 'audio/wav', $audioFile['name'] ?: 'voice.wav'),
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 120,
        ]);
        $body     = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $info     = curl_getinfo($ch);
        curl_close($ch);

        if ($body === false || $httpCode !== 200)
        {
            @unlink($audioFile['tmp_name']);
            $this->setMsg("本地语音识别失败, httpCode: {$httpCode}")->outError();
        }

        $result = json_decode($body, true);

        $text = '';
        if (json_last_error() === JSON_ERROR_NONE && isset($result['text']))
        {
            $text = trim($result['text']);
        }

        @unlink($audioFile['tmp_name']);

        return ['text' => $text, 'info' => $info, 'file' => $_FILES];
    }

    public function isDebug()
    {
        return true;
    }
}
