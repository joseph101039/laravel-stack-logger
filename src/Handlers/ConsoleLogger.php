<?php


namespace RDM\StackLogger\Handlers;

use RDM\StackLogger\Traits\LogRecordTransformerTrait;
use Google\Cloud\Logging\LoggingClient;
use Illuminate\Support\Str;
use Monolog\Handler\PsrHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Psr\Log\LogLevel;

/**
 *
 *
 * Monolog
 * @link https://github.com/Seldaek/monolog
 *
 * Google Client initialization
 * @link https://cloud.google.com/logging/docs/reference/libraries#using_the_client_library
 *
 * Google\Cloud\Logging\PsrLogger
 * @link http://googleapis.github.io/google-cloud-php/#/docs/google-cloud/v0.20.1/logging/psrlogger
 */
class ConsoleLogger extends PsrHandler implements LoggerInterface
{

    use LogRecordTransformerTrait;
    /**
     * @var SymfonyStyle
     */
    private $console;

    /**
     * @param string          $gcp_project_id GCP Project ID
     * @param string          $name           The name of the log to write entries to.
     * @param LoggerInterface $logger         The underlying PSR-3 compliant logger to which messages will be proxied
     * @param string|int      $level          The minimum logging level at which this handler will be triggered
     * @param bool            $bubble         Whether the messages that are handled can bubble up the stack or not
     */
    public function __construct( LoggerInterface $logger, $level = Logger::DEBUG, bool $bubble = true)
    {
        parent::__construct($logger, $level, $bubble);

        $out = new ConsoleOutput();
        $in = $input = new ArgvInput();
        $this->console = new SymfonyStyle($in, $out);
    }

    /**
     * {@inheritdoc}
     */
    public function handle(array $record): bool
    {
        if (!$this->isHandling($record)) {
            return false;
        }

        /**
         * @see \Monolog\Logger::$levels  - $record['level_name']
         */
        $function = strtolower($record['level_name']);      // level name 即為 LogLevel 種類
        $this->{$function}($record['message'], $record['context']);

        return false === $this->bubble;
    }


    #######################
    #   Logging function
    #######################

    public function emergency($message, array $context = array())
    {
        $this->block($message, $context, 'emergency', 'fg=white;bg=red;options=bold,underscore', true);
    }

    public function alert($message, array $context = array())
    {
        $length = Str::length(strip_tags($message)) + 12;

        $this->error(str_repeat('*', $length));
        $this->error('*     '.$message.'     *');
        $this->error(str_repeat('*', $length));


        $this->error('', $context);
    }

    public function critical($message, array $context = array())
    {
        $this->block($message, $context, 'critical', 'fg=white;bg=red');
    }

    public function error($message, array $context = array())
    {
        $this->block($message, $context, null, 'fg=red');
    }

    public function warning($message, array $context = array())
    {
        $this->block($message, $context, null, 'fg=black;bg=yellow;options=underscore');
    }

    public function notice($message, array $context = array())
    {
        $this->block($message, $context, '!', 'fg=yellow');
    }

    public function info($message, array $context = array())
    {
        $this->block($message, $context, null, 'fg=green');
    }

    public function debug($message, array $context = array())
    {
        $this->block($message, $context, null, 'fg=default;bg=default');
    }

    /**
     * 相容 laravel artisan console 介面
     * @param       $message
     * @param array $context
     */
    public function line($message, array $context = array())
    {
        $text = $this->messageTransform($message, $context);
        $this->console->writeln($text);
    }

    /**
     * @see LogLevel for $level
     * @param mixed  $level
     * @param string $message
     * @param array  $context
     */
    public function log($level, $message, array $context = array())
    {
        $method = strtolower($level);

        if (method_exists($this, $method)) {
            $this->{$message}($message, $context);
        } else {
            $this->line($message, $context);
        }
    }

    /**
     * Formats a message as a block of text.
     *
     * @param string|array $messages The message to write in the block
     * @param string|null  $type 訊息前的標題
     * @param string|null  $style ASCII 格式, 參考以下連結
     * @link https://symfony.com/doc/current/console/coloring.html#using-color-styles
     */
    protected function block($message, array $context, ?string $type = null, ?string $style = null, bool $padding = false)
    {
        $text = $this->messageTransform($message, $context);
        $prefix = $type ? "[{$type}] " : '';

        $padding && $this->console->newLine();  // 換行
        $this->console->writeln("<$style>" . $prefix . $text . '</>');
        $padding && $this->console->newLine();  // 換行

    }



}
