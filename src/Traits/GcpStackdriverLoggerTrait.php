<?php


namespace RDM\StackLogger\Traits;


use RDM\StackLogger\Handlers\StackdriverLogger;

trait GcpStackdriverLoggerTrait
{
    use ChannelAccessTrait;

    /**
     * @var string Channel 名稱
     */
    private static $stackdriverChannel = 'stackdriver';

    /**
     * 設置 logName 名稱, 作為 Google console 頁面查詢條件
     * @param string $name log 名稱
     * @return $this
     */
    public function setStackDriverLogName(string $name)
    {
        $this->getStackdriverChannel()->setLogName($name);
        return $this;
    }

    /**
     * 取得 Google 記錄檔探索工具 瀏覽頁面的 log 連結
     * @return string|null
     */
    public function getStackDriverLink()
    {
        return $this->getStackdriverChannel()->getLink();
    }

    /**
     * @return StackdriverLogger|\Monolog\Handler\HandlerInterface|null
     */
    private function getStackdriverChannel()
    {
        return $this->getMonologHandler(self::$stackdriverChannel);
    }
}
