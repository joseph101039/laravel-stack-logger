<?php
namespace RDM\StackLogger\Facades;

use RDM\StackLogger\Handlers\ConsoleLogger;
use RDM\StackLogger\Handlers\FileLogger;
use RDM\StackLogger\Handlers\GcpStorageLogger;
use RDM\StackLogger\Handlers\StackdriverLogger;
use RDM\StackLogger\Handlers\TelegramLogger;
use RDM\StackLogger\Processors\Timer;
use Illuminate\Support\Facades\Facade;

/**
 * @mixin  \Psr\Log\LoggerInterface
 * @see     \RDM\StackLogger\Managers\GCPLogManager
 *
 * final static string destroy() - 清除所有的設置到預設值, 摧毀 facade instance, 下次呼叫時重建新 instance, 若有修改到路徑等設置請務必呼叫此函式, 避免 queue 影響到下一個 job
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
 * @method static log($level, $message, array $context = []);
 * Logs with an arbitrary level.
 *
 * 其他相容 Laravel Console 介面
 * @method static void line(string $out, array $context = [])        同 debug()
 * @method static void comment(string $out, array $context = [])     同 debug()
 * @method static void warn(string $out, array $context = [])        同 warning()
 * @method static string ask(string $out)                            詢問問題並等待使用者輸入
 *
 * 本地 Log 檔案相關
 * @method static self setLocalLogPath(string $path)       設置 log 檔案路徑
 * @method static self setCustomLogPath(string $path)      同 setLocalLogPath()
 * @method static string getLocalLogPath()                 取得 log 檔案路徑
 * @method static self clearLogFile()                      清除 log 檔案內容
 * @method static self deleteLogFile()                     刪除 log 檔案
 *
 * GCP Storage 檔案處理相關
 * @method static self setStorageBucketFolder(string $path)  設置 Bucket id 底下的跟資料夾路徑
 * @method static self setStorageBucketPath(string $path)    設置 BucketFolder 底下的上傳物件路徑
 * @method static string|null getStorageObjectPath()     取得 Storage 上傳物件的完整路徑 (bucket folder + bucket path)
 * @method static string|null getStorageLogPath()        同 getStorageObjectPath()
 * @method static string|null getStoragePublicUrl()      取得 Storage object 的公開網址
 * @method static self setStorageUploadFilePath(string $path) 設置 Storage 上傳本地檔案的路徑
 * @method static string|null getStorageUploadFilePath() 取得 Storage 被上傳本地檔案的路徑
 *
 * GCP StackDriver 相關
 * @method static self setStackDriverLogName(string $name) 設置 logName 名稱, 作為 Google console 頁面查詢條件
 * @method static string|null getStackDriverLink() 取得 Google 記錄檔探索工具 瀏覽頁面的 log 連結
 *
 * Telegram 相關
 * @method static self setTelegramChatIds(array $chat_ids) 設置接收通知者
 * @method static self addTelegramChatId(string $chat_id) 添加一位通知者 (傳入 user id)
 * @method static self removeTelegramChatId(string $chat_id) 移除一位通知者 (傳入 user id)
 * @method static self disableTelegramNotification() 關閉通知提示音
 * @method static self enableTelegramNotification() 開啟通知提示音
 *
 * 例外處理
 * @method static void renderException(\Exception $e, $render_type = 'editor', $verbosity = true) - 渲染 exception 成漂亮可讀訊息 (human-readable message)
 *      $render_type 可以代入 editor, trace, default 三種參數, 會有不同印出格式
 * @method static void dumpExceptionPrettyPage(\Exception $e, string $export_html_path) 匯出 exception 成一個 html 檔案, 需要指定匯出 html 的完整檔案路徑
 * @method static void failIf($condition, $error_message, $success_message = '', $throw = false) 如果 condition 為假, log $error_message, 否則 log $success_message, 如果 $throw 為真會拋出例外
 * @method static void failUnless($condition, $error_message, $success_message = '', $throw = false) 如果 condition 為真, log $error_message, 否則 log $success_message, 如果 $throw 為真會拋出例外
 *
 * 計時器功能
 * @method static void watch() - 開始計時, 在第一次呼叫 timing 前呼叫
 * @method static string timing(string $out, bool $return = false) - 印出附帶距離上次呼叫 timing 或 watch 的時間間隔, $return 為 true 表示只回傳字串不 print 也不 logging
 * @method static Timer createTimer() 創立一個計時器
 * @method static Timer[] createTimers(int $count_of_timers = 1) - 創立多個計時器
 *
 * MySQL Log 相關
 * @method static self enableMysqlGeneralLog($connection = null)    啟用 General log, 每一項查詢將會被記錄到 mysql.general_log
 * @method static self disableMysqlGeneralLog($connection = null)   停用 General log
 * @method static self clearMysqlGeneralLog($connection = null)     清除  mysql.general_log
 * @method static self enableMysqlSlowLog($long_query_time = 1, $connection = null)  啟用 Slow log, 每一項超過 $long_query_time 秒的查詢, 將會被記錄到 mysql.slow_log
 * @method static self disableMysqlSlowLog($connection = null) 停用 Slow log
 * @method static self clearMysqlSlowLog($connection = null)   清除 mysql.slow_log
 *
 *                                                     * 取得各類 logger 實例
 * @method static ConsoleLogger console($connection = null)         取得 console logger
 * @method static FileLogger file($connection = null)               取得 file logger
 * @method static GcpStorageLogger storage($connection = null)      取得 storage logger
 * @method static StackdriverLogger stackdriver($connection = null) 取得 stackdriver logger
 * @method static TelegramLogger telegram($connection = null)       取得 telegram logger
 */


Class GCPLog extends Facade implements FacadeInterface
{
    /**
     * @see GCPLogProvider::register()
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'stackLogger';
    }

    /**
     * 清除所有的設置到預設值, 摧毀 facade instance, 下次呼叫時重建新 instance
     */
    final public static function destroy()
    {
        // App 與 Facade 兩個地方都必須清除指向這個 singleton 的 reference
        app()->forgetInstance(self::getFacadeAccessor());
        self::clearResolvedInstance(self::getFacadeAccessor());
    }
}
