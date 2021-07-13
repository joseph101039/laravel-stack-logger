<?php


namespace RDM\StackLogger\Traits;


trait LogRecordTransformerTrait
{
    /**
     * 結合 log message 與 context array 成為單一字串
     * @param       $message
     * @param array $context
     *
     * @return string
     */
    private function messageTransform($message, array $context): string
    {
        if ($context) {
            return $message . PHP_EOL . var_export($context, true);
        }

        return $message;
    }



}
