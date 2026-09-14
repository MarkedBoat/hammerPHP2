<?php

namespace cli\homepc;

use hammer\cli\CmdBase;
use hammer\param\Param;
use hammer\sys\Sys;

ini_set('memory_limit', '3072M');


class CmdMusic extends CmdBase
{
    public function merge_lrc()
    {


        $originalLyricsDir   = '/mnt/f/doc/music/lyrics';                // 原始歌词目录
        $translatedLyricsDir = '/mnt/f/doc/music/tlyrics';               // 翻译歌词目录
        $mp3Dir              = '/mnt/f/doc/music/mp3';                   // 翻译歌词目录


        // 获取原始歌词文件列表
        $originalFiles = glob($originalLyricsDir . '/*.lrc');
        // 获取翻译歌词文件列表
        $translatedFiles = glob($translatedLyricsDir . '/*.lrc');

        $basename2tpye2file_KV = [];
        //var_dump($originalFiles);
        foreach ($originalFiles as $file)
        {
            $basename2tpye2file_KV[strtolower(basename($file))] = ['src' => $file];
        }
        foreach ($translatedFiles as $file)
        {
            $filename = strtolower(basename($file));
            if (!isset($basename2tpye2file_KV[$filename]))
            {
                $basename2tpye2file_KV[$filename] = [];
            }
            $basename2tpye2file_KV[$filename]['cn'] = $file;
        }


        function lrcStrs2KV($strs)
        {
            $lyrics = [];

            foreach ($strs as $line)
            {
                // 匹配时间标签 [mm:ss.xx]
                if (preg_match_all('/\[(\d+):(\d+\.\d+)\](.*)/', $line, $matches, PREG_SET_ORDER))
                {
                    foreach ($matches as $match)
                    {
                        $minutes = $match[1];
                        $seconds = $match[2];
                        $text    = trim($match[3]);

                        if (!empty($text))
                        {
                            $time          = sprintf("[%02d:%05.2f]", $minutes, $seconds);
                            $lyrics[$time] = $text;
                        }
                    }
                }
            }

            return $lyrics;
        }


        function getLrcContent($filesInfo)
        {
            if (count($filesInfo) === 1)
            {
                return file_get_contents(array_values($filesInfo)[0]);
            }
            else
            {
                $src_strs = lrcStrs2KV(explode("\n", file_get_contents($filesInfo['src'])));
                $cn_strs  = lrcStrs2KV(explode("\n", file_get_contents($filesInfo['cn'])));
                $times    = array_merge(array_keys($src_strs), array_keys($cn_strs));
                usort($times, function ($a, $b)
                {
                    // 提取分钟、秒和毫秒
                    preg_match('/\[(\d+):(\d+)\.(\d+)\]/', $a, $matchesA);
                    preg_match('/\[(\d+):(\d+)\.(\d+)\]/', $b, $matchesB);

                    $timeA = (int)$matchesA[1] * 60 + (float)($matchesA[2] . '.' . $matchesA[3]);
                    $timeB = (int)$matchesB[1] * 60 + (float)($matchesB[2] . '.' . $matchesB[3]);

                    if ($timeA == $timeB)
                    {
                        return 0;
                    }
                    return ($timeA < $timeB) ? -1 : 1;
                });
                $times = array_unique($times);

                $res = [];
                foreach ($times as $time)
                {
                    $strs = [$time];

                    if (isset($src_strs[$time]))
                    {
                        $strs[] = trim($src_strs[$time]);
                    }
                    if (isset($cn_strs[$time]))
                    {
                        $strs[] = trim($cn_strs[$time]);
                    }
                    $res[] = join(' ', array_unique($strs));

                }
                return join("\n", $res);

            }
        }

        $i = 0;
        foreach ($basename2tpye2file_KV as $k => $filesInfo)
        {
            $i++;
            echo "\n{$i} {$k}\n";
            $lrcText = getLrcContent($filesInfo);
            file_put_contents("{$mp3Dir}/{$k}", $lrcText);
        }


        echo "\n歌词合并完成\n";
    }


}