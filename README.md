# Laravel Stack Logger 

## Introduction

<p>
使用 Laravel 框架的 logging 與 facades 功能組成的複合型工具, 
可以使用 facade 在程式中任何一處呼叫, 
方便開發使用
</p>

## Table of contents

1. [安裝步驟](#chapter-1)
2. [Logger 各別介紹與設置](#chapter-2)
3. [Logger 使用方法](#chapter-3)
4. [程式碼範例](#chapter-4)

## 1. 安裝步驟 <a name="chapter-1"></a> <sub>[[返回目錄]](#table-of-contents)</sub>

###(1) 執行安裝指令:

```bash 
    composer require trd-rdm/stack-logger --with-all-dependencies
```

###(2) 註冊 Service Provider 以使用 Log Facade:

打開 `app/config.php` 在 providers 陣列底下添加 ```RDM\StackLogger\StackLoggerServiceProvider``` 註冊 facade

```php
'providers' => [
     /*
     * Package Service Providers...
     */
    RDM\StackLogger\StackLoggerServiceProvider::class,
]
```

###(3) 添加註冊多種 loggers 以支援 facade 功能:

打開 `config/logging.php` 添加以下 logger 到 <i>channels</i> 陣列中
```php
return [
    
    'channels' => [
        /**
         * Log Facade
         * 主要 logger , 以 stack driver 結合多個 custom log 同時呼叫
         * 加入 channels 的 driver 可以在 Facade 中呼叫
         */
        'stack_logger'   => [
            'driver'            => 'stack',
            'channels'          => ['console', 'file', 'storage', 'stackdriver', 'telegram'],        // 指定多種 log 頻道, 可以自由添加移除
            'ignore_exceptions' => false,
        ],    
        /**
         * 1. 螢幕輸出: 彩色字體
         */
        'console'     => [
            'driver'    => 'monolog',
            'level'     => env('LOG_LEVEL', 'debug'),   // The minimum logging level at which this handler will be triggered
            'handler'   => RDM\StackLogger\Handlers\ConsoleLogger::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with'      => [
            ],
        ],
        /**
         * 2. 文件檔案 : 需要指定寫入檔案路徑
         */
        'file'        => [
            'driver'    => 'monolog',
            'level'     => env('LOG_LEVEL', 'debug'),
            'handler'   => RDM\StackLogger\Handlers\FileLogger::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with'      => [
                // handler constructor 額外參數
                'default_path' => storage_path('default.log'),    // 預設檔案路徑
                'permission'   => 0775,       // The log file's permissions
                'locking'      => false,         // 	Attempt to lock the log file before writing to it
            ],
        ],
        /**
         * 3. GCP Storage 儲存空間 : 需要指定本地上傳檔案路徑 與 storage 路徑
         *
         * 結果請至 GCP Cloud Storage - Browser查看
         *
         * @link https://console.cloud.google.com/storage/browser
         */
        'storage'     => [
            'driver'    => 'monolog',
            'level'     => env('LOG_LEVEL', 'debug'),
            'handler'   => RDM\StackLogger\Handlers\GcpStorageLogger::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with'      => [
                'gcp_project_id'  => 'your-gcp-project',                  // GCP Project
                'credential_path' => 'your-local-credential-file-path',          // 憑證路徑 (專案根目錄為 root)

                'bucket_id'     => 'your-storage-bucket-id',     // GCP Storage bucket 名稱
                'bucket_folder' => 'bucket-folder1/folder2',       // 此 logger 在 bucket id 底下的 root 路徑
                'bucket_path'   => '',                          // bucket_folder 底下的檔案路徑, 需要使用者用 setPath() 設置後才能寫入
            ],
        ],

        /**
         * 4. GCP Stackdriver : 需要指定 log 名稱
         *
         * 輸出結果請至 Google Console 查詢 Log
         *
         * @link https://console.cloud.google.com/logs
         */
        'stackdriver' => [
            'driver'    => 'monolog',
            'level'     => env('LOG_LEVEL', 'debug'),
            'handler'   => RDM\StackLogger\Handlers\StackdriverLogger::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with'      => [
                'name'            => 'your-stackdriver-logging-name',        // log 名稱 (在 google console 查詢過濾條件 logName 的值)
                'gcp_project_id'  => 'rdm-bbos',
                'credential_path' => '/rdm-bbos.json',          // 憑證路徑 (專案根目錄為 root)
                'batch_enabled'   => true,                      // 允許批次送出多筆 log 紀錄, 避免每次送出產生高延遲
            ],
        ],
        /**
         * 5. Telegram 通知 : 需要指定 接收者 chat_id 與 api_key 機器人群組
         */
        'telegram'    => [
            'driver'    => 'monolog',
            'level'     => env('LOG_LEVEL', 'debug'),   // The minimum logging level at which this handler will be triggered
            'handler'   => RDM\StackLogger\Handlers\TelegramLogger::class,
            'formatter' => env('LOG_STDOUT_FORMATTER'),
            'with'      => [
                'chat_ids'              => ['1378441015'],  // 收信者
                'disable_notification' => false,        // 關閉通知提示音
                'api_key'              => (env('APP_ENV') === 'prod')
                    ? env('TELEGRAM_BOT_TOKEN', 'xxxxx')    // 正式 Bot
                    : env('TELEGRAM_BOT_TOKEN', 'xxxxx'),   // 測試 Bot
            ],
        ],

    ]
];
```
調整 stack_logger 的 channels 以選擇你需要的 channels, 並且針對下面的五種 log 設置參數。
這五種 log 都是 monolog log, 關於這些參數定義可以參考
 [Laravel Logging - Creating Monolog Handler Channel](https://laravel.com/docs/8.x/logging#creating-monolog-handler-channels)
<br/>

## 2. Logger 各別介紹與 config 設置<a name="chapter-2"></a> <sub>[[返回目錄]](#table-of-contents)</sub>

打開 `config/logging.php` 中可以看到新添加入的 logger 底下將各別作介紹。

1. ##### stack_logger

    首先 stack_logger 即 [Log Facade](src/Facades/SettleLog.php) 功能,
    他是一種 [Laravel Log stack 類型](https://laravel.com/docs/8.x/logging#building-log-stacks), 
    
    需要設置的參數有:
    + channels: 添加或移除你需要的 log 名稱, 可以開啟或關閉指定 log 功能, 
    讓你在呼叫此 log 時可以一次執行多種 channels 中的 log,
    而不用各別呼叫。

    舉例來說呼叫以下這一行
    ```php
    Log::channel('stack_logger')->info('info message');
    ```
    相當於執行以下這幾行
    ```php
    Log::channel('console')->info('info message');
    Log::channel('file')->info('info message');
    Log::channel('storage')->info('info message');
    Log::channel('stackdriver')->info('info message');
    Log::channel('telegram')->info('info message');
    ```

2. ##### console

    螢幕輸出, 即傳統意義上的 print, 
    只是依據 [log 等級](https://laravel.com/docs/8.x/logging#log-levels)
    會有不同的文字色彩, 依據訊息重要程度增加辨識度。
    
    需要設置的參數有:
    + level : 什麼等級(含)以上的 log 才需要被執行,
    例如將 level 設置為 'info', 則 debug 等級的 log 不會印到螢幕上
     
3. ##### file
    寫到本地檔案。
    
     需要設置的參數有:
     + default_path: 指定預設寫入的 log 檔案路徑

4. ##### storage

    上傳檔案到 Google Cloud Storage,
    使用此 log 的話每寫入一行 log 就會, 
    同步 file logger 設置的檔案內容到你指定的 Storage Bucket 檔案之中。
    
    需要設置的參數有:
    + gcp_project_id :GCP 專案名稱 
    + credential_path: GCP API 憑證檔案, 請設置本地相對於專案根目錄的檔案路徑
    + bucket_id: Storage Bucket 名稱
    + bucket_folder: Storage 檔案在 bucket 底下的資料夾, 通常執行時不變動
    + bucket_path: Storage 檔案在 bucket_folder 底下的路徑

5. ##### stackdriver

    寫入的 log 會透過 GCP API 傳送到 [Google Stackdriver](https://cloud.google.com/logging/docs/basic-concepts?hl=zh-tw) 儲存,
    可以透過 [GCP Log 記錄檔探索工具](https://cloud.google.com/logging/docs/view/logs-viewer-interface?hl=zh-tw) 查看,
    查詢時的過濾條件就可以帶入 **logName="your-stackdriver-logging-name"**,
    以查到執行結果, 目前此 stackdriver log 紀錄是 30 日後會自動消失。
    
    需要設置的參數有:
    + gcp_project_id: GCP 專案名稱 
    + credential_path: GCP API 憑證檔案, 請設置本地相對於專案根目錄的檔案路徑
    + name: log 名稱, 在 google console 查詢過濾條件 logName 的值
    + [batch_enabled: 允許[批次送出多筆 log 紀錄](https://cloud.google.com/logging/docs/setup/php?hl=zh-tw#enabling_batching_option), 
   避免每次送出 API 產生高延遲, 影響程式效率
   
6. ##### telegram

    透過 telegram bot 通知傳送訊息給指定一位或多位收訊者。
    
    需要設置的參數有:
    + chat_ids: 收信者的 telegram user id
    + disable_notification: 關閉通知提示音
    + api_key: 註冊機器人時 Telegram 官方提供的 Telegram Bot API Token 

## 3. Logger 使用方法<a name="chapter-3"></a> <sub>[[返回目錄]](#table-of-contents)</sub>

你可以使用整合進 stack_logger driver 的 SettleLog Facade (以下簡稱 **SettleLog**)<br/>
也可以使用 laravel 原生的 Log Facade (以下簡稱 **Log**) 執行 logging。<br/>

但是 **Log** 僅支援 [PSR-3 標準的介面函式](https://www.php-fig.org/psr/psr-3/), 
你沒有辦法在程式執行期動態修改 `config/logging.php` 中的參數, 
以修改 logger 參數, 比如設置檔案路徑等, 
執行期針對 config 的異動不會影響到已經建立靜態 (static) 實例的 **Log**。

而 **SettleLog** 提供一些介面讓你能在執行期設置參數, 
以及整合部分 logging 以外的除錯函式方便程式人員開發。


以下為 **SettleLog** 支援的函式介紹

<a name="interface1"></a>

| PHP PSR-3 介面, 所有 logger 共同支援 | [範例1](#example1) |
| :-------------------------------------------------------------| :----------------- |
| `static void emergency(string $message, array $context = [])` | 系統不可用 |
| `static void alert(string $message, array $context = [])` | **必須**馬上採取行動 |
| `static void critical(string $message, array $context = [])` | 緊急狀況 |
| `static void error(string $message, array $context = [])` | 運行時出現的錯誤，不須要馬上採起行動，但必須記錄下來以備檢測 |
| `static void warning(string $message, array $context = [])` | 出現非錯誤性的異常 |
| `static void notice(string $message, array $context = [])` | 通常性重要的事件 |
| `static void info(string $message, array $context = [])` | 重要事件  |
| `static void debug(string $message, array $context = [])` | debug 詳情 |
| `static void log($level, string $message, array $context = [])` | 任意等級的日誌記錄, [$level](#https://github.com/php-fig/log/blob/master/Psr/Log/LogLevel.php) 僅限連結中任一值 |

<a name="interface2"></a>

| 其他相容 [Laravel Console](https://laravel.com/docs/8.x/artisan#command-io) 介面 | |
| :-------------------------------------------------------------| :----------------- |
| `static void line(string $out, array $context = [])` | 同 debug() |
| `static void comment(string $out, array $context = [])` | 同 debug() |
| `static void warn(string $out, array $context = [])` | 同 warning() |
| `static string ask(string $out)` | 詢問問題並等待使用者輸入, 回傳使用者答案 |

<a name="interface3"></a>

| 本地 Log 檔案相關 - [file logger](#file) | [範例2](#example2) |
| :-------------------------------------------------------------| :----------------- |
| `static self setLocalLogPath(string $path)` | 設置 log 檔案路徑 |
| `static self setCustomLogPath(string $path)` | 同 setLocalLogPath() |
| `static string getLocalLogPath()` | 取得 log 檔案路徑 |
| `static self clearLogFile()`      | 清除 log 檔案內容 |
| `static self deleteLogFile()`     | 刪除 log 檔案 |

<a name="interface4"></a>

| GCP Storage 檔案處理相關 - [storage logger](#storage) |   |
| :-------------------------------------------------------------| :----------------- |
| `static self setStorageBucketFolder(string $path)` |  設置 Bucket id 底下的跟資料夾路徑 |
| `static self setStorageBucketPath(string $path)` |    設置 BucketFolder 底下的上傳物件路徑 |
| <code>static string&#124;null getStorageObjectPath()</code>      |     取得 Storage 上傳物件的完整路徑 (bucket folder + bucket path) |
| <code>static string&#124;null getStorageLogPath()</code> |        同 getStorageObjectPath() |
| <code>static string&#124;null getStoragePublicUrl()</code> |      取得 Storage object 的公開網址 |
| <code>static self setStorageUploadFilePath(string $path)</code> | 設置 Storage 上傳本地檔案的路徑 |
| <code>static string&#124;null getStorageUploadFilePath()</code> | 取得 Storage 被上傳本地檔案的路徑 |

<a name="interface5"></a>

| GCP StackDriver 相關 - [stackdriver logger](#stackdriver) | [範例2](#example2)  |
| :-------------------------------------------------------------| :----------------- |
| <code>static self setStackDriverLogName(string $name)</code> | 設置 logName 名稱, 作為 [Google console 頁面查詢條件](https://cloud.google.com/logging/docs/view/overview#search_performance) |
| <code>static string&#124;null getStackDriverLink()</code> | 取得 Google 記錄檔探索工具 瀏覽頁面的 log 連結 |

<a name="interface6"></a>

| Telegram 相關 - [telegram logger](#telegram) | [範例3](#example3) |
| :-------------------------------------------------------------| :----------------- |
| `static self setTelegramChatIds(array $chat_ids)` | 設置接收通知者 |
| `static self addTelegramChatId(string $chat_id)` | 添加一位通知者 (傳入 user id) |
| `static self removeTelegramChatId(string $chat_id) ` | 移除一位通知者 (傳入 user id) |
| `static self disableTelegramNotification(string $chat_id)` | 關閉通知提示音 |
| `static self enableTelegramNotification(string $chat_id)` | 開啟通知提示音 |

<a name="interface7"></a>

| 例外處理 | [範例1](#example1) |
| :-------------------------------------------------------------| :----------------- |
| `static void renderException(\Exception $e, $render_type = 'editor', $verbosity = true)` | 渲染 exception 成漂亮可讀訊息 (human-readable message), $render_type 可以代入 editor, trace, default 三種參數, 會有不同印出格式 |
| `static void dumpExceptionPrettyPage(\Exception $e, string $export_html_path)` | 匯出 exception 成一個 html 檔案, 需要指定匯出 html 的完整檔案路徑 |
| `static void failIf($condition, $error_message, $success_message = '', $throw = false)` | 如果 condition 為假, log $error_message, 否則 log $success_message, 如果 $throw 為真會拋出例外 |
| `static void failUnless($condition, $error_message, $success_message = '', $throw = false)` | 如果 condition 為真, log $error_message, 否則 log $success_message, 如果 $throw 為真會拋出例外 |

<a name="interface8"></a>

| 計時器功能 | [範例4](#example4), [範例5](#example5) |
| :-------------------------------------------------------------| :----------------- |
| `static void watch()` | 開始計時, 在第一次呼叫 timing 前呼叫 |
| `static string timing(string $out, bool $return = false)` | 印出附帶距離上次呼叫 timing 或 watch 的時間間隔, $return 為 true 表示只回傳字串而不會 logging |
| `static Timer createTimer()` | 創立一個計時器 |
| `static Timer[] createTimers(int $count_of_timers = 1)` | 創立多個計時器 |

<a name="interface9"></a>

| MySQL Log 相關 | [範例6](#example6) |
| :-------------------------------------------------------------| :----------------- |
| `static self enableGeneralLog($connection = null)` | 啟用 General log, 每一項查詢將會被記錄到表 mysql.general_log |
| `static self disableGeneralLog($connection = null) ` | 停用 General log |
| `static self clearGeneralLog($connection = null)` | 清除  mysql.general_log |
| `static self enableSlowQueryLog($long_query_time = 1, $connection = null)` | 啟用 Slow log, 每一項超過 $long_query_time 秒的查詢, 將會被記錄到表 mysql.slow_log |
| `static self disableSlowQueryLog($connection = null)` | 停用 Slow log |
| `static self clearSlowQueryLog($connection = null)` | 清除 mysql.slow_log |

| 其他 | |
| :-------------------------------------------------------------| :----------------- |
| `static void destroy()` | Facade 摧毀自身實例, 可以用在當想要清除設定值的時候, 例如在 laravel queue 的 job 結尾呼叫, 避免設定值影響到下一個 job |


## 程式碼範例<a name="chapter-4"></a> <sub>[[返回目錄]](#table-of-contents)</sub>

1. Log 成功與錯誤訊息 
<a name="example1"></a>
<sub>[跳至函式](#interface1)</sub>

    ```php
    use RDM\StackLogger\Facades\SettleLog;
    use \Psr\Log\LogLevel;
    
    // 成功訊息 
    SettleLog::info('success');

    // 同上成功訊息
    SettleLog::log(LogLevel::INFO, 'success', ['success' => true, 'result' => []]);
    
    try {
            // ...
    } catch (\Exception $exception) {
        // 錯誤訊息
        SettleLog::error('something wrong');
        
        // 印出漂亮格式的錯誤訊息
        SettleLog::renderException($exception);
    
        // 匯出例外訊息成 HTML 檔
        SettleLog:dumpExceptionPrettyPage($exception, storage_path('exception.html'));
    }
    ```

2. 設置 Log 路徑 
<a name="example2"></a>
<sub>[跳至函式](#interface3)</sub>

    ```php
    use RDM\StackLogger\Facades\SettleLog;
    
    // 設置路徑參數
    SettleLog::setLocalLogPath(storage_path('logs/err.log'))    // 設置 log 檔案參數
        ->setStackDriverLogName('stack-driver-log-name');       // 設置 stackdriver log 名稱
       
    SettleLog::info("message");
    
    // 讀取路徑參數
    fprintf("All messages are written at file %s", SettleLog::getLocalLogPath());   // 取得 log 檔案路徑
    fprintf("All messages are export to stackdriver: %s", SettleLog::getStackDriverLink());     // 取得 stackdriver log 存取連結
    ```

3. telegram 
<a name="example3"></a>
<sub>[跳至函式](#interface6)</sub>

    ```php
    use RDM\StackLogger\Facades\SettleLog;
    
    $john_user_id = '12345';
    SettleLog::setTelegramChatIds([$john_user_id])  // 設置收訊者
        ->disableTelegramNotification();        // 關閉通知聲響   

    SettleLog::emergency('SOS! System Crashed');    // 送出通知
    ```

4. 計時器 1
<a name="example4"></a>
<sub>[跳至函式](#interface8)</sub>

    ```php
   use RDM\StackLogger\Facades\SettleLog;
   
   SettleLog::watch();     // 開始計時
   
   /*
   * Code Block 1
   */
   
   SettleLog::timing('執行 Code Block 1');
   
   /*
   * Code Block 2
   */
   SettleLog::timing('執行 Code Block 2');
   
   /**
   範例輸出結果:
   執行 Code Block 1 1.500 sec
   執行 Code Block 2 1.500 sec
   */
   ```
   
5. 計時器 2 
<a name="example5"></a>
<sub>[跳至函式](#interface8)</sub>

    ```php
   use RDM\StackLogger\Facades\SettleLog;
   
    $timer = SettleLog::createTimer();
    $timer->setDescription('執行 Code Block 1');
    
    for($i = 5; $i > 0; $i--) {
      $timer->start();
      sleep(1);
      $timer->stop();
       sleep(2);
    }
    echo (string)$timer;
   
   /**
   範例輸出結果:
   執行 Code Block 1 花費 : 5.01 秒
   */
   ```
   
6. SQL Log 
<a name="example6"></a>
<sub>[跳至函式](#interface9)</sub>

    ```php
   use RDM\StackLogger\Facades\SettleLog;
   use Illuminate\Support\Facades\DB;
   
   $connection = 'mysql';
   SettleLog::enableMysqlGeneralLog($connection);   // 啟用 mysql general log
   
   DB::connection($connection)->table('users')->get(); // send query
   
   SettleLog::disableMysqlGeneralLog($connection);   // 停用 mysql general log
   ```



