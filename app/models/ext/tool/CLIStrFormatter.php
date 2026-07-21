<?php

namespace models\ext\tool;

//use common\models\tool\SysAdv;
use hammer\sys\Sys;


class CLIStrFormatter
{
    /**
     * 字色              背景              颜色
     * ---------------------------------------
     * 30                40              黑色
     * 31                41              紅色
     * 32                42              綠色
     * 33                43              黃色
     * 34                44              藍色
     * 35                45              紫紅色
     * 36                46              青藍色
     * 37                47              白色
     *
     * 0 终端默认设置（黑底白字）
     * 1 高亮显示
     * 4 使用下划线
     * 5 闪烁
     * 7 反白显示
     * 8 不可见
     */

    /**      黑色     */
    const black = 30;
    /**      紅色     */
    const red = 31;
    /**      綠色     */
    const green = 32;
    /**      黃色     */
    const yellow = 33;
    /**      藍色     */
    const blue = 34;
    /**      紫紅色     */
    const magenta = 35;
    /**      青藍色     */
    const cyan = 36;
    /**      白色     */
    const white   = 37;
    const default = 39;

    /**  终端默认设置 */
    const reset = 0;
    /**  高亮显示 */
    const hightlight = 1;

    /**  高亮显示 */
    const underline = 4;


    /**  闪烁 */
    const blink = 5;
    /**  反白显示 */
    const reverse = 7;
    /**  不可见 */
    const hidden = 8;




    public static function getStr($text, $color = 39, $bg = 39, $opts = [])
    {

        $optStr = '0';
        if (is_numeric($opts))
        {
            $optStr = "{$opts}";
        }
        else if (is_array($opts))
        {
            if (count($opts) > 0)
            {
                $str    = join(';', $opts);
                $optStr = "{$str}";
            }
            else
            {
                //
            }
        }
        else
        {
           //
        }
        $bg += 10;
        return "\033[{$optStr};{$color};{$bg}m{$text}\033[0m";
    }

    // 快捷方法
    public static function error($text)
    {
        return self::getStr($text, self::black, self::red, []);
    }

    public static function success($text)
    {
        return self::getStr($text, self::green, self::default, [self::hightlight]);
    }

    public static function warning($text)
    {
        return self::getStr($text, self::black, self::yellow, []);
    }

    public static function info($text)
    {
        return self::getStr($text, self::blue, self::default, [self::hightlight]);
    }

    public static function eg()
    {
        // 使用示例
        echo self::getStr('test1 ') . "\n";
        echo self::getStr('test2 ', self::red, self::yellow) . "\n";
        echo self::getStr('test3 ',self::red, self::yellow,self::blink) . "\n";
        echo self::getStr('test3 ',self::yellow, self::blue,self::hightlight) . "\n";
        echo self::getStr('test3 ',self::black, self::yellow) . "\n";
        echo "\033[0;30;41m 1 color!!! \033[0m\n";
        echo "\033[0;30;41m 2 color!!!  \033[0m\n";
        echo "\033[1;5;4;30;41m 2.1 color!!!  \033[0m\n";
        echo "\033[0;33;49m 3 color!!!  \033[0m\n";
        echo "\033[0;30;43m 4 color!!!  \033[0m\n";

        printf("\033[0;30;40m color!!! \033[0m Hello \n");
        printf("\033[0;30;41m color!!! \033[0m Hello \n");
        printf("\033[0;30;42m color!!! \033[0m Hello \n");
        printf("\033[0;30;43m color!!! \033[0m Hello \n");

        echo CLIStrFormatter::error("错误！操作失败。") . PHP_EOL;
        echo CLIStrFormatter::success("成功！操作完成。") . PHP_EOL;
        echo CLIStrFormatter::warning("警告！请注意。") . PHP_EOL;
        echo CLIStrFormatter::info("信息：这是一个提示。") . PHP_EOL;

        // 自定义样式
    }
}

