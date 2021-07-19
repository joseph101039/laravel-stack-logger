<?php

namespace RDM\StackLogger\Facades;

interface FacadeInterface
{
    /**
     * 清除所有的設置到預設值, 摧毀 facade instance, 下次呼叫時重建新 instance
     * 由於結算 job 跑在 queue 上面, 針對這一類 facade 使用完後都應該呼叫 destroy()
     * 避免下次不完全初始化造成的 instance 設置值異常
     */
    public static function destroy();

}
