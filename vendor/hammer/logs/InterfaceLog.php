<?php

namespace hammer\logs;

interface InterfaceLog
{

    public function setDir($dir);


    /**
     * 设置 文件路径风格
     * @param string $style 默认  'Ym/d/H'
     * @return static
     */
    public function setlogDatePathStyle($style);

    /**
     *记录到内存中
     * @param bool $state
     * @return static
     */
    public function letMemLogging($state);

    /**
     * 临时记录到内存中
     * @return static
     */
    public function tmpLetMemLogging();

    /**
     * 记录到文件中
     * @param bool $state
     * @return static
     */
    public function letFileLogging($state);

    /**
     * 临时记录到文件中
     * @return static
     */
    public function tmpLetFileLogging();


    /**
     * 设置log 美化
     * @param bool $state
     * @return static
     */
    public function letLogPretty($state);

    /**
     * 临时美化log
     * @return static
     */
    public function tmpLetLogPretty();

    /**
     * 设置数据记录风格
     * @param bool $state
     * @return static
     */
    public function setDataStyle($flag);

    /**
     * 临时设置数据记录风格
     * @param bool $state
     * @return static
     */
    public function tmpSetDataStyle($flag);

    /**
     * 记录日志
     * @param string $text
     * @param mixed $data ;
     * @return bool
     */
    public function log($text, $data = []);


    /**
     * 获取记录在内存中的记录
     * @return array
     */
    public function getMemLogs();
}