<?php

namespace RDM\StackLogger\Managers;

use RDM\StackLogger\Interfaces\LaravelConsoleInterface;
use RDM\StackLogger\Processors\Timer;
use RDM\StackLogger\Processors\TimerHandler;
use RDM\StackLogger\Traits\ConsoleLoggerTrait;
use RDM\StackLogger\Traits\FileLoggerTrait;
use RDM\StackLogger\Traits\GcpStackdriverLoggerTrait;
use RDM\StackLogger\Traits\GcpStorageLoggerTrait;
use RDM\StackLogger\Traits\MysqlLoggingTrait;
use RDM\StackLogger\Traits\TelegramLoggerTrait;
use RDM\StackLogger\StackLoggerServiceProvider;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\HandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * 同時達到 Command 終端輸出與 Logging (以下依照 Syslog 等級排序)
 * @see https://en.wikipedia.org/wiki/Syslog#Severity_level
 */
class SettleLogManager implements LoggerInterface, LaravelConsoleInterface
{
    use ConsoleLoggerTrait;

    use FileLoggerTrait; // 實作檔案介面

    use GcpStorageLoggerTrait;  // 實作 GCP Storage 介面

    use GcpStackdriverLoggerTrait;  // 實作 GCP Stackdriver Log 介面

    use TelegramLoggerTrait; // 實作 telegram 介面

    use MysqlLoggingTrait;      // Mysql logging 開關

    /**
     * @var TimerHandler 處理計時相關任務
     */
    private $timerHandler;

    /**
     * PSR-3 stack driver logger
     *
     * @var LoggerInterface
     */
    private $logger;

    public function __construct($setting)
    {
        if (!empty($setting['channels']) && count($setting['channels']) > 0) {
            // Create a new, on-demand aggregate logger instance
            $this->logger = Log::stack($setting['channels']);
        } else {
            throw new \Exception("At least one channel must be filled in " . StackLoggerServiceProvider::CHANNEL . ' logger');
        }

        $this->timerHandler = new TimerHandler();
    }

    public function __call($method, $parameters)
    {
        $this->logger->{$method}(...$parameters);
    }


    public function emergency($message, array $context = array())
    {
        $this->logger->emergency(...func_get_args());
    }

    public function alert($message, array $context = array())
    {
        $this->logger->alert(...func_get_args());
    }

    public function critical($message, array $context = array())
    {
        $this->logger->critical(...func_get_args());
    }

    public function error($message, array $context = array())
    {
        $this->logger->error(...func_get_args());
    }

    public function warning($message, array $context = array())
    {
        $this->logger->warning(...func_get_args());
    }

    public function notice($message, array $context = array())
    {
        $this->logger->notice(...func_get_args());
    }

    public function info($message, array $context = array())
    {
        $this->logger->info(...func_get_args());
    }

    public function debug($message, array $context = array())
    {
        $this->logger->debug(...func_get_args());
    }

    public function log($level, $message, array $context = array())
    {
        $this->logger->log(...func_get_args());
    }

    /**
     * 相容 laravel artisan console 介面
     * @param       $message
     * @param array $context
     */
    public function comment($message, array $context = array())
    {
        $this->debug($message, $context);
    }

    /**
     * 相容 laravel artisan console 介面
     * @param       $message
     * @param array $context
     */
    public function line($message, array $context = array())
    {
        $this->debug($message, $context);
    }

    /**
     * 相容 laravel artisan console 介面
     * @param       $message
     * @param array $context
     */
    public function warn($message, array $context = array())
    {
        $this->warning($message, $context);
    }

    #####################################
    #   Timing 計時器功能
    ###################################

    /**
     * 開始計時 timing 前呼叫
     */
    public function watch()
    {
        $this->timerHandler->watch();
    }


    /**
     * 印出附帶距離上次呼叫 _timing 的時間間隔
     *
     * @param string $message 輸出訊息 描述計時目標資訊
     * @param bool   $return  true 表示只回傳字串不 print 也不 logging
     *
     * @return string|null
     */
    public function timing(string $message, bool $return = false)
    {
        $message = $this->timerHandler->timing($message);

        if (! $return) {
            $this->debug($message);
        } else {
            return $message;
        }
    }

    /**
     * @param int $count_of_timers
     * @return Timer[] - 用以呼叫 start, stop, 總花費時間
     */
    public function createTimers($count_of_timers = 1)
    {
        return $this->timerHandler->createTimers($count_of_timers);
    }

    /**
     * Create only one timer
     * @return Timer
     */
    public function createTimer()
    {
        return $this->createTimers(1)[0];
    }


    ###############################
    #   例外處理
    ################################

    public function failIf($condition, $error_message, $success_message = '', $throw = false)
    {
        if ($condition) {
            $this->error($error_message);
            ! $throw && exit(1);
            throw new \Exception($error_message);
        }
        if($success_message) {
            $this->info($success_message);
        }
        return $condition;
    }

    public function failUnless($condition, $error_message, $success_message = '', $throw = false)
    {
        if (!$condition) {
            $this->error($error_message);
            ! $throw && exit(1);
            throw new \Exception($error_message);
        }
        if($success_message) {
            $this->info($success_message);
        }
        return $condition;
    }

    public function throwIf($condition, $error_message, $success_message = '') {
        $this->failIf($condition, $error_message, $success_message, true);
    }

    public function throwUnless($condition, $error_message, $success_message = '') {
        $this->failUnless($condition, $error_message, $success_message, true);
    }

}