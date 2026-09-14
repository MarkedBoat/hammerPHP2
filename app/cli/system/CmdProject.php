<?php

    /**
     * Created by PhpStorm.
     * User: markedboat
     * Date: 2018/7/20
     * Time: 11:01
     */

    namespace cli\system;

    use hammer\cli\CmdBase;
    use hammer\sys\Sys;

    class CmdProject extends CmdBase {


        public static function getClassName() {
            return __CLASS__;
        }


        public function init() {
            parent::init();
        }


        public function scan() {
            echo "\n fuc\n";
            $taskDir = Sys::app()->params['cli']['logDir'] . '/project/task';
            $logDir  = Sys::app()->params['cli']['logDir'] . '/project/log';
            foreach ([$taskDir, $logDir] as $dir)
                if (!file_exists($dir)) {
                    //exec("touch {$logFileName}");
                    exec("mkdir -p {$dir}");
                    exec("chmod 777 {$dir}");
                };

            $timeout = $this->inputBox->getNotEmptyInt('timeLimit');
            $endTime = time() + 3600;
            while ($endTime > time()) {
                echo "{$timeout}\n";
                $taskFiles = array_slice(scandir($taskDir), 2);
                $logFiles  = array_slice(scandir($logDir), 2);
                foreach ($taskFiles as $file) {
                    $hasLog = in_array("{$file}.log", $logFiles, true) ? 'has' : 'no';
                    echo "\n---------tasks:{$file} log:{$hasLog}---------\n";
                    list($time, $project, $branch, $shFile) = explode('__', $file);
                    if ((time() - $time) > 300) {
                        echo "timeout\n";
                        $cmd = "rm -f {$taskDir}/{$file}";
                        echo "{$cmd}\n";
                        exec($cmd);
                    } else {
                        $logFile = "{$logDir}/{$file}.log";
                        if ($hasLog === 'has') {
                            echo "skip\n";
                        } else {
                            if ($shFile)
                                $shFile = "/{$shFile}";
                            $cmd = "sh /data/code/debug/code.sh {$project} {$branch} '{$shFile}' > $logFile";
                            echo "{$cmd}\n";
                            exec($cmd);
                            file_put_contents($logFile, "\n<<<<<<<GIT OK>>>>>>\n", FILE_APPEND);
                        }
                    }
                }


                foreach ($logFiles as $file) {
                    echo "\n ----------- log:{$file}\n";
                    $time = explode('_', $file)[0];
                    if ((time() - $time) > 300) {
                        echo "timeout\n";
                        $cmd = "rm -f {$logDir}/{$file}";
                        echo "{$cmd}\n";
                        exec($cmd);
                    } else {
                        $logOkFile = $file . 'ok';
                        if (in_array($logOkFile, $logFiles)) {
                            $cmd = "rm -f {$logDir}/{$file}";
                            echo "{$cmd}\n";
                            exec($cmd);
                            $cmd = "rm -f {$logDir}/{$logOkFile}";
                            echo "{$cmd}\n";
                            exec($cmd);
                        } else {
                            echo "wait\n";
                        }
                    }
                }


                //usleep(5000);
                sleep(1);
                $timeout--;
            }

        }
    }