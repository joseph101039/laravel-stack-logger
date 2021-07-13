<?php


namespace RDM\StackLogger\Handlers;

use RDM\StackLogger\Interfaces\GcpLoggingInterface;
use Google\Cloud\Storage\Bucket;
use Google\Cloud\Storage\StorageClient;
use Illuminate\Support\Str;
use Monolog\Handler\PsrHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

/**
 * laravel - Creating Monolog Handler Channels
 *
 * @see https://laravel.com/docs/8.x/logging#creating-monolog-handler-channels
 *
 */
class GcpStorageLogger extends PsrHandler implements GcpLoggingInterface
{

    /**
     * @var Bucket
     */
    private $bucket;

    /**
     * @var string GCP Storage bucket 名稱
     */
    private $bucketId;

    /**
     * @var string 此 logger 在 bucket 底下的 root 路徑
     */
    private $bucketRoot;

    /**
     * @var string 使用者設置 $bucketRoot 以下的檔案路徑
     */
    private $bucketPath;

    /**
     * @var string 要上傳的檔案路徑
     */
    private $uploadFilePath;

    /**
     *
     * @param bool            $locking Attempt to lock the log file before writing to it
     * @param LoggerInterface $logger  The underlying PSR-3 compliant logger to which messages will be proxied
     * @param string|int      $level   The minimum logging level at which this handler will be triggered
     * @param bool            $bubble  Whether the messages that are handled can bubble up the stack or not
     */
    public function __construct(
        string $gcp_project_id,
        string $credential_path,
        string $bucket_id,
        string $bucket_folder,
        LoggerInterface $logger,
        $level = Logger::DEBUG,
        bool $bubble = true
    )
    {
        parent::__construct($logger, $level, $bubble);

        # 註冊憑證
        $credential_path = base_path($credential_path);
        if (!is_file($credential_path)) {
            throw new \Exception("Credential file '{$credential_path}' for GCP project '{$gcp_project_id}' is not found");
        }

        $storage_client = new StorageClient([
            'keyFilePath' => $credential_path,
            'projectId'   => $gcp_project_id,
        ]);

        # 設置 bucket 檔案路徑
        $this->bucketId = $bucket_id;
        $this->bucketRoot = $bucket_folder;
        $this->bucketPath = null;
        $this->uploadFilePath = null;


        $this->bucket = $storage_client->bucket($this->bucketId);
    }

    /**
     * {@inheritdoc}
     */
    public function handle(array $record): bool
    {
        $this->upload();
        return parent::handle($record);     // todo
    }


    /**
     * 設置檔案上傳路徑
     *
     * @param string $path
     * @param bool   $file_append
     *
     * @return GcpStorageLogger
     */
    public function setPath(string $path, bool $file_append = false)
    {
        $this->uploadFilePath = $path;
        return $this;
    }

    /**
     * 取得檔案上傳路徑
     * @return string|null
     */
    public function getPath(): ?string
    {
        return $this->uploadFilePath;
    }

    /**
     * @param string $path
     *
     * @return $this
     */
    public function setBucketPath(string $path)
    {
        $this->bucketPath = $path;
        return $this;
    }


    public function setBucketFolder(string $folder)
    {
        $this->bucketRoot = $folder;
        return $this;
    }

    /**
     * 取得當前檔案 Storage 路徑
     * @param string $filename 檔案名稱
     *
     * @return string|null
     */
    public function getObjectPath(): ?string
    {
        if (! $this->bucketPath) {
            return null;
        }

        return Str::finish($this->bucketRoot,  DIRECTORY_SEPARATOR) . $this->bucketPath;
    }

    /**
     * 取得 storage object 的公開網址
     */
    public function getLink(): ?string
    {
        $path = ltrim($this->getObjectPath(), DIRECTORY_SEPARATOR);
        return sprintf("http://storage.googleapis.com/%s/%s", $this->bucketId, $path);
    }

    /**
     * 上傳檔案
     * @return false|\Google\Cloud\Storage\StorageObject
     */
    public function upload()
    {
        if (!$object_path = $this->getPath()) {
            return false;
        }

        $options = [
            'resumable' => true,
            'name' => $this->getObjectPath(),
            'metadata' => [
                'contentType' => [
                    'media-type' => 'text/plain;charset=utf-8'
                ]
            ]
        ];

        $source = fopen($object_path, 'r');
        /**
         * 上傳檔案內容
         * @see \Google\Cloud\Storage\Bucket::upload
         */
        return $this->bucket->upload($source, $options);
    }


}
