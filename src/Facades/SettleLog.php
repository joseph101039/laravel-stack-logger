<?php
namespace RDM\StackLogger\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @mixin  \Psr\Log\LoggerInterface
 *
 * @ todo modify the interface
 * @see \RDM\StackLogger\Managers\SettleLogManager
 *
 * @final  static string destroy() - 清除所有的設置到預設值, 摧毀 facade instance, 下次呼叫時重建新 instance, 若有修改到路徑等設置請務必呼叫此函式, 避免 queue 影響到下一個 job
 * @method static SettleLog disablePrint()   - 永久關閉 print 功能
 * @method static SettleLog disableLog()     - 永久關閉 local log file 功能
 * @method static SettleLog disableStorage() - 永久關閉 GCP storage 功能
 *
 * @method static SettleLog skipPrint()     - skip print once
 * @method static SettleLog skipLog()       - skip local logging once
 * @method static SettleLog skipStorage()   - skip gcp storage logging once
 * @method static SettleLog onlyPrint()     - only print once
 * @method static SettleLog onlyLog()       - only local logging once
 * @method static SettleLog onlyStorage()   - only gcp storage logging once
 *
 * @method static SettleLog setCustomLogPath(string $path) - set a custom local log file path
 * @method static string getLocalLogPath() - get the current local log file path
 * @method static SettleLog clearCustomLogPath - clear custom local log file path (use default value: config - logging.settle.channels.file.path)
 *
 * @method static SettleLog setStorageLogPath(string $path) - set a GCP storage log file path
 * @method static string getStorageLogPath() - get the current GCP Storage log file path
 *
 * Extend the CommandLoggerTrait
 * @method static void alert(string $out)
 * @method static void error(string $out, array $context = null) - 紅底白字
 * @method static void warn(string $out, array $context = null) - 黃底白字
 * @method static void info(string $out, array $context = null)  - find the method in magic function __call - 綠字
 * @method static void line(string $out, array $context = null) - 白字
 * @method static void comment(string $out, array $context = null)  - 黃字
 * @method static string ask(string $out) - return the typing answer from console
 * 計時器功能 (Basic)
 * @method static string timing(string $out, bool $return = false) - 印出附帶距離上次呼叫 timing 或 watch 的時間間隔, $return 為 true 表示只回傳字串不 print 也不 logging
 * @method static void watch() - 開始計時 timing 前呼叫
 *
 *
 * @method static void renderException(\Exception $e, $render_type = 'editor', $verbosity = true) - 渲染 exception 成人類可讀訊息 (human-readable message)
 *      $render_type 可以代入 editor, trace, default 三種參數, 會有不同印出格式
 *
 * @example SettleLog::error('message')
 * @example SettleLog::skipLog()->skipStorage()->info('message')
 * @example SettleLog::watch(); ... SettleLog::timing('description');
 */


Class SettleLog extends Facade
{
    /**
     * Bind the class as alias 'settleLog' in facades :
     * @see SettleLogProvider::register()
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'stackLogger';
    }

    /**
     * 清除所有的設置到預設值, 摧毀 facade instance, 下次呼叫時重建新 instance
     */
    public static function destroy()
    {
        // App 與 Facade 兩個地方都必須清除指向這個 singleton 的 reference
        app()->forgetInstance(self::getFacadeAccessor());
        self::clearResolvedInstance(self::getFacadeAccessor());
    }
}
