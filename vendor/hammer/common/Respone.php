<?php

namespace hammer\common;

class Respone
{
    const TYPE_HTML = 'html';
    const TYPE_JSON = 'json';
    const TYPE_XML  = 'xml';
    const TYPE_TEXT = 'text';
    const TYPE_FILE = 'file';

    const RES_OK    = 'ok';
    const RES_ERROR = 'error';


    private string $type      = self::TYPE_JSON;
    private        $data      = [];
    private string $resCode   = self::RES_OK;
    private string $bizCode   = '';
    private string $message   = '';
    private array  $debugData = [];


    public static function m($type = self::html)
    {
        if (!in_array($type, [self::TYPE_HTML, self::TYPE_JSON, selff::TYPE_XML, self::TYPE_TEXT, self::TYPE_FILE]))
        {
            throw new \Exception('Respone type error');
        }
        $m       = new self();
        $m->type = $type;
        return $m;
    }

    public function success($type = self::TYPE_JSON)
    {
        $m = self::m([], $type);
        // $m->data = $data;

        $m->setResCode(self::RES_OK);
        return $this;
    }

    public function error($type = self::TYPE_JSON)
    {
        $m = self::m([], $type);
        // $m->data = $data;
        $m->setResCode(self::RES_ERROR);
        return $this;
    }


    public function getData()
    {
        return $this->data;
    }

    /**
     * @param $data
     * @return static
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    /**
     * @param $type
     * @return static
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }


    public function getResCode()
    {
        return $this->resCode;
    }

    /**
     * @param $resCode
     * @return static
     */
    public function setResCode($resCode)
    {
        $this->resCode = $resCode;
        return $this;
    }


    public function getBizCode()
    {
        return $this->bizCode;
    }

    /**
     * @param $bizCode
     * @return static
     */
    public function setBizCode($bizCode)
    {
        $this->bizCode = $bizCode;
        return $this;
    }

    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param $message
     * @return static
     */
    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }

    public function getDebugData()
    {
        return $this->debugData;
    }

    /**
     * @param $debugData
     * @return static
     */
    public function setDebugData($debugData)
    {
        $this->debugData = $debugData;
        return $this;
    }

    public function isJson()
    {
        return $this->type == self::TYPE_JSON;
    }

    public function isXml()
    {
        return $this->type == self::TYPE_XML;
    }

    public function isText()
    {
        return $this->type == self::TYPE_TEXT;
    }

    public function isHtml()
    {
        return $this->type == self::TYPE_HTML;
    }

    public function isFile()
    {
        return $this->type == self::TYPE_FILE;
    }


    public function out()
    {
        switch ($this->type)
        {
            case self::TYPE_HTML:
                header('Content-Type: text/html;charset=utf8');
                echo $this->data;
                break;
            case self::TYPE_JSON:
                header('Content-Type: application/json;charset=utf8');
                echo json_encode($this->data);
                break;
            case self::TYPE_XML:
                header('Content-Type: application/xml;charset=utf8');
                echo $this->data;
                break;
            case self::TYPE_TEXT:
                header('Content-Type: text/plain;charset=utf8');
                echo $this->data;
                break;
            case self::TYPE_FILE:
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . $this->data['name'] . '"');
                echo $this->data['data'];
                break;
        }
    }
}