<?php

namespace App\Console\Commands;

use App\Components\Helpers;
use Illuminate\Console\Command;
use App\Http\Models\User;
use App\Http\Models\Email;
use App\Http\Models\EmailTask;
use App\Http\Models\EmailRangeGroup;
use Log;
use DB;
use Mail;

class EmailJob extends Command
{
    protected $signature = 'emailJob';
    protected $description = '邮件发送任务';
    protected static $systemConfig;
    private $hourLimit = 500;//邮件服务器限制的，每小时最大发送量
    private $singleLimit = 400;//邮件服务器限制的，单封邮件最大收件人数量

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

        $this->createEmailTask();

        $jobEndTime = microtime(true);
        $jobUsedTime = round(($jobEndTime - $jobStartTime), 4);

        Log::info('执行定时任务【' . $this->description . '】，耗时' . $jobUsedTime . '秒');
    }

    public function createEmailTask(){
        $status = array('2','4');//启动和暂停状态都创建任务，暂停状态创建初始状态为暂停的任务
        $emailList = Email::query()->whereIn('status', $status)->get();
        foreach ($emailList as &$email) {           
                $group = $this->xmlToArray($email->expression);     
                $userQuery = $this->getUserQuery($group->userStatus,$group->userLabel,$group->userLevel );
                $usersInfo = $userQuery->get()->toArray();
                $users = array_unique(array_merge(array_column($usersInfo,'to'),explode(",",$email->to)));
                $taskStatus = $email->status == '2' ? 0 : 2;// $taskStatus0等待发送2暂停发送
                if('1'== $email->mode){//单封单人
                    //根据每小时可发送数量，计算发送间隔
                    $wait = 60*60/$this->hourLimit;
                    $total = 0;
                    foreach($users as $key => $user){      
                        $total = $total + $wait;
                        $emailTask = new EmailTask();
                        $emailTask->email_id = $email->id;
                        $emailTask->to = $user;
                        $emailTask->status = $taskStatus;//等待发送
                        $emailTask->start_at = strtotime( " +".$total." seconds " );
                        $emailTask->created_at = date('Y-m-d H:i:s');
                        $emailTask->save();
                    }
                }
                if('2'== $email->mode){//单封多人人
                    $tasks = array_chunk($users,$this->singleLimit);
                    $wait = 60*60/$this->hourLimit;
                    $total = 0;
                    foreach($tasks as $key => $task){      
                        $total = $total + $wait;
                        $emailTask = new EmailTask();
                        $emailTask->email_id = $email->id;
                        $emailTask->to = join(';',$task);
                        $emailTask->status = $taskStatus;//等待发送
                        $emailTask->start_at = strtotime( " +".$total." seconds " );
                        $emailTask->created_at = date('Y-m-d H:i:s');
                        $emailTask->save();
                    }
                }              
    
         
        }
    }
    
    //根据过滤条件构建query
    private function getUserQuery($u,$t,$l){       
              
         $userQuery = DB::table('user')->selectRaw('DISTINCT (case when email_blacklist.email is not null and email_blacklist.forward is not null then email_blacklist.forward ELSE `user`.username end) `to`'); 
         $userQuery ->leftJoin('email_blacklist',function($join){
              $join->on('email_blacklist.email', '=', 'user.username')
                   ->where('email_blacklist.status', '=', 1)
                   ->whereNotNull('email_blacklist.forward');
         });
         if (!empty($t)) {
             $userQuery ->leftJoin('user_label', 'user.id', '=', 'user_label.user_id');
             $userQuery ->whereIn('user_label.label_id', $t);
         }        
         if(!empty($l)){        
             $userQuery->whereIn('user.level', $l);
         }
         if($u!=""){        
             $userQuery->whereIn('user.status', $u);
         }              
        
         return  $userQuery;        
    }
    
    //把conditions xml解析为分组条件数组
    private function xmlToArray($xml)
    {    
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $s = simplexml_load_string($xml);
        $group = new EmailRangeGroup();
        $group->userStatus = array();
        $group->userLevel = array();
        $group->userLabel = array();    
        if(!empty($s->xpath("/conditions/condition[@table='user' and @column='status' and @relation='or']"))){
            foreach($s->xpath("/conditions/condition[@table='user' and @column='status' and @relation='or']") as $v){
                $group->userStatus = array_unique(array_merge($group->userStatus,  explode(',',$v)));
            }
        }
        if(!empty($s->xpath("/conditions/condition[@table='user' and @column='level' and @relation='or']"))){
            foreach($s->xpath("/conditions/condition[@table='user' and @column='level' and @relation='or']") as $v){
                $group->userLevel = array_unique(array_merge($group->userLevel,  explode(',',$v)));
            }
        }
        if(!empty($s->xpath("/conditions/condition[@table='user_label' and @column='label_id' and @relation='or']"))){
            foreach($s->xpath("/conditions/condition[@table='user_label' and @column='label_id' and @relation='or']") as $v){
                $group->userLabel = array_unique(array_merge($group->userLabel,  explode(',',$v)));
            }
        }
        return $group;
    }
    
}
