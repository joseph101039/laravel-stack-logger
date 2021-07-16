<?php


namespace RDM\StackLogger\Traits;


use RDM\StackLogger\Handlers\TelegramLogger;

trait TelegramLoggerTrait
{
    use ChannelAccessTrait;

    /**
     * @var string Channel 名稱
     */
    private static $channel = 'telegram';

    /**
     * 設置通知者
     * @param string[] $chat_ids (a list of telegram user id)
     * @return self
     */
    public function setTelegramChatIds(array $chat_ids)
    {
        $this->getTelegramChannel()->setChatIds($chat_ids);
        return $this;
    }


    /**
     * 添加通知者
     * @param string $chat_id
     *
     * @return $this
     */
    public function addTelegramChatId(string $chat_id)
    {
        $this->getTelegramChannel()->addChatId($chat_id);
        return $this;
    }

    /**
     * 移除指定通知者
     *
     * @param string $filtered_chat_id
     *
     * @return $this
     */
    public function removeTelegramChatId(string $filtered_chat_id)
    {
        $this->getTelegramChannel()->removeChatId($filtered_chat_id);
        return $this;
    }


    /**
     * 關閉通知提示音
     * @return $this
     */
    public function disableTelegramNotification()
    {
        $this->getTelegramChannel()->disableNotification();
        return $this;
    }

    /**
     * 開啟通知提示音
     * @return $this
     */
    public function enableTelegramNotification()
    {
        $this->getTelegramChannel()->enableNotification();
        return $this;
    }


    /**
     * @return TelegramLogger|\Monolog\Handler\HandlerInterface|null
     */
    protected function getTelegramChannel()
    {
        return $this->getMonologHandler(self::$channel);
    }
}
