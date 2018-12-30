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
    public $read_img_url;
    protected static $systemConfig;
    
    


    public function __construct($email_id)
    {        
        $this->email_id = $email_id;
        self::$systemConfig = Helpers::systemConfig();
    }

    public function build()
    {   
        if(!empty($this->task)){            
            $this->email_id = $this->task->email_id;
            $this->task_id = $this->task->id;
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
        if(!empty($this->email_id) && !empty($this->task)){
            $url1 = self::$systemConfig['website_url'] . '/email/img/'.$this->email_id .'/'. $this->task_id . '/read?u=' .$this->task->to;
            $url2 = self::$systemConfig['website_url'] . '/email/img/'.$this->email_id .'/'. $this->task_id . '/read';
            $this->read_img_url = $this->mode == '1' ? $url1 : $url2;
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
        
        $data = array('title'=> $this->title,'content'=>$this->content,'read_img_url'=>$this->read_img_url);
        if(!empty($this->template)){           
           //1为使用系统统一空白模板，0为空白模板
           if("0" == $this->template){//Markdown
                $this->view("emails.blankMail")->with($data);
           }
           if("1" == $this->template){
                $this->view("emails.freeMail")->with($data);
           }
        }
        else{
            //空白模板作为默认发送           
            $this->view("emails.blankMail")->with($data);          
        }
        
        return $this;
    }    
}
