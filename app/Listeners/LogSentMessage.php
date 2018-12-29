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
        $task = $event->data->task;
        if(!empty($task)){
            $data = ['status'=>1];//已发送
            EmailTask::query()->where('id', $task->id)->update($data);
            $eamil = Email::query()->where('id', $task->email_id)->first();
            $emailData = ['send'=> $eamil->send + 1];//发送数量加1
            Email::query()->where('id', $task->email_id)->update($emailData);
        }
       
    }
}
