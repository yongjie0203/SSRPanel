<?php

namespace App\Console\Commands;

use App\Components\Helpers;
use Illuminate\Console\Command;
use App\Http\Models\User;
use App\Http\Models\Email;
use Log;
use DB;
use Mail;

class EmailJob extends Command
{
    protected $signature = 'emailJob';
    protected $description = '邮件发送任务';
    protected static $systemConfig;

    public function __construct()
    {
        parent::__construct();
        self::$systemConfig = Helpers::systemConfig();
    }

    /*
     * 警告：除非熟悉业务流程，否则不推荐更改以下执行顺序，随意变更以下顺序可能导致系统异常
     */
    public function handle()
    {
        $jobStartTime = microtime(true);

        

        $jobEndTime = microtime(true);
        $jobUsedTime = round(($jobEndTime - $jobStartTime), 4);

        Log::info('执行定时任务【' . $this->description . '】，耗时' . $jobUsedTime . '秒');
    }

    public function createEmailTask(){
        $status = array('2','4');//启动和暂停状态都创建任务，暂停状态创建初始状态为暂停的任务
        $emailList = Email::query()->whereIn('status', $status)->get();
        foreach ($emailList as &$email) {
            
        }
    }
    
}
