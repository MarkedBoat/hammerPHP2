<?php

namespace modules\dev\v1\api\KL_PC;

use hammer\web\ActionBase;


class ActionGetFfmpeged extends ActionBase
{
//后期静态绑定代替了

    public function run()
    {

        $rootDir   = "/mnt/f/tmp2/format/wait/php";
        $ar        = scandir($rootDir, SCANDIR_SORT_ASCENDING);
        $filenames = [];
        foreach ($ar as $i => $filename1)
        {
            if (in_array($filename1, [".", ".."]))
            {
                continue;
            }
            $fullpath = $rootDir . "/" . $filename1;
            if (is_dir($fullpath))
            {
                $ar2 = scandir($fullpath, SCANDIR_SORT_ASCENDING);
                foreach ($ar2 as $filename2)
                {
                    if (in_array($filename2, [".", ".."]))
                    {
                        continue;
                    }
                    $filenames[] = "{$fullpath}/{$filename2}";
                }
            }
            else
            {
                $filenames[] = $fullpath;
            }
        }

        //var_dump($ar);
        //$href = 'http://kl-home-pc:8001/mnt/f/tmp2/tmp/%20FC2-PPV-4049718%20%E5%B8%8C%E6%9C%9B%E7%94%B7%E5%8F%8B%E7%94%A8%E5%8A%9B%E6%93%8D%E5%A5%B9%E7%9A%84%E9%AA%9A%E8%B4%A7%E5%A5%B3%E4%BA%BA.mp4';

        //header("HTTP/1.1 302");
        //Header("Location: {$href}");
        //die('xxxx2');
        $list   = [];
        $pairKV = [];
        foreach ($filenames as $fullfilename)
        {
            $file_info = pathinfo($fullfilename);
            $dirname   = $file_info['dirname'];
            $filename  = $file_info['filename'];

            $tmp = [];
            if (preg_match('/^-flag([\d\-])+flag/isU', $filename, $tmp))
            {
                $flag = str_replace('flag', '', $tmp[0]);
                if (!isset($pairKV[$flag]))
                {
                    $pairKV[$flag]           = [
                        'dir'  => str_replace($rootDir, '', $dirname),
                        'flag' => $flag,
                    ];
                    $pairKV[$flag]['dirMd5'] = empty($pairKV[$flag]['dir']) ? false : md5($pairKV[$flag]['dir']);
                }
                $fullfilename2 = str_replace(['#'], '', $fullfilename);
                if ($fullfilename2 !== $fullfilename)
                {
                    rename($fullfilename, $fullfilename2);
                    $fullfilename = $fullfilename2;
                }
                $file_info2 = pathinfo($fullfilename);
                //$basename   = $file_info2['basename'];
                $filename2 = $file_info['filename'];

                //    exec("ffprobe -hide_banner  -v panic  -show_entries format=duration,size,bit_rate,filename -select_streams v:0 -show_entries stream=height,width,nb_frames,avg_frame_rate,r_frame_rate  -print_format json '{$fullfilename}'",$res);
                //  exec("ls -l {$fullfilename}");
                //  $res=json_decode(join('',$res),true);
                // $list[] = ['title' => $filename, 'src' => "/mnt/f/tmp2/format/wait/php/{$filename}",'tmp'=>$tmp];
                $fileinfo = ['title' => str_replace($rootDir, '', $fullfilename), 'src' => $fullfilename, 'filesize' => filesize($fullfilename)];
                //filesize
                if (preg_match('/^(-flag[\d\-]+flagFlag-)/isU', $filename2))
                {
                    if (strstr($filename2, '_NC_'))
                    {
                        if (strstr($fullfilename2, '.jpg'))
                        {
                            $pairKV[$flag]['cover'] = $fileinfo;
                        }
                        else
                        {
                            $pairKV[$flag]['tmp'] = $fileinfo;
                        }
                    }
                    else
                    {
                        $pairKV[$flag]['res'] = $fileinfo;
                    }
                }
                else
                {
                    $pairKV[$flag]['src'] = $fileinfo;
                }
                // $list[]=['title' => $filename, 'src' => $fullfilename,'tmp'=>$res];
            }
            //   $filename=preg_replace('/^(flag[\d\-]+flag)/isU','$1Flag',$filename);


        }
        // die;

        return ['list' => array_values($pairKV), 'ar' => $ar];
    }

}