<?php

namespace models\ext\tool;

//use common\models\tool\SysAdv;
use hammer\sys\Sys;

class Printer
{
    private static $_case                    = null;
    private        $_configs                 = array();
    private        $_cases                   = array();//实例
    public         $cache                    = true;
    public         $params                   = array();
    private        $_logs                    = array();
    private        $_error_level             = 0;//输出级别
    private        $_out_opts                = array();
    private        $_opts                    = array();
    private        $_debug_fix               = false;
    private        $_force_out               = false;
    private        $_pretty                  = false;
    private        $_pretty_border           = '';
    private        $_json_mocks              = array();
    private        $_php_mod                 = 'cli';
    private        $_debug_opts              = null;
    private        $_tab_deep                = 0;
    private        $_echo_title              = '';
    private        $_echo_level              = '';
    private        $_echo_unfold             = '';
    private        $_opts_setting            = array();
    private        $_has_loaded_args         = false;
    private        $_callback_while_shutdown = array();//当shutdown的时候 要呼叫的东西
    private        $_has_listen_shutdown     = false;
    private        $_dump_tmp_outtype        = '';
    private        $_has_start_debug         = false;
    private        $_output_state            = true;     //是否关闭输出
    private        $data2textType            = 'print_r';//json

    private $adv_fcgi_url;
    private $adv_tabecho;

    private $flag_list    = [];
    private $base_tab_cnt = 2;

    /**
     * @param bool $all_info 返回全部信息
     * @return array
     */
    private function getCaller($all_info = false)
    {
        $steps  = debug_backtrace();
        $caller = array();
        $fixed  = array();
        $j      = 0;
        foreach ($steps as $i => $step)
        {
            if (isset($step['class']) && $step['class'] === 'Sys' && $step['function'] === 'getCaller')
            {

                $j = $i;
                break;
            }
        }
        $getCaller_index = $j + 1;
        $caller          = $steps[$getCaller_index];

        $user_array_fun_fix = isset($steps[$getCaller_index]['file']) ? 0 : 1;
        if ($this->_debug_fix && isset($steps[$getCaller_index + $this->_debug_fix + $user_array_fun_fix]))
        {
            $fixed = $steps[$getCaller_index + $this->_debug_fix + $user_array_fun_fix];
        }
        foreach ($steps as $i => $step)
        {
            if (isset($step['file']) && isset($step['line']))
            {
                $steps[$i]['file'] = "   {$step['file']}:{$step['line']}   ";
                unset($steps[$i]['line']);
            }

            if (isset($step['function']) && isset($step['class']))
            {
                $steps[$i]['call'] = "{$step['class']}{$step['type']}{$step['function']}";
                unset($steps[$i]['function']);
                unset($steps[$i]['class']);
                unset($steps[$i]['type']);
            }
            $steps[$i]['__step'] = $i;
        }
        $map = array(
            '$steps'           => $steps,
            '$j'               => $j,
            '$getCaller_index' => $getCaller_index,
            'count'            => count($steps),
            'line_fix'         => $this->_debug_fix,
            //'$caller'          => $caller,
            //'$fixed'           => $fixed,
            'return'           => $fixed ? $fixed : $caller,
        );
        //$this->__dump(__FILE__ . ':' . __LINE__, array('假code1,', '假code2'), array_keys($map), array_values($map));
        if (count($caller) === 0)
        {
            $this->_logs = array('没找到caller', array($steps));
        }
        //echo $this->jsonFormat($caller);
        $this->_debug_fix = false;
        return $all_info ? $map : $map['return'];
    }

    /**
     * @param $state
     * @return $this
     */
    public function setOutputState($state)
    {
        $this->_output_state = $state === true;
        return $this;
    }

    /**
     * @param $deep
     * @return static
     */
    public function setCallerDeep($deep)
    {
        if (empty($this->_debug_fix))
        {
            $this->_debug_fix = $deep;
        }
        return $this;
    }

    /**
     * 设置基础 tab 个数，不得小于0
     * @param $num
     * @return $this
     * @throws
     */
    public function setBaseTabNumber($num)
    {
        $num = intval($num);
        if ($num < 0)
        {
            throw new \Exception('说了，不得小于0');
        }
        $this->base_tab_cnt = $num;
        return $this;
    }

    /**
     * @param string $type
     * @return static
     */
    public function setData2TextType(string $type)
    {
        $this->data2textType = $type;
        return $this;
    }

