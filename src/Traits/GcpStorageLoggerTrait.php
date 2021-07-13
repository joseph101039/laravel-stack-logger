<?php


namespace RDM\StackLogger\Traits;

use RDM\StackLogger\Handlers\GcpStorageLogger;
use Monolog\Handler\HandlerInterface;

trait GcpStorageLoggerTrait
{
    use ChannelAccessTrait;

    /**
     * @var string Channel 名稱
     */
    private static $storageChannel = 'storage';


    ################################
    #
    #   GCP Storage function
    #
    ################################
    /**
     * 設置 bucket id 底下的跟資料夾路徑, 以後每一個檔案都會建立在此路徑之下, 若是沒有設置則以 config/logging 內設置的值為預設值
     *
     * @param string $folder
     *
     * @return self
     */
    public function setStorageBucketFolder(string $folder)
    {
        $this->getStorageChannel()->setBucketPath($folder);
        return $this;
    }

    /**
     * 設置 BucketFolder 底下的上傳物件路徑
     *
     * @param string $path
     *
     * @return $this
     */
    public function setStorageBucketPath(string $path)
    {
        $this->getStorageChannel()->setBucketPath($path);
        return $this;
    }


    /**
     * 取得 Storage 上傳物件的完整路徑 (bucket folder + bucket path)
     *
     * @return string|null
     */
    public function getStorageObjectPath()
    {
        return $this->getStorageChannel()->getObjectPath();
    }

    /**
     * 相容舊版介面
     * @return string|null
     */
    public function getStorageLogPath()
    {
        return $this->getStorageObjectPath();
    }

    /**
     * 取得 storage object 的公開網址
     * @return string|null
     */
    public function getStoragePublicUrl()
    {
        return $this->getStorageChannel()->getLink();
    }

    ################################
    #
    #   Upload File Path function
    #
    ################################

    /**
     * 設置 Storage 被上傳本地檔案的路徑
     * @param string $path
     *
     * @return $this
     */
    public function setStorageUploadFilePath(string $path)
    {
        $this->getStorageChannel()->setPath($path);
        return $this;
    }

    /**
     * 取得 Storage 被上傳本地檔案的路徑
     * @return string|null
     */
    public function getStorageUploadFilePath()
    {
        return $this->getStorageChannel()->getPath();
    }


    /**
     * @return GcpStorageLogger|HandlerInterface|null
     */
    private function getStorageChannel()
    {
        return $this->getMonologHandler(self::$storageChannel);
    }
}
