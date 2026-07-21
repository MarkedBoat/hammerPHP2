<?php

namespace hammer\param;


use hammer\sys\Sys;

class Param
{
    const valTypeObject = 'object';
    const valTypeArray  = 'array';
    const valTypeString = 'string';
    const valTypeInt    = 'int';
    const valTypeBool   = 'bool';

    static $chars = [];

    public static function requireArgs($param, $args)
    {
        $r        = false;
        $emptyKey = '';
        foreach ($args as $k)
        {
            if (!isset($param[$k]))
            {
                $r        = true;
                $emptyKey = $k;
                break;
            }
        }
        if ($r === true)
            throw new \Exception('参数必须:' . $emptyKey, 400);
        return $r;
    }

    public static function requireArgsNotNull($param, $args)
    {
        $r        = false;
        $emptyKey = '';
        foreach ($args as $k)
        {
            if (!isset($param[$k]) || empty($param[$k]))
            {
                $r        = true;
                $emptyKey = $k;
                break;
            }
        }
        if ($r === true)
            throw new \Exception('参数不能为空:' . $emptyKey, 400);
        return $r;
    }

    public static function get($param, $key)
    {
        if (!isset($param[$key]))
            throw new \Exception('没有参数' . $key, 400);
        return $param[$key];
    }


    public static function getInt($param, $key, $msg = '', $code = 0)
    {
        if (!isset($param[$key]))
            throw new \Exception($msg ? $msg : ('没有参数' . $key), $code ? $code : 400, $msg, [$param]);
        return intval($param[$key]);
    }

    public static function getNotEmptyInt($param, $key, $msg = '', $code = 0)
    {
        if (!isset($param[$key]))
            throw new \Exception($msg ? $msg : ('没有参数' . $key), $code ? $code : 400);
        $tmp = intval($param[$key]);
        if (empty($tmp))
            throw new \Exception($msg ? $msg : ('参数为不能为0:' . $key), $code ? $code : 400);
        return $tmp;
    }

    public static function tryGetInt($param, $key)
    {
        if (!isset($param[$key]))
            return 0;
        return intval($param[$key]);
    }

    public static function getString($param, $key, $msg = '', $code = 0)
    {
        if (!isset($param[$key]))
            throw new \Exception($msg ? $msg : ('没有参数' . $key), $code ? $code : 400);
        $tmp = strval($param[$key]);
        $tmp = trim($tmp);
        return $tmp;
    }

    public static function getNotEmptyString($param, $key, $msg = '', $code = 0)
    {
        if (!isset($param[$key]))
            throw new \Exception($msg ? $msg : ('没有参数' . $key), 400, '没有参数' . $key);
        if (!is_string($param[$key]) && !is_int($param[$key]) && !is_float($param[$key]))
            throw new \Exception($msg ? $msg : ('参数不是字符串' . $key), 400, '参数不是字符串' . $key);
        $tmp = strval($param[$key]);
        $tmp = trim($tmp);
        if (empty($tmp))
            throw new \Exception($msg ? $msg : ('参数为不能为空' . $key), $code ? $code : 400, '参数为不能为空' . $key);
        return $tmp;
    }

    public static function tryGetString($param, $key)
    {
        if (!isset($param[$key]))
            return '';
        $tmp = strval($param[$key]);
        $tmp = trim($tmp);
        return $tmp;
    }

    public static function getArray($param, $key, $msg = '', $code = 0)
    {
        if (!isset($param[$key]))
            throw new \Exception($msg ? $msg : ('没有参数' . $key), $code ? $code : 400);
        $tmp = $param[$key];
        if (!is_array($tmp))
            throw new \Exception($msg ? $msg : ('参数不是数组:' . $key), $code ? $code : 400);
        return $tmp;
    }

    public static function getNotEmptyArray($param, $key, $msg = '', $code = 0)
    {
        if (!isset($param[$key]))
            throw new \Exception($msg ? $msg : ('没有参数' . $key), $code ? $code : 400);
        $tmp = $param[$key];
        if (!is_array($tmp))
            throw new \Exception('参数不是数组:' . $key, $code ? $code : 400);
        if (empty($tmp))
            throw new \Exception($msg ? $msg : ('参数为不能为空:' . $key), $code ? $code : 400);
        return $tmp;
    }

    public static function tryGetArray($param, $key, $macthType = true)
    {
        if (!isset($param[$key]))
            return [];
        $tmp = $param[$key];
        if ($macthType)
        {
            if (!is_array($tmp))
                throw new \Exception('参数不是数组:' . $key, 400);
            return $tmp;
        }
        return [];

    }

    public static function isLastVersion($newVersion, $versionStand, &$lastVersion = '')
    {
        $new = explode('.', $newVersion);
        $std = explode('.', $versionStand);
        foreach ($new as $k => $v)
        {
            if (intval($v) > intval($std[$k]))
            {
                $lastVersion = $newVersion;
                return true;
            }
            if (intval($v) < intval($std[$k]))
            {
                $lastVersion = $versionStand;
                return false;
            }
        }
        $lastVersion = $versionStand;
        return false;
    }

