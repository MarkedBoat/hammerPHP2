<?php

namespace modules\dev\v1\api\KL_PC\xx\op\model;


use hammer\sys\Sys;
use models\ext\tool\CLIStrFormatter;
use models\ext\tool\Printer;

class TaskExpect
{

    public FileVideoInfo $videoInfoObj;
    public LsCmdFileinfo $lsCmdFileObj;
    private Printer      $printer;
    const int MIN_RATE_K = 1600;
    const int MIN_FPS    = 24;
    const int MIN_HEIGHT = 720;
    const int MAX_HEIGHT = 720;

    private array $EXPECT_TAG2CFG_KV  = [
        'NO_MOSAIC' => [
            // 'kws'    => 'tokyo,1pon,加勒比,carib,店长推荐,MKD,heyzo,hey,一本道,CWP,x-art,xart,ppv,kin8,MKBD,FC2,CWPBD,Blacked,',
            'kws'    => 'tokyo,1pon,加勒比,carib,店长推荐,MKD,heyzo,hey,一本道,CWP,x-art,xart,ppv,kin8,MKBD,CWPBD,',
            'pregs'  => ['/n\d+/isU'],
            'expect' => ['height' => 720, 'fps' => 30, 'rate' => 3000],
        ],
        'HEIGHT'    => [
            'kws'    => '',
            'pregs'  => ['/dst1080p/isU'],
            'expect' => ['height' => 1080, 'fps' => 30, 'rate' => 3000],
        ],
        'DOC'       => [
            'kws'    => '_doc_',
            'pregs'  => [],
            'expect' => ['height' => 1080, 'fps' => 12, 'rate' => 3000],
        ],
    ];
    private array $KEEP_SRC_VIDEO_KWS = ['黑丝', '天使', '星宫', '葵', '夏目', '二宫', '野野', '浦暖', 'ol', '本庄'];
    private array $STD_FPS            = [12, 15, 18, 24, 30];

    public function __construct()
    {

        // var_dump($ids,$taskMs);


        foreach ($this->EXPECT_TAG2CFG_KV as $tag => $cfg)
        {
            $this->EXPECT_TAG2CFG_KV[$tag]['kws'] = array_filter(array_map(function ($str) { return strtolower(trim($str)); }, explode(',', $cfg['kws'])), function ($str) { return strlen($str) > 0; });
        }
        $this->KEEP_SRC_VIDEO_KWS = array_merge($this->KEEP_SRC_VIDEO_KWS, $this->EXPECT_TAG2CFG_KV['NO_MOSAIC']['kws']);
        $this->KEEP_SRC_VIDEO_KWS = array_merge($this->KEEP_SRC_VIDEO_KWS, $this->EXPECT_TAG2CFG_KV['DOC']['kws']);
        $this->KEEP_SRC_VIDEO_KWS = array_unique($this->KEEP_SRC_VIDEO_KWS);


        // $this->src = $srcVideo;
    }

    public function ignoreKwsMatch()
    {
        //关闭自动匹配分辨率
        $this->EXPECT_TAG2CFG_KV = [];
    }

    /**
     * @param Printer $printer
     * @return static
     */
    public function setPrinter(Printer $printer)
    {
        $this->printer = $printer;
        return $this;
    }

    /**
     * @param FileVideoInfo $videoInfoObj
     * @return static
     */
    public function loadVideoFileInfo(FileVideoInfo $videoInfoObj)
    {
        $this->videoInfoObj = $videoInfoObj;
        return $this;
    }

    /**
     * @param LsCmdFileinfo $lsCmdVideoInfo
     * @return static
     */
    public function loadLsCmdFileinfo(LsCmdFileinfo $lsCmdVideoInfo)
    {
        $this->lsCmdFileObj = $lsCmdVideoInfo;
        return $this;
    }

    public function getDstInfo()
    {
        return ['code' => $this->dstCode, 'height' => $this->dstMaxHeight, 'rate' => $this->dstMaxRateK, 'fps' => $this->dstFps];
    }

