<?php

namespace modules\dev\v1\api\KL_PC;

use hammer\web\ActionBase;


class ActionFormatName extends ActionBase
{
//后期静态绑定代替了

    public function run()
    {
        $rootDir   = "/mnt/f/tmp2/format/wait/php";
        $rubbishWords     = explode(',', '  ,『,』,【,】,【】,[,],〖,〗,《,》,@,顶级無碼,sex8.cc,guochan2048.com,Night24.com,2048社区,-big2048.com,SEX8.CC,Weagogo,kpxvs.com,miohot0428,jav20s8.com,Woxav.Com,fun2048.com,最新流出,最新,钻石级,❤️,推荐,会所尊享,❤,推荐,蜜桃,传媒,特辑,新作,SEX8.CC,爱剪辑,我的视频,无码破解,破解,独家爆料,#,~,「,」,[,],(,),（,）,Uncensored,uncensored, MP4 ,MP4-,MP4_,BVPP, _final_ ,_name_ver_2023_02_02,_N_V__ _N_V__,⚫️,_N_V_1_,✅,\\,|,?,#,+');
       // exec('ls -l',$ar);

        $rubbishWords[]   = "'";
        $rubbishWords[]   = '"';
        $rubbishWordEmpty = array_fill(0, count($rubbishWords), ' ');

        $dirs=[];
        exec("find '{$rootDir}' -type d",$dirs);
        $dirKV=[];
        foreach ($dirs as $dir) {
            $dirKV[$dir]=false;
            $new_dir=str_replace($rubbishWords, $rubbishWordEmpty, $dir);
            if($dir!==$new_dir){
                $dirKV[$dir]=$new_dir;
                exec("mv '{$dir}' '{$new_dir}'");
            }
        }

        $files=[];
        exec("find '{$rootDir}' -type f",$files);
        $fileKV=[];
        foreach ($files as $file) {
            $fileKV[$file]=false;
            $new_file=str_replace($rubbishWords, $rubbishWordEmpty, $file);
            if($file!==$new_file){
                $fileKV[$file]=$new_file;
                exec("mv '{$file}' '{$new_file}'");
            }
        }


       // passthru("ls -l ''",$ar);
        return ['sta' => true, 'dirs' => $dirKV, 'files' => $fileKV];
    }

}