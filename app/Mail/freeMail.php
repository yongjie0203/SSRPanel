<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Http\Models\Email;
use App\Components\Helpers;
use Illuminate\Mail\Markdown;
use DB;

class freeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $content;
    public $subject;
    public $format;//1:html、2:markdown
    public $title;
    public $template;
    public $email_id;
    public $task_id;
    public $task;
    public $mode;
    protected static $systemConfig;
    
    
    public function addRead(){        
        $url1 = self::$systemConfig['website_url'] . '/email/img/'.$this->email_id .'/'. $this->task_id . '/read?u=' .$task->to;
        $url2 = self::$systemConfig['website_url'] . '/email/img/'.$this->email_id .'/'. $this->task_id . '/read';
        $url = $this->mode == '1' ? $url1 : $url2;        
        $this->content .= "<div style='display:none;' ><img src='" .$url. "' /></div>";
    }

    public function __construct($email_id)
    {        
        $this->email_id = $email_id;
        self::$systemConfig = Helpers::systemConfig();
    }

    public function build()
    {   
        if(!empty($this->task)){            
            $this->email_id = $this->task->email_id;
        }
        if(!empty($this->task_id)){
            $this->task =  EmailTask::query()->where('id', $this->task_id)->first();
            $this->email_id = $this->task->email_id;
        }
        //有指定email_id主题、标题、格式、及内容的优先使用指定值，否则根据email_id读取
        if(!empty($this->email_id)){
            $email = Email::query()->where('id', $this->email_id)->first();
            $this->format = empty($this->format) ? $email->format : $this->format;
            $this->subject = empty($this->subject) ? $email->subject : $this->subject;
            $this->content = empty($this->content) ? $email->content : $this->content;
            $this->title = empty($this->title) ? $email->title : $this->title;
            $this->template = empty($this->template) ? $email->template : $this->template;
            $this->mode = empty($this->mode) ? $email->mode : $this->mode;
        }
        if(!empty($this->subject)){
            $this->subject($this->subject);
        }
        if(!empty($this->format)){           
           //如果是markdown格式先转换成html
           if("2" == $this->format){//Markdown
                $this->content = Markdown::parse($this->content);
           }
        }
        $this->addRead();
        $data = array('title'=> $this->title,'content'=>$this->content);
        if(!empty($this->template)){           
           //1为使用系统统一空白模板，0为不使用模板
           if("1" == $this->template){//Markdown
                $this->view("emails.freeMail")->with($data);
           }
        }
        else{
            //html作为默认发送
            $this->html($this->content);
        }
        
        return $this;
    }    
}
