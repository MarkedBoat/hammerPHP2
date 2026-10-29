<?php

namespace hammer\web;


class WebRequest
{
    public $ip         = '';
    public $postVar    = [];
    public $getVar     = [];
    public $requestVar = [];
    public $serverVar  = [];
    public $rawBody    = '';
    public $cookieVar  = [];
    public $headerVar=[];
    public $fileVar=[];

    /**
     * WebRequest类构造函数
     * WebRequest constructor.
     * @param $serverVar $_SERVER 变量
     */
    public function __construct($serverVar)
    {
        $this->ip        = isset($serverVar['REMOTE_ADDR']) ? $serverVar['REMOTE_ADDR'] : '';
        $this->serverVar = $serverVar;

    }

    /**
     * 设置$_POST变量
     * @param $postVar
     * @return static
     */
    public function setPostVar($postVar)
    {
        $this->postVar = $postVar;
        return $this;
    }

    /**
     * 设置$_GET变量
     * @param $getVar
     * @return static
     */
    public function setGetVar($getVar)
    {
        $this->getVar = $getVar;
        return $this;
    }

    /**
     * 获取$_POST变量
     * @return array
     */
    public function getPostVar()
    {
        if (empty($this->postVar))
        {
            $this->postVar = $_POST;
        }
        return $this->postVar;
    }

    /**
     * 获取$_GET变量
     * @return array
     */
    public function getGetVar()
    {
        if (empty($this->getVar))
        {
            $this->getVar = $_GET;
        }
        return $this->getVar;
    }

    /**
     * 设置$_REQUEST变量
     * @param $requestVar
     * @return static
     */
    public function setRequestVar($requestVar)
    {
        $this->requestVar = $requestVar;
        return $this;
    }

    /**
     * 获取$_REQUEST变量
     * @return array
     */
    public function getRequestVar()
    {
        $this->makeRequestVar();
        return $this->requestVar;
    }

    /**
     * 合并$_POST和$_GET变量
     * @return static
     */
    public function makeRequestVar()
    {
        //$this->requestVar = array_merge($this->postVar, $this->getVar);
        if (empty($this->requestVar))
        {
            //合并  get 和 post ，以post的为准
            //$this->requestVar=$this->postVar+$this->getVar;//快几十纳秒，但是容易写反，被覆盖的要写在后面，要保留的优先的写在前面，容易误解
            $this->requestVar = array_replace($this->getGetVar(), $this->getPostVar());

        }

        return $this;
    }

    /**
     * 设置原始请求体
     * @param $rawBody
     * @return static
     */
    public function setRawBody($rawBody)
    {
        $this->rawBody = $rawBody;
        return $this;
    }

    /**
     * 获取原始请求体
     * @return string
     */
    public function getRawBody()
    {
        if (empty($this->rawBody))
        {
            $this->rawBody = file_get_contents('php://input');
        }
        return $this->rawBody;
    }


    public function setCookieVar($cookieVar)
    {
        $this->cookieVar = $cookieVar;
        return $this;
    }

    public function getCookieVar()
    {
        if (empty($this->cookieVar))
        {
            $this->cookieVar = $_COOKIE;
        }
        return $this->cookieVar;
    }


    public function setHeaderVar($headerVar)
    {
        $this->headerVar = $headerVar;
        return $this;
    }

    public function getHeaderVar()
    {
        if (empty($this->headerVar))
        {
            $this->headerVar = $_SERVER;
        }
        return $this->headerVar;
    }

    public function setFileVar($fileVar)
    {
        $this->fileVar = $fileVar;
        return $this;
    }

    public function getFileVar()
    {
        if (empty($this->fileVar))
        {
            $this->fileVar = $_FILES;
        }
        return $this->fileVar;
    }

    /**
     * 获取IP地址
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }
}

