<?php


namespace RDM\StackLogger\Traits;


use RDM\StackLogger\Handlers\FileLogger;
use Monolog\Handler\HandlerInterface;

trait FileLoggerTrait
{
    use ChannelAccessTrait;

    /**
     * @var string Channel 名稱
     */
    private static $fileChannel = 'file';

    /**
     * 設置檔案路徑
     *
     * @param string $path
     * @return FileLoggerTrait
     */
    public function setCustomLogPath(string $path)
    {
        $this->getFileChannel()->setPath($path);
        return $this;
    }

    /**
     * 取得檔案路徑
     * @return string|null
     */
    public function getLocalLogPath()
    {
        return $this->getFileChannel()->getPath();
    }

    public function clearLogFile()
    {
        return $this->getFileChannel()->clearFile();
    }

    public function deleteLogFile()
    {
        return $this->getFileChannel()->deleteFile();
    }


    /**
     * @param \Exception $e - 例外
     * @param string $export_html_path - 匯出 html 的完整檔案路徑
     * @return null|true
     * @ref Github flip/whoops
     */
    public function dumpExceptionPrettyPage($e, $export_html_path)
    {
        if (! ($e instanceof \Exception)) {
            return null;
        }
        $whoops = new \Whoops\Run;
        $whoops->allowQuit(false);
        $whoops->writeToOutput(false);

        $pretty_page_handler = new \Whoops\Handler\PrettyPageHandler();
        $pretty_page_handler->handleUnconditionally(true);  // 就算 PHP_SAPI 是 cli 也要處理, 預設cli 還境 whoops不動作
        $whoops->pushHandler($pretty_page_handler);

        $html = $whoops->handleException($e);
        $this->dump($html, $export_html_path);
        return true;
    }

    /**
     * Write object to file
     * @param $variable
     * @param $file_name
     * @return false|int
     */
    private function dump($variable, $file_name)
    {
        $folder = dirname($file_name);
        if(!is_dir($folder)) {
            $old = umask(0);
            @mkdir($folder, 0775, true);       // 修改權限從 777 改成 755 防止 permission denied 錯誤
            umask($old);
        }

        $content = is_string($variable) ? $variable:  var_export($variable, true);
        $len = file_put_contents($file_name, $content, FILE_BINARY);
        return $len;
    }

    /**
     * @return FileLogger|HandlerInterface|null
     */
    private function getFileChannel()
    {
        return $this->getMonologHandler(self::$fileChannel);
    }


}
