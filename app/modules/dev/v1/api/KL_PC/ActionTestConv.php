<?php

namespace modules\dev\v1\api\KL_PC;

use hammer\web\ActionBase;
use models\ext\tool\SimpleSSE;


class ActionTestConv extends ActionBase
{
//后期静态绑定代替了

    public function run()
    {
        @ob_clean();

        $srcFile = '/mnt/f/tmp2/format/wait/php/Enkai enkai987Fansone 走马探花 Ysabella仙女降临超骚性技口技吓吓叫AV导演_恩凯.mp4';
        $dstFIle = '/mnt/f/tmp2/format/wait/php/FFOutput_tmp/Enkai enkai987Fansone 走马探花 Ysabella仙女降临超骚性技口技吓吓叫AV导演_恩凯 1548 _NC_.mp4';
        if (file_exists($dstFIle))
        {
            @unlink($dstFIle);
        }
        $cmd = "ffmpeg -hide_banner -i '{$srcFile}' -ss 24 -to 2917 \
-map 0:v:0 -c:v hevc_nvenc -crf 0 -maxrate 1600k -bufsize 4000k \
-vf \"scale = -2:if (gte(ih\,720)\,720\,ih),fps = 24\" -preset p7 \
-map 0:a? -c:a aac -ac 2 -b:a 192k \
-map 0:s? -c:s mov_text \
-map_metadata 0 -movflags use_metadata_tags \
'{$dstFIle}'";

        // ffmpeg_progress_correct.php




        $command = $cmd;









        $sse = new SimpleSSE();

        $sse->send("\n调用命令，需要手动\n");
        $sse->send("\nmake conv tasks\n");
        $sse->send("解决不了ffmpeg的输出问题，不成熟");
        $sse->end();
        die;
        $srcFile = '/mnt/f/tmp2/format/wait/php/Enkai enkai987Fansone 走马探花 Ysabella仙女降临超骚性技口技吓吓叫AV导演_恩凯.mp4';
        $dstFIle = '/mnt/f/tmp2/format/wait/php/FFOutput_tmp/Enkai enkai987Fansone 走马探花 Ysabella仙女降临超骚性技口技吓吓叫AV导演_恩凯 1548 _NC_.mp4';
        if (file_exists($dstFIle))
        {
            @unlink($dstFIle);
        }
        $cmd = "ffmpeg -hide_banner -i '{$srcFile}' -ss 24 -to 2917 \
-map 0:v:0 -c:v hevc_nvenc -crf 0 -maxrate 1600k -bufsize 4000k \
-vf \"scale = -2:if (gte(ih\,720)\,720\,ih),fps = 24\" -preset p7 \
-map 0:a? -c:a aac -ac 2 -b:a 192k \
-map 0:s? -c:s mov_text \
-map_metadata 0 -movflags use_metadata_tags \
'{$dstFIle}'";

       // $sse->simpleExecCmd("stdbuf -i0 -o0 -e0 sudo ffmpeg -hide_banner -i '/mnt/f/tmp2/format/wait/php/Enkai enkai987Fansone 走马探花 Ysabella仙女降临超骚性技口技吓吓叫AV导演_恩凯.mp4' -ss 24 -to 2917 -map 0:v:0 -c:v hevc_nvenc -crf 0 -maxrate 1600k -bufsize 4000k -vf 'scale=-2:if(gte(ih\,720)\,720\,ih),fps=24' -preset p7 -map 0:a? -c:a aac -ac 2 -b:a 192k -map 0:s? -c:s mov_text -map_metadata 0 -movflags use_metadata_tags '/mnt/f/tmp2/format/wait/php/FFOutput_tmp/Enkai enkai987Fansone 走马探花 Ysabella仙女降临超骚性技口技吓吓叫AV导演_恩凯 1548 _NC_.mp4'  2>&1");

        //$sse->simpleExecCmd("script -q -f -c \"sudo ffmpeg -hide_banner -i '/mnt/f/tmp2/format/wait/php/Enkai enkai987Fansone 走马探花 Ysabella仙女降临超骚性技口技吓吓叫AV导演_恩凯.mp4' -ss 24 -to 2917 -map 0:v:0 -c:v hevc_nvenc -crf 0 -maxrate 1600k -bufsize 4000k -vf 'scale=-2:if(gte(ih\,720)\,720\,ih),fps=24' -preset p7 -map 0:a? -c:a aac -ac 2 -b:a 192k -map 0:s? -c:s mov_text -map_metadata 0 -movflags use_metadata_tags '/mnt/f/tmp2/format/wait/php/FFOutput_tmp/Enkai enkai987Fansone 走马探花 Ysabella仙女降临超骚性技口技吓吓叫AV导演_恩凯 1548 _NC_.mp4'\" ");

        # 终端2：运行 ffmpeg（使用 unbuffer 确保实时写入）

        $sse->simpleExecCmd("tail -f ~/tmp.txt &

unbuffer sudo ffmpeg -hide_banner -i '/mnt/f/tmp2/format/wait/php/Enkai enkai987Fansone 走马探花 Ysabella仙女降临超骚性技口技吓吓叫AV导演_恩凯.mp4' -ss 24 -to 2917 -map 0:v:0 -c:v hevc_nvenc -crf 0 -maxrate 1600k -bufsize 4000k -vf 'scale=-2:if(gte(ih\,720)\,720\,ih),fps=24' -preset p7 -map 0:a? -c:a aac -ac 2 -b:a 192k -map 0:s? -c:s mov_text -map_metadata 0 -movflags use_metadata_tags '/mnt/f/tmp2/format/wait/php/FFOutput_tmp/Enkai enkai987Fansone 走马探花 Ysabella仙女降临超骚性技口技吓吓叫AV导演_恩凯 1548 _NC_.mp4' > ~/tmp.txt 2>&1");


        $sse->end();

        die;

    }

}

