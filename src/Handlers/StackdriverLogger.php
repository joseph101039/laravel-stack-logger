<?php


namespace RDM\StackLogger\Handlers;

use RDM\StackLogger\Interfaces\GcpLoggingInterface;
use RDM\StackLogger\Traits\LogRecordTransformerTrait;
use Google\Cloud\Logging\LoggingClient;
use Monolog\Handler\PsrHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

/**
 * laravel - Creating Monolog Handler Channels
 * @see https://laravel.com/docs/8.x/logging#creating-monolog-handler-channels
 *
 * Required the GCP IAM "Logging Admin" Role
 *
 * Google Client initialization
 * @link https://cloud.google.com/logging/docs/reference/libraries#using_the_client_library
 *
 * Google\Cloud\Logging\PsrLogger
 * @link http://googleapis.github.io/google-cloud-php/#/docs/google-cloud/v0.20.1/logging/psrlogger
 *
 * GCP Stackdriver Api Reference
 * @link https://cloud.google.com/logging/docs/samples?hl=zh-tw
 */
class StackdriverLogger extends PsrHandler implements GcpLoggingInterface
{
    /**
     * Sink 會出位置
     * @link https://cloud.google.com/logging/docs/api/tasks/exporting-logs#introduction_to_sinks
     */
    const STORAGE_SINK_DESTINATION = 0b01;      // Cloud Storage bucket,
    const BIGQUERY_SINK_DESTINATION = 0b10;     // BigQuery dataset
    const PUBSUB_SINK_DESTINATION = 0b100;      // Pub/Sub topic
    const LOGGING_SINK_DESTINATION = 0b1000;    // Cloud Logging bucket

    /**
     * @var int[] 允許的 SINk 目的地 (stackdriver 匯出目的地種類)
     */
    private $availableDestinations = [
        self::STORAGE_SINK_DESTINATION,
        self::BIGQUERY_SINK_DESTINATION,
        self::PUBSUB_SINK_DESTINATION,
        self::LOGGING_SINK_DESTINATION,
    ];

    use LogRecordTransformerTrait;

    /**
     * @var LoggerInterface[]
     */
    protected $loggers;

    /**
     * @var LoggingClient
     */
    protected $loggingClient;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string GCP Project ID
     */
    protected  $gcpProjectId;

