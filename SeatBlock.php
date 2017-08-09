<?php
require_once 'Booking.php';

class SeatBlock 
{
    //区域号
    public $section;
    //行号
    public $raw;
    //块起始座位号
    public $start;
    //块里的座位数量
    public $count;

    public function __construct($section, $raw, $start = 0, $count = null)
    {
        $this->section = $section;
        $this->raw = $raw;
        $this->start = $start;
        if (is_null($count)) {
            $this->count = $raw * 2 + Booking::FIRST_RAW_SEAT_COUNT;
        } else {
            $this->count = $count;
        }
    }

    //从此座位块中分出座位，并返回剩下的座位块
    public function getSeat($count)
    {
        $result = array();
        $newBlocks = array();
        if ($count > $this->count) { //错误数量
            $result = array();
        } elseif ($count == $this->count) { //刚好分配走
            $result = new SeatBlock($this->section, $this->raw, $this->start, $count);
        } elseif ($count < $this->count) { //未售的数量比较多，需要再次随机
            //先获取随机的位置
            $restCount = $this->count - $count;
            $startIndex = mt_rand(0, $restCount);
            $keyPre = $this->section . '_' . $this->raw . '_';
            //结果
            $result = new SeatBlock($this->section, $this->raw, $this->start + $startIndex, $count);
            if ($startIndex == 0) {
                $key = $keyPre . ($this->start + $count);
                $newBlocks = array($key => new SeatBlock($this->section, $this->raw, $this->start + $count, $restCount));
            } elseif ($startIndex == $restCount) {
                $key = $keyPre . $this->start;
                $newBlocks = array($key => new SeatBlock($this->section, $this->raw, $this->start, $restCount));
            } else {
                $block1 = new SeatBlock($this->section, $this->raw, $this->start, $startIndex);
                $block2 = new SeatBlock($this->section, $this->raw, $this->start + $count + $startIndex, $restCount - $startIndex);
                $key1 = $keyPre . $this->start;
                $key2 = $keyPre . ($this->start + $count + $startIndex);
                $newBlocks = array($key1 => $block1, $key2 => $block2);
            } 
        }

        return array(
                'result' => $result,
                'newBlocks' => $newBlocks
        );
    }
}