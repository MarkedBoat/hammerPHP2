<?php


    namespace cli\test;

    use hammer\cli\CmdBase;
    use hammer\sys\Sys;


    /**
     * Class CmdTmp2
     * @package cli\test
     */
    class CmdTmp2 extends CmdBase {

        /**
         * @return string
         */
        public static function getClassName() {
            return __CLASS__;
        }


        /**
         *
         */
        public function init() {
            parent::init();
        }


        public function out(){
            $r=$this->inputBox->tryGetInt('r');
            echo "\nddddddddddd\n";
            if($r!==1)throw new \Exception('ddd',11);
            return true;
       }
    }