    /**
     * tab 输出
     * @param $text
     * @param int $deep
     * @param bool $call
     */
    public function tabEcho($text, $deep = 0, $call = false)
    {
        if (empty($deep) && $this->_tab_deep)
        {
            $deep = $this->_tab_deep;
        }
        $spaces = str_repeat('|    ', $deep);
        //$spaces = str_repeat('.....', $level);
        if ($this->_output_state)
        {
            if (is_array($text) || is_object($text))
            {
                if ($this->data2textType === 'json')
                {
                    $text = json_encode($text, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
                }
                else
                {
                    $text = print_r($text, true);
                }

            }
            else if (!is_string($text) && !is_int($text))
            {
                $text = var_export($text, true);
            }
            if ($call)
            {
                $this->_debug_fix = 1;
                $call             = $this->getCaller(false);
                $text             .= "----      {$call['file']}:{$call['line']}     ";
            }
            if (isset($text[0]) && $text[0] !== "\n")
            {
                $text = "\n{$text}";
            }
            echo str_replace("\n", "\n{$spaces}", $text);
        }
    }

    public function newTabEcho($flag, $text = '', $call = false)
    {
        if (!in_array($flag, $this->flag_list, true))
        {
            $this->flag_list[] = $flag;
        }
        $this->_tab_deep = count($this->flag_list) + $this->base_tab_cnt - 1;
        $this->tabEcho(($this->_php_mod === 'cli' ? '<<<<<<<' : htmlspecialchars('<<<<<<<')) . $text, $this->_tab_deep, $call);
        $this->_tab_deep = $this->_tab_deep + 1;
    }

    public function endTabEcho($flag, $text = '', $call = false)
    {
        $new_flag_list = [];
        $is_matched    = false;
        foreach ($this->flag_list as $tmp_i => $exist_flag)
        {

            if ($exist_flag === $flag)
            {
                $is_matched = true;
                break;
            }
            $new_flag_list[] = $exist_flag;//和new tab 不一样，并没有-1的修正，所以没有把命中的key 塞进数组，因为end需要把 key 清掉
        }
        if ($is_matched)
        {
            $this->flag_list = $new_flag_list;
            $this->_tab_deep = count($this->flag_list) + $this->base_tab_cnt;//和new tab 不一样，并没有上面-1的修正，所以没有把命中的key 塞进数组，因为end需要把 key 清掉
            $this->tabEcho(($this->_php_mod === 'cli' ? '>>>>>>>' : htmlspecialchars('>>>>>>>')) . $text, $this->_tab_deep, $call);
        }
        else
        {
            if ($text !== '')
            {
                $this->tabEcho(($this->_php_mod === 'cli' ? '>>>>>>>' : htmlspecialchars('>>>>>>>')) . $text, 0, $call);
            }
        }

    }

    public function getTabEchoDeep()
    {
        return $this->_tab_deep;
    }

    /**
     * 设置定深，防止异常、抛错之类的导致不能回收
     * @param $deep
     * @return static
     */
    public function setTabEchoDeep($deep)
    {
        $this->_tab_deep = $deep;
        return $this;
    }


    /**
     * dump trace，这个属于debug
     * !!!!!!!!!!!!!!!!!!以下情况会数据会异常,不确定是否是php5.3问题
     * <br>1.参数引用传递,自己包装的参数会直接报错，请换其它方法
     * <br>2.json_decode 打印值不正确,请打印json 字符串
     * <br>!!!!!!!!!!!!!!!!!!!!!!!!!!!
     *
     */
    public function dump()
    {

        if (1)
        {
            $this->_force_out = false;

            $args = func_get_args();

            $map = $this->getCaller(true);

            $step  = $map['return'];
            $line  = $step['class'] . '->' . $step['function'] . '() #' . $step['file'] . ':' . $step['line'] . '       <<<<<>>>>>>';
            $codes = explode("\n", file_get_contents($step['file']));
            $str   = $codes[$step['line'] - 1];
            if ($this->_php_mod === 'cli')
            {
                $codes = array($str);
            }
            else
            {
                $codes = array_slice($codes, $step['line'] - 4, 7);
            }
            preg_match('/dump\((.*)?\);/i', $str, $ar);
            $argnames = array();
            if (count($ar) === 2)
            {
                $argnames = self::splitStringKeepBlock($ar[1]);
            }

            $this->__dump($line, $codes, $argnames, $args, true ? $map : array());
        }
        $this->_echo_level       = '';
        $this->_dump_tmp_outtype = '';
    }

    /**
     * @param string $line file&line
     * @param array $codes 代码截取 [string,string]
     * @param array $argnames [$arg_name1,$arg_name2]
     * @param array $args [$arg_val1,$arg_val2]
     * @param array $traces
     * @return bool
     */
    private function __dump($line, $codes, $argnames, $args, $traces = array())
    {
        if ($this->_pretty)
        {
            $this->__prettyDump($line, $codes, $argnames, $args, $traces);
            return false;
        }
        echo "--------------------------------------Echo Var:{$line}:\n";
        echo join("\n", $codes);
        if ($this->_echo_title)
        {
            echo "\n####{$this->_echo_title}";
            $this->_echo_title = '';
        }
        echo "\n--------------------------------------\n";
        foreach ($args as $i => $data)
        {
            echo "\n>>>>>{$i}:" . (isset($argnames[$i]) ? $argnames[$i] : '');
            echo "\n";
            if (is_string($data) || is_int($data))
            {
                if (empty($data))
                {
                    var_dump($data);
                }
                else
                {
                    echo "{$data}\n";
                }
            }
            else if (is_object($data) || is_array($data))
            {
                if (1 || $this->_dump_tmp_outtype === 'json')
                {
                    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                }
                else
                {
                    var_export($data);
                }
                //echo $this->jsonFormat($data);
            }
            else
            {
                if (1)
                {
                    echo json_encode($data);
                }
                else
                {
                    var_dump($data);
                }
            }
        }
        echo "\n";
    }

    /**
     * @param $line
     * @param $codes
     * @param $argnames
     * @param $args
     * @param array $traces
     * @return bool
     */
    public function __prettyDump($line, $codes, $argnames, $args, $traces = array())
    {
        $lines              = array('title' => $this->_echo_title, 'unfold' => $this->_echo_unfold, 'echo_type' => $this->_echo_level, 'line' => $line, 'code' => $codes, 'argnames' => $argnames, 'args' => array(), 'traces' => array($traces));
        $this->_echo_title  = '';
        $this->_echo_unfold = false;
        foreach ($args as $i => $data)
        {
            $lines['args'][] = array('key' => isset($argnames[$i]) ? $argnames[$i] : $i, 'val' => $data);
        }
        $json = json_encode(array('type' => 'dump', 'data' => $lines));
        // echo "<pretty>$json</pretty>\n{$this->_pretty_border}\n";
        // $this->__prettyEchoHeader();
        //echo "<script>window.top.new_content({$json})</script>\n";
        if (!headers_sent())
        {
            header('content-Type:text/html;charset=utf8');
        }
        $adv_fcgi_url = $this->adv_fcgi_url;
        if (empty($adv_fcgi_url))
        {
            $adv_fcgi_url = 'http://cli.kl_funtv.com/';
        }
        echo "<script>window.parent.postMessage({$json},'{$adv_fcgi_url}')</script>";
        if ($this->adv_tabecho)
        {
            echo "\n";
        }
        ob_flush();
    }

    /**
     * 设置输出的提示头
     * @param string $title
     * @return static
     */
    public function setEchoTitle($title)
    {
        $this->_echo_title = $title;
        return $this;
    }


    public function callbackWhileShutdown($last_error)
    {

        if (is_array($last_error) && isset($last_error['type']))
        {
            if (count($this->_callback_while_shutdown) === 0)
            {
                $this->setEchoTitle('没有callback')->dump($last_error, '没有没有callback');
                return false;
            }
            foreach ($this->_callback_while_shutdown as $flag => $opt)
            {
                if ($opt['types'] === false || in_array($last_error['type'], $opt['types']))
                {
                    call_user_func_array($opt['call'], $opt['params'] === false ? array($last_error) : $opt['params']);
                }
            }
        }
        else
        {
            $this->setEchoTitle('没有错误')->dump($last_error, '没有错误');
        }

    }


    /**
     * 切割字符串  保持块   被 ()/[]/''/"" 包围的部分，直接跳过， 比如  '23,[2,3],56' 以 , 切割， 被切割成3份  23/[2,3]/56,而不是4份 23/[2/3]/56
     * @param $str
     * @return array
     */
    public static function splitStringKeepBlock($str)
    {
        $map        = array(
            '(' => ')',
            '[' => ']',
            '"' => '"',
            "'" => "'"
        );
        $now        = array();
        $len        = mb_strlen($str);
        $last_index = 0;
        $tmp        = array();
        /**
         * 跳开 (),[] 等分级 限定 指定范围 符号，找切割符
         */
        for ($i = 0; $i < $len; $i++)
        {
            $s      = mb_substr($str, $i, 1, 'utf-8');
            $tmp[]  = $s;
            $dealed = false;
            if (in_array($s, $now, true))
            {
                // $tmp[]=$now;
                array_pop($now);
                $dealed = true;
            }
            if (isset($map[$s]))
            {

                if ($dealed === false)
                {
                    $now[] = $map[$s];
                }
            }
            if ($s === ',' && count($now) === 0)
            {
                $length     = $i - $last_index;
                $result[]   = mb_substr($str, $last_index, $length, 'utf-8');
                $last_index = $i + 1;
            }
        }
        $result[] = mb_substr($str, $last_index, ($len - $last_index), 'utf-8');
        return $result;
    }
}