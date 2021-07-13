<?php


namespace RDM\StackLogger\Traits;


use Illuminate\Support\Facades\DB;

trait MysqlLoggingTrait
{

    #######################################
    #
    #   General Query Log - 所有查詢
    #
    #######################################

    public function clearQueryLog($connection = null)
    {
        DB::connection($connection)->statement("truncate mysql.general_log");
    }

    public function enableQueryLog($connection = null)
    {
        DB::connection($connection)->statement("set global general_log='ON'");
        DB::connection($connection)->statement("set global log_output='TABLE'");
    }

    public function disableQueryLog($connection = null)
    {
        DB::connection($connection)->statement("set global general_log='OFF'");
    }

    #######################################
    #
    #   Slow Query Log - 慢查詢
    #
    #######################################
    /**
     * 啟用表格紀錄 慢查詢 logging, 結果將被寫入 mysql.slow_log
     * @param int  $long_query_time 幾秒以上被視為慢查詢
     * @param null $connection
     */
    public function enableSlowQueryLog($long_query_time = 1, $connection = null)
    {
        DB::connection($connection)->statement("set global slow_query_log='ON'");
        DB::connection($connection)->statement("set global log_output='TABLE'");
        DB::connection($connection)->statement("set global long_query_time={$long_query_time}");
    }

    public function disableSlowQueryLog($connection = null)
    {
        DB::connection($connection)->statement("set global slow_query_log='OFF'");
    }

    public function clearSlowQueryLog($connection = null)
    {
        DB::connection($connection)->statement("truncate mysql.slow_log");
    }
}
