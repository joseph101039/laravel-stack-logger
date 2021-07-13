<?php


namespace RDM\StackLogger\Processors;

/**
 * 計算一段邏輯在多次迴圈中總花費時間用 累積 start ~ stop 時間
 * Class Timer
 *
 * @package RDM\StackLogger\Processors
 */
Class Timer {
    private $lastTime = null;
    private $duration = 0.0;
    private $description = '';
    private $timeout = 0;   // 逾時上限 (秒), 0 表示無 timeout
    private $throwAtTimeout = false;    // timeout 狀態時拋出例外

    public function __construct() {
        $this->lastTime = microtime(true);
    }
    public function start() {
        $this->lastTime = microtime(true);
        return $this;
    }
    public function stop() {
        $now = microtime(true);
        $this->duration += ($now - $this->lastTime);
        $this->lastTime = $now;
        throw_if($this->throwAtTimeout && $this->isTimeout()
            , new \Exception(sprintf("%s 花費 %.2f秒, 逾時錯誤", $this->description, $this->duration)));
        return $this;
    }
    public function reset() {       // 重設經過時間
        $this->duration = 0.0;
        return $this;
    }
    public function setDescription(string $description) {
        ($this->description === '') && $this->description = $description;       // 有值就不執行
        return $this;
    }
    public function totalSpend() {     // getter
        return $this->duration;     // in second
    }
    public function setTimeout($timeout) {
        $this->timeout = $timeout; // second
        return $this;
    }
    public function isTimeout() : bool{
        return $this->timeout ? ($this->duration > $this->timeout): false;
    }
    public function enableTimeoutException() {
        $this->throwAtTimeout = true;
        return $this;
    }

    public function __toString(){       // print description and time spent, 例如可以 $this->_info(implode("\n", $timers)) 印出所有花費時間
        return sprintf("%s 花費 : %.2f 秒", $this->description, $this->duration);
    }
}
