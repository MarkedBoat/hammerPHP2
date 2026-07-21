<?php

namespace modules\dev\v1\api\KL_PC;

use hammer\web\ActionBase;


class ActionConvOK extends ActionBase
{
//后期静态绑定代替了

    public function run()
    {
        $pairInfo = $this->inputBox->tryGetArray('pairInfo');
        if (empty($pairInfo['src']['src']) || empty($pairInfo['res']['src']))
        {
            $this->setMsg('缺少信息')->outError();
        }
        if (!is_file($pairInfo['src']['src']))
        {
            $this->setMsg("原视频文件不存在 {$pairInfo['src']['src']}")->outError();

        }
        if (!is_file($pairInfo['res']['src']))
        {
            $this->setMsg("视频结果文件不存在 {$pairInfo['res']['src']}")->outError();

        }
        //
        $resFileInfo = pathinfo($pairInfo['res']['src']);
        /** string $resFileOldDir 转码完成后的老目录 */
        $resFileOldDir      = $resFileInfo['dirname'];
        $resFileOldBasename = $resFileInfo['basename'];


        $resFileNewBasename = trim(preg_replace('/^(-flag[\d\-]+flagFlag-)/isU', '', $resFileOldBasename));
        /** string $resFileFFOutputDir 转码后的视频所在目录 xx/FFOutput */
        $resFileFFOutputDir = '';
        /** string $resFileNewFullname 转码后的视频 fullpath xx/FFOutput/xx.mp4 */
        $resFileNewFullname = '';
        if (1)
        {
            $resFileFFOutputDir = "{$resFileOldDir}/FFOutput";
            if (!file_exists($resFileFFOutputDir))
            {
                mkdir($resFileFFOutputDir, 0777, true);
            }
            $resFileNewFullname = "{$resFileFFOutputDir}/{$resFileNewBasename}";
        }
        else
        {
            $resFileNewFullname = "{$resFileOldDir}/{$resFileNewBasename}";
        }

        // 先把转码完成后的文件  挪到 ffoutput里面去
        rename($pairInfo['res']['src'], $resFileNewFullname);
        unlink($pairInfo['src']['src']);

        usleep(200 * 1000);
        $tmp_ar  = scandir($resFileOldDir);
        $targets = [];
        foreach ($tmp_ar as $resFileOldDirFilename)
        {
            if ($resFileOldDirFilename == '.' || $resFileOldDirFilename == '..' || $resFileOldDirFilename === 'FFOutput')
                continue;
            $targets[] = $resFileOldDirFilename;
        }
        //检查老目录下，还有没有其他文件
        if (count($targets) === 0)
        {
            if (substr($resFileOldDir, -3) !== 'php' && substr($resFileOldDir, -12) !== 'php/FFOutput')
            {
                $resFileFFOutputDirFilenames = scandir($resFileFFOutputDir);
                foreach ($resFileFFOutputDirFilenames as $resFileFFOutputDirFilename)
                {
                    if ($resFileFFOutputDirFilename == '.' || $resFileFFOutputDirFilename == '..')
                        continue;
                    rename("{$resFileFFOutputDir}/{$resFileFFOutputDirFilename}", "{$resFileOldDir}/{$resFileFFOutputDirFilename}");
                }
                usleep(200 * 1000);
                if (count(scandir($resFileFFOutputDir)) === 2)
                {
                    rmdir($resFileFFOutputDir);
                }

                @rename($resFileOldDir, "{$resFileOldDir} DIR_OK");
            }
        }

        return ['sta' => true, 'filename' => $resFileNewFullname];
    }

}