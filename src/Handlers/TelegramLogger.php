<?php


namespace RDM\StackLogger\Handlers;

use RDM\StackLogger\Processors\TelegramBotHandler;
use RDM\StackLogger\Traits\LogRecordTransformerTrait;
use Monolog\Handler\PsrHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

/**
 * Class TelegramLogger
 *
 * @package RDM\StackLogger\Handlers
 *
 *
 * @package RDM\StackLogger\Handlers
 *
 * PHP PSR-3 介面, 所有 logger 共同支援
 * @method static void emergency(string $out, array $context = [])    System is unusable
 * @method static void alert(string $out, array $context = [])        Action must be taken immediately
 * @method static void critical(string $out, array $context = [])     Critical conditions.
 * @method static void error(string $out, array $context = [])        Runtime errors that do not require immediate action but should typically
 * @method static void warning(string $out, array $context = [])      Exceptional occurrences that are not errors.
 * @method static void notice(string $out, array $context = [])       Normal but significant events.
 * @method static void info(string $out, array $context = [])         Interesting events.
 * @method static void debug(string $out, array $context = [])        Detailed debug information.
 */
class TelegramLogger extends PsrHandler
{
    use LogRecordTransformerTrait;

    /**
     * 接收者的 user_id 陣列
     * @var array $chatIds
     */
    private $chatIds;

    private $apiKey;

    /**
     * @var bool 關閉通知聲響
     */
    private $disableNotification;

    /**
     * @param string          $api_key
     * @param array          $chat_ids
     * @param bool            $disable_notification
     * @param LoggerInterface $logger The underlying PSR-3 compliant logger to which messages will be proxied
     * @param string|int      $level  The minimum logging level at which this handler will be triggered
     * @param bool            $bubble Whether the messages that are handled can bubble up the stack or not
     */
    public function __construct(string $api_key, array $chat_ids, bool $disable_notification, LoggerInterface $logger, $level = Logger::DEBUG, bool $bubble = true)
    {
        parent::__construct($logger, $level, $bubble);

        $this->apiKey = $api_key;
        $this->chatIds = $chat_ids;
        $this->disableNotification = $disable_notification;

    }

    public function __call($method, $parameters)
    {
        $log_level = strtoupper($method);
        if (defined("\Psr\Log\LogLevel::{$log_level}")) {
            $this->log($log_level, $message = $parameters[0], $context = $parameters[1] ?? []);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function handle(array $record): bool
    {
        if (!$this->isHandling($record)) {
            return false;
        }

        $function = strtolower($record['level_name']);      // level name 即為 LogLevel 種類
        $this->{$function}($record['message'], $record['context']);

        return false === $this->bubble;
    }

    public function log($log_level, $message, array $context)
    {
        $text =  $this->messageTransform($message, $context);
        $this->sendNotification("$text");
    }

    /**
     * 設置通知者
     * @param string[] $chat_ids (a list of telegram user id)
     */
    public function setChatIds(array $chat_ids)
    {
        $this->chatIds = $chat_ids;
    }

    /**
     * 添加通知者
     * @param string $chat_id
     *
     * @return $this
     */
    public function addChatId(string $chat_id)
    {
        if (!in_array ($chat_id, $this->chatIds)) {
            $this->chatIds[] = $chat_id;
        }
        return $this;
    }
    /**
     * 移除指定通知者
     *
     * @param string $filtered_chat_id
     *
     * @return $this
     */
    public function removeChatId(string $filtered_chat_id)
    {
        $this->chatIds = array_filter($this->chatIds, function ($chat_id) use ($filtered_chat_id)  {
            return $chat_id != $filtered_chat_id;
        });
        return $this;
    }

    public function disableNotification()
    {
        $this->disableNotification = true;
    }

    public function enableNotification()
    {
        $this->disableNotification = false;
    }

    public function setApiKey($api_key)
    {
        $this->apiKey = $api_key;
    }

    /**
     * 傳參 將被以 html 格式解析
     * @link https://python-telegram-bot.readthedocs.io/en/stable/telegram.parsemode.html
     *
     * 支援的 html tags 如下表所列 (僅有這些)
     * @link https://core.telegram.org/bots/api#html-style
     */
    private function sendNotification($text)
    {
        foreach ($this->chatIds as $chat_id) {
            $bot = new TelegramBotHandler($this->apiKey, (string)$chat_id, Logger::DEBUG, true, 'HTML', false, $this->disableNotification);
            $bot->send($text);
        }
    }

}