    /**
     * @param $versionInput string 输入版本号
     * @param $versionNew string 特性版本号
     * @param string $lastVersion string
     * @return bool true:高于等于特性版本  false:低于特性版本
     */
    public static function isNewVersion($versionInput, $versionNew, &$lastVersion = '')
    {
        $input = explode('.', $versionInput);
        $std   = explode('.', $versionNew);
        foreach ($input as $k => $v)
        {
            if ($k > 2)
                continue;//比较前三位就行了
            if (intval($v) > intval($std[$k]))
            {
                $lastVersion = $versionInput;
                return true;
            }
            if (intval($v) < intval($std[$k]))
            {
                $lastVersion = $versionNew;
                return false;
            }
        }
        $lastVersion = $versionNew;
        if ($input[2] === $std[2])
        {
            return true;//说明前三位的都是一样的，为持有新特性的第一版
        }
        else
        {
            return false;
        }
    }


    public static function jsonOutArray($title, $array)
    {
        echo "\n//$title\n";
        echo is_array($array) || is_object($array) ? ('' . json_encode($array, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) : ("/**\n" . $array . "\n**/");
        echo "\n";
    }

    public static function outXML($title, $xmlstr)
    {
        $str = self::getFormatXmlStr($xmlstr);
        echo str_replace("\n", "\n*", htmlspecialchars($str));

    }

    /**
     * @param $xmlstr
     * @return string
     */
    public static function getFormatXmlStr($xmlstr)
    {
        return self::getDomFormatStr(self::getXmlDom($xmlstr));
    }


    /**
     * //获取xml操作类
     * @param $xmlstr
     * @return \SimpleXMLElement
     */
    public static function getSimpleXMLElement($xmlstr)
    {
        $xml = simplexml_load_string($xmlstr);
        if (empty($xml))
            Sys::app()->interruption()->setMsg('xml异常')->setDebugMsg('加载xml出错')->outError();
        return $xml;
    }

    /**
     * @param $xmlstr
     * @return \DOMElement
     */
    public static function getXmlStringDom($xmlstr)
    {
        return self::getXmlDom(self::getSimpleXMLElement($xmlstr));

    }

    /**
     * @param \SimpleXMLElement $xml
     * @return \DOMElement
     */
    public static function getXmlDom(\SimpleXMLElement $xml)
    {
        return dom_import_simplexml($xml);
    }


    /**
     * @param \DOMElement $dom
     * @return string
     */
    public static function getDomFormatStr(\DOMElement $dom)
    {
        $doc               = $dom->ownerDocument;
        $doc->formatOutput = true;
        return $doc->saveXML();
    }


    public static function getOutputVal($valType, $val)
    {
        switch ($valType)
        {
            case self::valTypeArray:
                $val = json_decode($val, true);
                if (!is_array($val))
                    $val = null;
                break;
            case self::valTypeObject:
                $val = json_decode($val);
                if (!is_array($val))
                    $val = null;
                break;
            case self::valTypeInt:
                if ($val === false)
                    $val = null;
                else $val = intval($val);
                break;
            case self::valTypeBool:
                $val = in_array($val, [1, true, '1', 'true'], true) ? true : false;
                break;
            default:
                if ($val === false)
                    $val = null;
                break;
        }
        return $val;
    }


    public static function getInputVal($valType, $val)
    {
        switch ($valType)
        {
            case self::valTypeArray:
                $val = json_encode($val);
                break;
            case self::valTypeObject:
                $val = json_encode($val);
                break;
            case self::valTypeInt:
                $val = intval($val);
                break;
            case self::valTypeBool:
                $val = in_array($val, [1, true, '1', 'true'], true) ? 1 : 0;
                break;
            case self::valTypeString:
                break;
        }
        return $val;
    }


    /**
     * 字符串半角和全角间相互转换
     * @param string $str 待转换的字符串
     * @param int $type TODBC:转换为半角；TOSBC，转换为全角
     * @return string 返回转换后的字符串
     */
    public static function convertStrType($str, $type)
    {
        if (count(self::$chars) === 0)
        {
            self::$chars = [
                'dbc' => explode(',', '０,１,２,３,４,５,６,７,８,９,Ａ,Ｂ,Ｃ,Ｄ,Ｅ,Ｆ,Ｇ,Ｈ,Ｉ,Ｊ,Ｋ,Ｌ,Ｍ,Ｎ,Ｏ,Ｐ,Ｑ,Ｒ,Ｓ,Ｔ,Ｕ,Ｖ,Ｗ,Ｘ,Ｙ,Ｚ,ａ,ｂ,ｃ,ｄ,ｅ,ｆ,ｇ,ｈ,ｉ,ｊ,ｋ,ｌ,ｍ,ｎ,ｏ,ｐ,ｑ,ｒ,ｓ,ｔ,ｕ,ｖ,ｗ,ｘ,ｙ,ｚ,－,　,：,．,，,／,％,＃,！,＠,＆,（,）,＜,＞,＂,＇,？,［,］,｛,｝,＼,｜,＋,＝,＿,＾,￥,￣,｀'),
                'sbc' => explode(',', '0,1,2,3,4,5,6,7,8,9,A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z,a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z,-,,:,.,,,/,%,#,!,@,&,(,),<,>,",\,?,[,],{,},\\,|,+,=,_,^,￥,~,`')
            ];
        }
        if ($type == 'TODBC')
        {
            return str_replace(self::$chars['sbc'], self::$chars['dbc'], $str); //半角到全角
        }
        elseif ($type == 'TOSBC')
        {
            return str_replace(self::$chars['dbc'], self::$chars['sbc'], $str); //全角到半角
        }
        else
        {
            return $str;
        }
    }


}
