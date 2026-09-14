<?php

namespace modules\dev\v1\api\KL_PC\xx\op\model;


class TaskSettingBox
{


    public FileVideoInfo $src;
    public int           $dstFps       = 0;
    public int           $dstMaxHeight = 0;
    public int           $dstMaxRateK  = 0;
    public string        $dstCode      = '265';
    public string        $dstFlag      = '';
    public FileVideoInfo $dst;
    public bool          $delSrc       = false;

    public function __construct(FileVideoInfo $srcVideo)
    {
        $this->src = $srcVideo;
    }

    public function loadDstFile(FileVideoInfo $dstVideo)
    {
        $this->dst = $dstVideo;
    }

    /**
     * @return array
     */
    public function getDstInfo()
    {
        return ['code' => $this->dstCode, 'height' => $this->dstMaxHeight, 'rate' => $this->dstMaxRateK, 'fps' => $this->dstFps];
    }

    public function getOpenInfo()
    {
        return [
            'type'     => $this->dstCode,
            'height'   => $this->dstMaxHeight,
            'maxRateK' => "{$this->dstMaxRateK}K",
            'fps'      => $this->dstFps,
        ];
    }

    public function loadInfo($info)
    {
        $this->dstCode      = $info['type'];
        $this->dstMaxHeight = $info['height'];
        $this->dstMaxRateK  = intval(str_replace('K', '', $info['maxRateK']));
        $this->dstFps       = $info['fps'];
    }


}