    /**
     * @param string          $name           The name of the log to write entries to.
     * @param string          $gcp_project_id  GCP Project ID
     * @param string          $credential_path GCP CLient API 憑證路徑
     * @param bool          $batch_enabled    允許批次送出多筆 log 紀錄, 避免每次送出產生高延遲
     * @param LoggerInterface $logger         The underlying PSR-3 compliant logger to which messages will be proxied
     * @param string|int      $level          The minimum logging level at which this handler will be triggered
     * @param bool            $bubble         Whether the messages that are handled can bubble up the stack or not
     */
    public function __construct(string $name, string $gcp_project_id, $credential_path, LoggerInterface $logger, $level = Logger::DEBUG, bool $bubble = true, bool $batch_enabled = true)
    {
        parent::__construct($logger, $level, $bubble);

        $this->gcpProjectId = $gcp_project_id;
        $this->name = $name;

        # 註冊憑證
        $credential_path = base_path($credential_path);
        if (!is_file($credential_path)) {
            throw new \Exception("Credential file '{$credential_path}' for GCP project '{$this->gcpProjectId}' is not found");
        }

        $this->loggingClient = new LoggingClient($config = [
            'projectId' => $this->gcpProjectId,
            'keyFilePath' => $credential_path,
        ]);

        $this->loggers[$this->name] = $this->loggingClient->psrLogger($this->name, $options = [
            'batchEnabled' => $batch_enabled,     // 會稍微 delay 異步批次送出, 設置成 false 則會立即送出但是效能較差
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function handle(array $record): bool
    {
        $method = strtolower($record['level_name']);
        $this->getLoggers($this->name)->{$method}($record['message'], $record['context']);
        return false === $this->bubble;
    }

    /**
     * 設置 logName 名稱, 作為 Google console 頁面查詢條件
     * @param string $name log 名稱
     */
    public function setLogName(string $name)
    {
        $this->name = $name;
        return $this;
    }


    /**
     * 取得 Google 記錄檔探索工具 瀏覽頁面的 log 連結
     * @return string|null
     */
    public function getLink(): ?string
    {
        $timeRange = 'PT3H';        // 過去三小時

        /**
         * @link https://console.cloud.google.com/logs/storage?folder=true&hl=zh-tw&organizationId=965517989624&project=rdm-bbos
         */
        $logging_gcs_bucket = '_Default';
        $logging_bucket_view = '_Default';

        $queries = http_build_query([
            'query' => "logName=\"projects/{$this->gcpProjectId}/logs/{$this->name}\"",
            'timeRange' => $timeRange,
            'storageScope' => "storage,projects/{$this->gcpProjectId}/locations/global/buckets/{$logging_gcs_bucket}/views/{$logging_bucket_view}"
        ], '', ';');

        return "https://console.cloud.google.com/logs/query;$queries";
    }

    /**
     * @todo
     */
    public function export($destination, ... $parameters)
    {
        $sink_name = 'my_first_sink';       // todo modify
        $this->exportNow($destination, $sink_name, ...$parameters);
    }

    /**
     * @todo
     * 匯出多個目的地, 用多個 bit 表示, e.g. $destination = self::STORAGE_SINK_DESTINATION | self::BIGQUERY_SINK_DESTINATION
     * @param int $destinations 一個或是多個
     * @param $sink_name
     */
    protected function exportNow(int $destinations , string $sink_name, ... $parameters)
    {
        // 根據每一個指定要匯出的目的地, 逐一匯出
        foreach ($this->availableDestinations as $available_destination) {
            if ($destinations & $available_destination) {
                $destination_link = $this->getSinkDestination($available_destination, ...$parameters);  // todo
                $sink_label = $this->getSinkName($sink_name, $available_destination);

                $sink = $this->loggingClient->createSink($sink_label, $destination_link, [
                    // 過濾條件
                    'filter' => "logName=\"projects/{$this->gcpProjectId}/logs/{$this->name}\" AND resource.type=\"global\"",
                ]);

                dump($sink->info());
                // todo >>>         $sink->delete();
            }
        }
    }

    /**
     * @param int  $sink_destination BITWISE of many destinations
     * @param mixed ...$parameters
     *
     * @return string
     * @link https://cloud.google.com/logging/docs/api/tasks/exporting-logs#introduction_to_sinks
     */
    protected function getSinkDestination(int $sink_destination, ...$parameters)
    {
        switch ($sink_destination) {
            case self::STORAGE_SINK_DESTINATION:
                return sprintf("storage.googleapis.com/%s", $BUCKET_ID = $parameters[0]);
            case self::BIGQUERY_SINK_DESTINATION:
                return sprintf("bigquery.googleapis.com/projects/%s/datasets/%s", $PROJECT_ID = $parameters[0], $DATASET_ID = $parameters[1]);
            case self::PUBSUB_SINK_DESTINATION:
                return sprintf("pubsub.googleapis.com/projects/%d/topics/%s", $PROJECT_ID = $parameters[0], $TOPIC_ID = $parameters[1]);
            case self::LOGGING_SINK_DESTINATION:
                return sprintf("logging.googleapis.com/projects/%s/locations/%d/buckets/%s", $PROJECT_ID = $parameters[0], $REGION = $parameters[1], $BUCKET_ID = $parameters[2]);
        }
    }

    protected function getSinkName(string $sink_name, $destination_type): string
    {
        return $sink_name . '_' . (string) $destination_type;
    }


    private function getLoggers($name)
    {
        if (!isset($this->loggers[$name])) {
            $this->loggers[$name] = $this->loggingClient->logger($name);
        }

        return $this->loggers[$name];
    }
}
