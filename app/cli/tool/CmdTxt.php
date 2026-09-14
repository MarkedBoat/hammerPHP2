<?php


namespace cli\tool;

use hammer\cli\CmdBase;

ini_set('memory_limit', '3072M');


class CmdTxt extends CmdBase
{

    public function chapter()
    {

        $src_file = $this->inputBox->getNotEmptyString('src');
        echo "\n ok \n";
        $out_file = str_replace('.txt', (date('Ymd_hi') . '.txt'), $src_file);

        $f = fopen($src_file, 'r');
        file_put_contents($out_file, '');
        $parttners = ['/^(\s?)(\d+)(.{0,32})$/'];
        echo "\n new file {$out_file}\n";
        $i = 0;
        while (!feof($f))
        {
            $i++;
            if ($i % 10000 === 0)
            {
                echo "\n{$i}\n";
            }
            $raw_str = fgets($f);
            foreach ($parttners as $parttner)
            {
                $res = preg_match($parttner, $raw_str, $ar);
                if ($res)
                {
                    $raw_str =  preg_replace($parttner, '第$2章 $3', $raw_str);
                    //var_dump($res, $ar, $raw_str);
                    break;
                }

            }
            file_put_contents($out_file, "{$raw_str}\n", FILE_APPEND);

        }
        fclose($f);
        echo "\n new file {$out_file}\n";


    }


}