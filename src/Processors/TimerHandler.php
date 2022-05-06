<?php


namespace RDM\StackLogger\Processors;

use RDM\StackLogger\Processors\Timer;

/**
 * 計時功能
 * Class TimerHandler
 *
 * @package RDM\StackLogger\Processors
 */
class TimerHandler
{
    /**
     * last watch time
     * @var int
     */
    public $lastTime = null;

    // create another static timer if you would like timer to be global, e.g.
    public static $lastGlobalTime = null;


    /**
     * 開始計時 timing 前呼叫
     */
    public function watch()
    {
        $this->lastTime = microtime(true);
    }


    /**
     * 印出附帶距離上次呼叫 _timing 的時間間隔, $return 為 true 表示只回傳字串不 print 也不 logging
     * @param string $message 輸出訊息 描述計時目標資訊
     */
    public function timing(string $message)
    {
        // 附帶距離上次呼叫的時間間隔, 可以改用功能更多的 createTimers
        $current = microtime(true);
        if (is_null($this->lastTime)) {
            $this->lastTime = $current;
        }

        $output = sprintf("%s %.3f sec", $message, $current - $this->lastTime);
        $this->lastTime = $current;

        return $output;
    }


    /**
     * @param int $count_of_timers
     * @return Timer[] - 用以呼叫 start, stop, 總花費時間
     */
    public function createTimers($count_of_timers = 1) {
        $timers = [];
        for($i = 0; $i < $count_of_timers; $i++) {
            $timers[] = new Timer();
        }
        return $timers;
    }
}
