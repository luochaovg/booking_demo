<?php
require_once 'Booking.php';
require_once 'SeatBlock.php';

if(!defined('STDIN'))  define('STDIN',  fopen('php://stdin',  'r'));
if(!defined('STDOUT')) define('STDOUT', fopen('php://stdout', 'w'));

main();

function main() {
    $menu = <<< EOT

------------------------------
1.订票。
2.显示当前订票情况。
q.退出
------------------------------
请选择操作：
EOT;
    fwrite(STDOUT, '欢迎您来到xx订票系统。'.PHP_EOL);
    fwrite(STDOUT, '第一次进入已为您初始化。'.PHP_EOL);
    init();

    while (true) {
        fwrite(STDOUT, $menu);
        $option = trim(fgets(STDIN));
        switch ($option) {
            case '1':
                bookingSys();
                break;
            case '2':
                show();
                break;
            case 'q':
                fwrite(STDOUT, '感谢您使用xx订票系统，Bye!'.PHP_EOL);
                exit;
        }
    }
}

//初始化订票系统
function init()
{
    Booking::getInstance()->initBooking();
}

//订票
function bookingSys() 
{
    $menu = <<< EOT

------------------------------
1.订票。
2.测试订票。
q.返回上级。
------------------------------
请选择操作：
EOT;
    while (true) {
        fwrite(STDOUT, $menu);
        $option = trim(fgets(STDIN));
        switch ($option) {
            case '1':
                booking();
                break;
            case '2':
                testBooking();
                break;
            case 'q':
                return;
        }
    }
}

function booking()
{
    if (Booking::getInstance()->isSaledOut()) {
        $info = <<< EOT

    ------------------
    | 票已卖光，再见！|
    ------------------

EOT;
        fwrite(STDOUT, $info);
        return;
    }

    while (true) {
        $info = '请输入订票数量（数量限制1-5, 取消订票q）：';
        fwrite(STDOUT, $info);
        $count = trim(fgets(STDIN));

        if ($count == 'q') {
            return;
        }
        $count = intval($count);
        if ($count > 5 || $count < 0) {
            fwrite(STDOUT, '您的输入有误，请重新输入' . PHP_EOL);
        } else {
            break;
        }
    }

    $block = Booking::getInstance()->booking($count);

    if ($block === -1) {
        $info = '已经没有您选择数量的连续座位了，请分开订票' . PHP_EOL;
        fwrite(STDOUT, $info);
    } else {
        if ($block->count == 1) {
            $bookingInfo = '您所定的票区域为' . chr(65 + $block->section) . '区，第' . ($block->raw + 1) . '排，' . ($block->start + 1) . '号' . PHP_EOL;
        } else {
            $bookingInfo = '您所定的票区域为' . chr(65 + $block->section) . '区，第' . ($block->raw + 1) . '排，' 
                    . ($block->start + 1) . '号至' . ($block->start + $block->count) . '号' . PHP_EOL;
        }
        fwrite(STDOUT, $bookingInfo);
    }
}

//打印当前订票情况
function show() 
{
    $seatMap = Booking::getInstance()->getSeatMap();
    for ($section = 0; $section < Booking::SECTION_COUNT; $section++) {
        echo '---------------------------------------------↓↓↓↓↓'.chr(65+$section).'↓↓↓↓↓---------------------------------------------';
        echo PHP_EOL;
        for ($raw = Booking::RAW_COUNT-1; $raw >= 0; $raw--) {
            $seatCount = Booking::FIRST_RAW_SEAT_COUNT + $raw * 2;
            for ($j=0; $j < Booking::RAW_COUNT- 1 - $raw; $j++) { 
                echo ' ';
            }
            if ($raw < 9) {
                echo ' '; 
            }
            echo $raw + 1;
            for ($i=0; $i < $seatCount; $i++) {
                if ($seatMap[$section][$raw][$i] == 1) {
                    echo 'x';
                } else {
                    echo '_';
                }
            }
            echo PHP_EOL;
        }
        echo '---------------------------------------------↑↑↑↑↑'.chr(65+$section).'↑↑↑↑↑---------------------------------------------';
        echo PHP_EOL.PHP_EOL.PHP_EOL;
    }
}

function testBooking()
{
    fwrite(STDOUT, '测试购票多少组？买到无法售卖请输入n：');
    $totalTimes = trim(fgets(STDIN));
    $till = false;
    if ($totalTimes == 'n') {
       $till = true;
    }
    $totalTimes = intval($totalTimes);

    fwrite(STDOUT, '是否需要固定数量？随机请输入n，固定数量请输入（1-5）:');
    $count = trim(fgets(STDIN));

    $randFlag = false;
    if ($count == 'n') {
        $randFlag = true;
    }
    $count = intval($count);

    $times = 0;

    do {
        if ($randFlag) {
            $count = mt_rand(1, 5);
        }
        $block = Booking::getInstance()->booking($count);
        $times++;
    } while($block !== -1 && !empty($block) && ($till || $times < $totalTimes));
}