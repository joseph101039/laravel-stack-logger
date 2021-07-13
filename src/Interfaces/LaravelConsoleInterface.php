<?php


namespace RDM\StackLogger\Interfaces;

/**
 * 相容 laravel artisan console 額外添加的 log 介面
 * @link https://laravel.com/docs/8.x/artisan#writing-output
 *
 * Interface LaravelConsoleInterface
 *
 * @package RDM\StackLogger\Interfaces
 */
interface LaravelConsoleInterface
{
    /**
     *
     * @param       $message
     * @param array $context
     */
    public function warn($message, array $context = array());

    /**
     * 相容 laravel artisan console 介面
     * @param       $message
     * @param array $context
     */
    public function comment($message, array $context = array());


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
    public function ask($message, array $context = array());

}
