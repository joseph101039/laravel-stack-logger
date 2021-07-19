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
     * @var string 憑證檔案路徑
     */
    private $credientialFile;

    /**
     * @var string GCP 專案名稱
     */
    private $gcpProjectId;


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
     * @var bool 上傳失敗時不要拋出例外
     */
    private $ignoreExceptions;

    /**
     * @param LoggerInterface $logger  The underlying PSR-3 compliant logger to which messages will be proxied
     * @param string|int      $level   The minimum logging level at which this handler will be triggered
     * @param bool            $bubble  Whether the messages that are handled can bubble up the stack or not
     */
    public function __construct(
        string $gcp_project_id,
        string $credential_path,
        string $bucket_id,
        string $bucket_folder,
        bool $ignore_exceptions,
        LoggerInterface $logger,
        $level = Logger::DEBUG,
        bool $bubble = true
    )
    {
        parent::__construct($logger, $level, $bubble);
        $this->bucket = null;

        # 憑證檔案
        $this->credientialFile = base_path($credential_path);
        $this->gcpProjectId = $gcp_project_id;


        # 設置 bucket 檔案路徑
        $this->bucketId = $bucket_id;
        $this->bucketRoot = $bucket_folder;
        $this->bucketPath = null;
        $this->uploadFilePath = null;
        $this->ignoreExceptions = $ignore_exceptions;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(array $record): bool
    {
        // 不在 construct 初始化, 因為在建構子初始化失敗, 不會拋出例外
        if (!$this->bucket) {
            $this->instantizeBucket();
        }

        try {
            $this->upload();
        } catch (\Exception $e) {
            if (! $this->ignoreExceptions) {
                throw $e;
            }
        }
        return parent::handle($record);
    }

    private function instantizeBucket()
    {

        if (!is_file($this->credientialFile)) {
            throw new \Exception("Credential file '{$this->credientialFile}' for 'storage' logger is not found");
        }

        $storage_client = new StorageClient([
            'keyFilePath' => $this->credientialFile,
            'projectId'   => $this->gcpProjectId,
        ]);

        $this->bucket = $storage_client->bucket($this->bucketId);
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
        if (!$path) {
            return null;
        }
        return sprintf("http://storage.googleapis.com/%s/%s", $this->bucketId, $path);
    }

    /**
     * 上傳檔案
     * @return false|\Google\Cloud\Storage\StorageObject
     */
    public function upload()
    {
        if (!$source_path = $this->getPath()) {
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

        $source = fopen($source_path, 'r');
        /**
         * 上傳檔案內容
         * @see \Google\Cloud\Storage\Bucket::upload
         */
        return $this->bucket->upload($source, $options);
    }


}
