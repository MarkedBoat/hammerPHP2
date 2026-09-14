<?php

namespace modules\dev\v1\api\KL_PC\xx\op\config;

use hammer\web\ActionBase;


class ActionSaveRubishWords extends ActionBase
{

//后期静态绑定代替了

    public function run()
    {
        @ob_clean();
        // $ar = scandir("/mnt/f/tmp2/tmp");

        $rubishWordsFilename = __APP_DIR__ . '/config/file/xx_video/rubish_words.txt';
        $dir                 = dirname($rubishWordsFilename);
        if (!file_exists($dir))
        {
            mkdir($dir, 0777, true);
        }
        $rubishWords = [];
        if (file_exists($rubishWordsFilename))
        {
            $rubishWords = explode("\n", file_get_contents($rubishWordsFilename));
        }


        $inputAr = explode("\n", trim($this->inputBox->getNotEmptyString('text')));
        $newAr   = array_unique(array_map(function ($str) { return trim($str); }, array_merge($inputAr, $rubishWords)));
        $newAr[] = ' ';
        //  rsort($newAr, SORT_NUMERIC);
        //   rsort($newAr, SORT_STRING);
        //$this->sortByFirstCharAndLength($newAr, true, false);
        $this->sortByLengthAndString($newAr, false, true);
        file_put_contents($rubishWordsFilename, join("\n", $newAr));
         \hammer\web\HttpApp::$hasOutput = true;
        var_dump($newAr);
        die(file_get_contents($rubishWordsFilename));
    }


    /**
     * 多级排序：先按第一个字符排序（Unicode码点），同首字符再按字符串长度排序
     *
     * @param array &$array 待排序数组（元素为字符串）
     * @param bool $char_asc 首字符排序方向：true=升序，false=降序
     * @param bool $length_asc 长度排序方向：true=升序（短→长），false=降序（长→短）
     * @param bool $preserve_keys 是否保留原键名
     * @return bool
     */
    function sortByFirstCharAndLength(array &$array, bool $char_asc = true, bool $length_asc = true, bool $preserve_keys = false): bool
    {
        $getFirstChar = function ($str)
        {
            return mb_substr($str, 0, 1, 'UTF-8');
        };

        $compare = function ($a, $b) use ($getFirstChar, $char_asc, $length_asc)
        {
            $firstA = $getFirstChar((string)$a);
            $firstB = $getFirstChar((string)$b);

            if ($firstA === $firstB)
            {
                // 首字符相同 → 按长度排序（独立方向）
                $lenA = mb_strlen($a, 'UTF-8');
                $lenB = mb_strlen($b, 'UTF-8');
                $cmp  = $lenA <=> $lenB;
                return $length_asc ? $cmp : -$cmp;
            }
            else
            {
                // 首字符不同 → 按 Unicode 码点排序
                $cmp = $firstA <=> $firstB;
                return $char_asc ? $cmp : -$cmp;
            }
        };

        if ($preserve_keys)
        {
            return uasort($array, $compare);
        }
        else
        {
            return usort($array, $compare);
        }
    }


    /**
     * 多级排序：先按字符串长度排序（短→长 或 长→短），长度相同则按字符顺序（Unicode 码点）排序
     *
     * @param array &$array 待排序数组（元素为字符串）
     * @param bool $length_asc 长度排序方向：true=升序（短→长），false=降序（长→短）
     * @param bool $string_asc 字符顺序排序方向：true=升序，false=降序（仅在长度相同时生效）
     * @param bool $preserve_keys 是否保留原键名
     * @return bool
     */
    function sortByLengthAndString(array &$array, bool $length_asc = true, bool $string_asc = true, bool $preserve_keys = false): bool
    {
        $compare = function ($a, $b) use ($length_asc, $string_asc) {
            $lenA = mb_strlen((string)$a, 'UTF-8');
            $lenB = mb_strlen((string)$b, 'UTF-8');

            if ($lenA !== $lenB) {
                // 长度不同，按长度排序
                $cmp = $lenA <=> $lenB;
                return $length_asc ? $cmp : -$cmp;
            } else {
                // 长度相同，按字符串本身的字符顺序比较（多字节安全，使用二进制比较）
                $cmp = $a <=> $b;
                return $string_asc ? $cmp : -$cmp;
            }
        };

        if ($preserve_keys) {
            return uasort($array, $compare);
        } else {
            return usort($array, $compare);
        }
    }
}