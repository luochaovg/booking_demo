<?php
require_once 'SeatBlock.php';

class Booking
{
    //系统固定参数
    const SECTION_COUNT = 4;
    const RAW_COUNT = 26;
    const FIRST_RAW_SEAT_COUNT = 50;
    const TOTAL_SEAT_COUNT = 7800; //(50+100)*26/2 * 4 座位总数

    //所有座位售卖情况
    public $seatMap;

    public $saledCount = 0;
    //座位块1-4
    public $seatList1 = array();
    public $seatList2 = array();
    public $seatList3 = array();
    public $seatList4 = array();
    //大于4的座位块
    public $seatList5 = array();

    //单例获取Booking类
    private static $instance = null;

    private function __construct() {}

    public static function getInstance() 
    {
        if (is_null(self::$instance)) {
            self::$instance = new Booking();
        }
        return self::$instance;
    }

    //初始化系统，清除所有订票信息
    public function initBooking()
    {
        $this->saledCount = 0;
        $this->seatList1 = array();
        $this->seatList2 = array();
        $this->seatList3 = array();
        $this->seatList4 = array();
        $this->seatList5 = array();

        for ($section = 0; $section < Booking::SECTION_COUNT; $section++) {
            $this->seatMap[$section] = array();
            for ($raw = 0; $raw < Booking::RAW_COUNT; $raw++) {
                $key = $section . '_' . $raw . '_' . 0;
                $this->seatList5[$key] = new SeatBlock($section, $raw);
                $seatCount = Booking::FIRST_RAW_SEAT_COUNT + $raw * 2;
                $this->seatMap[$section][$raw] = array();
                for ($i = 0; $i  < $seatCount; $i++) { 
                    $this->seatMap[$section][$raw][$i] = 0;
                }
            }
        }
    }

    //从系统中订票
    public function booking($count)
    {
        if ($count < 1) {
            return -1;
        }

        if ($count > Booking::TOTAL_SEAT_COUNT - $this->saledCount) {
            return -1;
        }
        $block = array();
        switch ($count) {
            case 1:
                if (!empty($this->seatList1)) {
                    $randKey = array_rand($this->seatList1);
                    $block = $this->seatList1[$randKey];
                    unset($this->seatList1[$randKey]);
                }
                break;
            case 2:
                if (!empty($this->seatList2)) {
                    $randKey = array_rand($this->seatList2);
                    $block = $this->seatList2[$randKey];
                    unset($this->seatList2[$randKey]);
                }
                break;
            case 3:
                if (!empty($this->seatList3)) {
                    $randKey = array_rand($this->seatList3);
                    $block = $this->seatList3[$randKey];
                    unset($this->seatList3[$randKey]);
                }
                break;
            case 4:
                if (!empty($this->seatList4)) {
                    $randKey = array_rand($this->seatList4);
                    $block = $this->seatList4[$randKey];
                    unset($this->seatList4[$randKey]);
                }
                break;
            default:
        }

        if (empty($block)) {
            $block = $this->bookingFromBigBlock($count);
        }

        if (is_object($block) && $block !== -1) {
            $this->saledCount += $block->count;
            $this->updateSeatMap($block);
        }

        return $block;
    }

    //从更大得座位块中分出座位，并返回新的作为块
    private function bookingFromBigBlock($count)
    {
        $randKeys = array();
        if (2 > $count) {
            $randKeys = array_merge($randKeys, array_keys($this->seatList2));
        }
        if (3 > $count) {
            $randKeys = array_merge($randKeys, array_keys($this->seatList3));
        }
        if (4 > $count) {
            $randKeys = array_merge($randKeys, array_keys($this->seatList4));
        }
        $randKeys = array_merge($randKeys, array_keys($this->seatList5));

        if (empty($randKeys)) {
            return -1;
        }
        $key = $randKeys[array_rand($randKeys)];

        $block = null;
        if (isset($this->seatList2[$key])) {
            $block = $this->seatList2[$key];
            unset($this->seatList2[$key]);
        } elseif (isset($this->seatList3[$key])) {
            $block = $this->seatList3[$key];
            unset($this->seatList3[$key]);
        } elseif (isset($this->seatList4[$key])) {
            $block = $this->seatList4[$key];
            unset($this->seatList4[$key]);
        } elseif (isset($this->seatList5[$key])) {
            $block = $this->seatList5[$key];
            unset($this->seatList5[$key]);
        }

        //无法再分当前count了；
        if (is_null($block)) {
            return -1;
        }

        //拆分作为块
        $bookingResult = $block->getSeat($count);

        $newBlocks = $bookingResult['newBlocks'];
        //如果有新的座位块，加入到未分配块中
        if (!empty($newBlocks)) {
            foreach ($newBlocks as $key => $value) {
                switch ($value->count) {
                    case 1:
                        $this->seatList1[$key] = $value;
                        break;
                    case 2:
                        $this->seatList2[$key] = $value;
                        break;
                    case 3:
                        $this->seatList3[$key] = $value;
                        break;
                    case 4:
                        $this->seatList4[$key] = $value;
                        break;
                    default:
                        $this->seatList5[$key] = $value;
                        break;
                }
            }
        }
        
        return $bookingResult['result'];
    }

    //判断是否卖光所有票
    public function isSaledOut()
    {
        return (Booking::TOTAL_SEAT_COUNT - $this->saledCount) == 0;
    }

    //更新座位售卖图
    private function updateSeatMap($block)
    {
        for ($i=0; $i < $block->count; $i++) { 
            $this->seatMap[$block->section][$block->raw][$block->start + $i] = 1;
        }
    }

    //返回座位售卖图
    public function getSeatMap()
    {
        return $this->seatMap;
    }
}