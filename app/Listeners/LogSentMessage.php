<?php

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Http\Models\EmailTask;
use App\Http\Models\Email;
use Log;


class LogSentMessage
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  MessageSent  $event
     * @return void
     */
    public function handle(MessageSent $event)
    {
        $json = json_encode($event);
        if(strpos($json,'email_id') == false){
            return;
        }
        $task = $event->data['task'];
        if(!empty($task)){
            $data = ['status'=>1];//已发送
            EmailTask::query()->where('id', $task->id)->update($data);
            $send = DB::table('email_task')->selectRaw("sum(length(email_task.to)-length(replace(email_task.to,'@','')))  send")
                ->where('email_task.email_id',$task->email_id)
                ->where('email_task.status',1)
                ->first()['to'];
            
            $emailData = ['send'=> $send];
            Email::query()->where('id', $task->email_id)->update($emailData);
        }
       
    }
}
