<?php


namespace RDM\StackLogger\Traits;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\HandlerInterface;

trait ChannelAccessTrait
{

    private function getMonologHandler($channel): ?HandlerInterface
    {
        $channel = Log::channel($channel);
        $logger = $channel->getLogger();
        if ($logger instanceof \Monolog\Logger) {
            $handlers = $logger->getHandlers();
            $handler = Arr::first($handlers);
            return $handler;
        }
        return null;
    }
}
