<?php

    namespace hammer\param;


    class WebRequest {
        public $ip = '';

        public function __construct() {
            $this->ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';;
        }
    }

