<?php

    namespace models\ext\tool;

    use hammer\sys\Sys;

    class Time {
        const minUnit   = 'min';
        const hourUnit  = 'hour';
        const dayUnit   = 'day';
        const monthUnit = 'month';
        const yearUnit  = 'year';

        public static function model() {
            return new Time();
        }

        public function __construct() {

        }

        public static function getTime($number, $unit, $startTimeStamp = 0) {
            $time = 0;
            switch ($unit) {
                case 'h':
                    $time = $number * 3600;
                    break;
                case 'd':
                    $time = $number * 86400;
                    break;
                case 'm':
                    $time = self::addMonth($startTimeStamp, $number);
                    break;
                case 'y':
                    $time = self::addMonth($startTimeStamp, $number * 36);
                    break;
            }
            return $time;
        }

        public static function add($duration, $startTime) {
            $durNum  = intval($duration);
            $durUnit = str_replace($durNum, '', $duration);
            return self::getTime($durNum, $durUnit, $startTime);
        }

        public static function addMonth($startTimeStamp, $monthsNumber) {
            $startTimeStamp = $startTimeStamp > 0 ? $startTimeStamp : time();
            self::getMonthNumber($startTimeStamp, $y, $m, $d);
            $isLeapYear = self::isLeapYear($y);
            $days       = 0;
            //12月开始
            $monthDays = [
                0  => 31,
                1  => 31,
                2  => 28,
                3  => 31,
                4  => 30,
                5  => 31,
                6  => 30,
                7  => 31,
                8  => 31,
                9  => 30,
                10 => 31,
                11 => 30,

            ];
            for ($i = 1; $i <= $monthsNumber; $i++) {
                $mc   = $m + $i;
                $mod  = $mc % 12;
                $days += $monthDays[$mod];
                if ($mod === 2 && $isLeapYear)
                    $days += 1;
                if ($mod === 0) {
                    $y++;
                    $isLeapYear = self::isLeapYear($y);
                }
            }
            return $days * 86400;
        }

        public static function getMonthNumber($timestamp, &$y, &$m, &$d) {
            list($y, $m, $d) = explode('-', date('Y-m-d', $timestamp));
            //array_map不能用，只能这样
            $y = intval($y);
            $m = intval($m);
            $d = intval($d);
        }

        private function isLeapYear($year) {
            return ($year % 100 === 0 ? $year % 400 : $year % 4) === 0 ? true : false;
        }


        public static function getDuration($dur, &$num = 0, &$unit = '') {
            $units = [
                'h',
                'H',
                'd',
                'D',
                'm',
                'M',
                'y',
                'Y',
                'days',
                'months',
                'years'
            ];
            $num   = intval(str_replace($units, '', $dur));
            $unit  = trim(str_replace($num, '', $dur));
            if (!in_array($unit, $units))
                Sys::app()->interruption()->setMsg('未定义的时间单位')->outError();
            $unit      = strtolower($unit);
            $trueUnits = [
                'h' => 'hours',
                'd' => 'days',
                'm' => 'months',
                'y' => 'years',
            ];
            if (isset($trueUnits[$unit]))
                $unit = $trueUnits[$unit];
            if (empty($num) || empty($unit))
                Sys::app()->interruption()->setMsg('获取权益时长失败')->setDebugData([$dur, $num, $unit])->outError();

            return true;
        }

        public static function getUnitCN($durUnit) {
            $names = ['min' => '分钟', 'hour' => '小时', 'day' => '天', 'month' => '个月', 'year' => '年'];
            if (!isset($names[$durUnit]))
                Sys::app()->interruption()->setMsg('不存在时间单位')->outError();
            return $names[$durUnit];
        }

        public static function addDuration_bak($startTime, $durNum, $durUnit) {
            $monthsNum = 0;
            if ($durUnit === self::monthUnit)
                $monthsNum = $durNum;
            if ($durUnit === self::yearUnit)
                $monthsNum = $durNum * 12;
            if ($monthsNum) {
                $daysCountInMonth = [31, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30];//12月,1月,2月,3月……
                $startDate        = date('Y/m/d/H/i/s', $startTime);
                $ar               = explode('/', $startDate);
                $year             = intval($ar[0]);
                $month            = intval($ar[1]);
                $day              = intval($ar[2]);
                $time             = $ar[3] . ':' . $ar[4] . ':' . $ar[5];
                $yearIncr         = $year + floor(($month + $monthsNum - 1) / 12);
                $monthIncr        = ($month + $monthsNum) % 12;
                $dayIncrMax       = $monthIncr === 2 && ($yearIncr % 100 === 0 ? $yearIncr % 400 === 0 : $yearIncr % 4 === 0) ? ($daysCountInMonth[$monthIncr] + 1) : $daysCountInMonth[$monthIncr];
                $dayIncr          = $day > $dayIncrMax ? $dayIncrMax : $day;
                if ($monthIncr === 0)
                    $monthIncr = 12;
                $dateNew = join('-', [$yearIncr, $monthIncr, $dayIncr]) . ' ' . $time;
                return strtotime($dateNew);
            } else {
                return strtotime('+' . $durNum . $durUnit . 's', $startTime);
            }


        }

        public static function addDuration($startTime, $durNum, $durUnit) {

            if ($durUnit === self::monthUnit) {
                return $startTime + $durNum * 31 * 24 * 3600;
            } else if ($durUnit === self::minUnit) {
                return $startTime + $durNum * 60;
            } else if ($durUnit === self::hourUnit) {
                return $startTime + $durNum * 3600;
            } else {
                return strtotime('+' . $durNum . $durUnit . 's', $startTime);
            }


        }
    }

