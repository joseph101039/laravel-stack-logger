<?php


namespace RDM\StackLogger\Interfaces;


interface GcpLoggingInterface
{
    /**
     * 取得能夠存取/檢閱 log 檔案內容的 HTTP 連結
     * @return string|null
     */
    public function getLink(): ?string;
}
