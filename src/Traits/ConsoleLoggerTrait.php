<?php


namespace RDM\StackLogger\Traits;


use RDM\StackLogger\Handlers\ConsoleLogger;
use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandlerContract;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

trait ConsoleLoggerTrait
{
    use ChannelAccessTrait;

    /**
     * @var string Channel 名稱
     */
    private static $consoleChannel = 'console';

    /**
     * 渲染 exception 成人類可讀訊息 (human-readable message)
     * @param \Exception $e - Exception or Trace array when 'editor-input-trace' render type is selected
     * @param string $render_type - 渲染類型
     * @param bool $verbosity - 詳細程度
     * @return void
     */
    public function renderException($e, $render_type = 'editor', $verbosity = true)
    {
        $level = $verbosity ? OutputInterface::VERBOSITY_VERBOSE: OutputInterface::VERBOSITY_NORMAL;

        switch ($render_type) {
            case 'editor':      // editor + traceback (pretty format)
                $console_out =  new ConsoleOutput();
                $console_out->setVerbosity($level);
                /** @see \Illuminate\Foundation\Exceptions\Handler::renderForConsole */
                app(ExceptionHandlerContract::class)->renderForConsole($console_out, $e);
                break;

            case 'trace':       // only traceback (pretty format)
                /** @see \Illuminate\Foundation\Exceptions\Handler::renderForConsole */
                $console_out = new ConsoleOutput;
                $console_out->setVerbosity($level);  // print more or less debug message
                (new ConsoleApplication)->renderThrowable($e, $console_out);
                break;
// todo remove >>> customized type is not supported
//
//            case 'editor-settle-child-process':     // child process 難以傳遞例外 (trace 無法序列化), 特製專門處理流程
//                $console_out =  new ConsoleOutput();
//                $console_out->setVerbosity($level);
//                $writer = (new \NunoMaduro\Collision\Writer());
//                $writer->setOutput($console_out);
//                $writer->write(new \App\Exceptions\SettleChildProcExceptInspector($e));
//                break;

            case 'default':
            default:            // print traceback string + write log
                $this->getConsoleChannel()->log(LogLevel::ERROR, "Job Exception: " . $e->getMessage() . PHP_EOL . substr($e->getTraceAsString(), 0, $verbosity ? 1000 : 300));
        }
    }


    /**
     * 僅作為 console 輸出, 不寫 log
     *
     * 相容 laravel artisan console 介面
     *
     * @param       $message
     * @param array $context
     *
     * @return false|string $answer
     */
    public function ask($message, array $context = array())
    {
        $this->getConsoleChannel()->line($message, $context);
        echo '>';
        $handle = fopen("php://stdin", "r");
        throw_if(!$handle, new \Exception("ask failure"));
        $answer = fgets($handle);
        fclose($handle);
        return $answer;
    }


    /**
     * @return ConsoleLogger|\Monolog\Handler\HandlerInterface|null
     */
    protected function getConsoleChannel()
    {
        return $this->getMonologHandler(self::$consoleChannel);
    }
}