    /**
     * @param $msg
     * @return false|TaskSettingBox
     * @throws \Exception
     */
    public function getAutoExpectTaskSettingBox(&$msg = [])
    {
        $isError = false;
        if (empty($this->lsCmdFileObj))
        {
            $msg[]   = 'ERROR lsCmdFileinfo empty';
            $isError = true;
        }
        if (empty($this->videoInfoObj))
        {
            $msg[]   = 'ERROR videoInfoObj empty';
            $isError = true;
        }
        if ($isError)
        {
            return false;
        }
        Sys::app()->initPrinter();
        $printer = $this->printer;

        $videoInfoObj = $this->videoInfoObj;
        $lsCmdFileObj = $this->lsCmdFileObj;

        $videoInfoObj->setPrinter($printer);


        $this->printer->setOutputState(true);


        $this->printer->newTabEcho('make dst config', "make task config  准备获取videos info\n");


        $msgs = ['ERROR initVideo失败'];
        if ($videoInfoObj->initVideoInfo($msgs) === false)
        {
            echo CLIStrFormatter::error($msgs);
            $this->printer->tabEcho('error  ,skip');
            return false;
        }


        $this->printer->tabEcho("\nSRC:width:{$videoInfoObj->width} height:{$videoInfoObj->height} fps:{$videoInfoObj->fps} bit_rate:{$videoInfoObj->rateK} duration:{$videoInfoObj->durationSeconds}");
        if (empty($videoInfoObj->fps) || empty($videoInfoObj->width) || empty($videoInfoObj->height) || empty($videoInfoObj->rateK))
        {
            $this->printer->tabEcho(CLIStrFormatter::error("ERROR video_info_lost\n"));

            return false;
        }

        $taskSettingBox = new TaskSettingBox($videoInfoObj);

        $isPhone = $videoInfoObj->isPhone;


        foreach ($this->KEEP_SRC_VIDEO_KWS as $kw)
        {
            if (strstr($taskSettingBox->src->baseName, $kw))
            {
                $taskSettingBox->delSrc = false;
                break;
            }
        }


        $this->printer->newTabEcho('auto_dst_config', 'auto dst config');
        /**   $expectFps int 预期fps */
        $expectFps = self::MIN_FPS;
        /**   $MIN_RATE_K int 预期rate k */
        $expectRateK = self::MIN_RATE_K;
        /**   $MIN_HEIGHT int 预期高度 */
        $expectHeight = self::MIN_HEIGHT;


        $flags = ['copy' => true];

        if ($expectRateK > $videoInfoObj->rateK)
        {
            if ($videoInfoObj->fps < self::MIN_FPS)
            {
                $taskSettingBox->dstFlag = 'ffCopy';
                $taskSettingBox->dstFlag = 'low2';

                if (in_array($videoInfoObj->fps, $this->STD_FPS))
                {
                    $taskSettingBox->dstFps = $videoInfoObj->fps;
                }
                else
                {
                    foreach ($this->STD_FPS as $fps)
                    {
                        if ($videoInfoObj->fps < $fps)
                        {
                            break;
                        }
                        $taskSettingBox->dstFps = $fps;
                    }

                }
                $taskSettingBox->dstMaxRateK  = intval($videoInfoObj->rateK * 0.9);
                $taskSettingBox->dstMaxHeight = $videoInfoObj->height;
                $this->printer->tabEcho("rate  video < DEFAULT && fps  video < MIN   {$videoInfoObj->rateK}  < {$expectRateK} / {$videoInfoObj->fps} < {$MIN_FPS} ,so dst flag is [ low2 ]");


            }
            else
            {
                $taskSettingBox->dstMaxRateK = intval($videoInfoObj->rateK * 0.9);
                $this->printer->tabEcho("rate  video < DEFAULT && fps  video > MIN  {$videoInfoObj->rateK}  < {$expectRateK}  / {$videoInfoObj->fps} > {$MIN_FPS} ,so dst rate is [ 9/10 *video.rate ]");

            }
        }
        else
        {
            $taskSettingBox->dstMaxRateK = $expectRateK;
            $this->printer->tabEcho("rate  video > DEFAULT   {$videoInfoObj->rateK}  > {$expectRateK} ,so dst rate is [ DEFAULT ]");

        }


        if ($taskSettingBox->dstFlag === 'ffCopy')
        {

        }
        else if ($taskSettingBox->dstFlag === 'low2')
        {
            $flags['copy'] = false;
        }
        else
        {
            $flags = ['copy' => false];
            if ($isPhone)
            {
                if ($videoInfoObj->height >= self::MAX_HEIGHT)
                {
                    $taskSettingBox->dstMaxHeight = self::MAX_HEIGHT;
                }
                else
                {
                    if ($videoInfoObj->height >= self::MIN_HEIGHT)
                    {
                        $taskSettingBox->dstMaxHeight = self::MIN_HEIGHT;
                    }
                }
            }
            else
            {
                if ($videoInfoObj->height >= $expectHeight)
                {
                    $taskSettingBox->dstMaxHeight = $expectHeight;
                }
                else if ($videoInfoObj->height >= self::MIN_HEIGHT)
                {
                    $taskSettingBox->dstMaxHeight = self::MIN_HEIGHT;
                }
            }

            if ($videoInfoObj->fps >= $expectFps)
            {
                $taskSettingBox->dstFps = $expectFps;
            }
            else
            {
                if ($videoInfoObj->fps > 24 && $videoInfoObj->fps < 30)
                {
                    $taskSettingBox->dstFps = 24;
                }
                else
                {
                    $taskSettingBox->dstFps = $videoInfoObj->fps;
                }
            }


            if ($expectHeight > $videoInfoObj->height)
            {
                $taskSettingBox->dstMaxHeight = $videoInfoObj->height;
            }

        }



        //  $ts1   = time();
        //   $date1 = date('Y-m-d H：i：s', $ts1);

        //  $this->printer->tabEcho('DST:' . json_encode($taskSettingBox->getDstInfo(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return $taskSettingBox;
        //  return $taskInfo;


    }

}