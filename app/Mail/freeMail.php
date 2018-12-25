<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Http\Models\Email;
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
   

    public function __construct($email_id)
    {        
        $this->$email_id = $email_id;
    }

    public function build()
    {        
        //有指定email_id主题、标题、格式、及内容的优先使用指定值，否则根据email_id读取
        if(!empty($this->email_id)){
            $email = Email::query()->where('id', $this->email_id)->first();
            $this->format = empty($this->format) ? $email->format : $this->format;
            $this->subject = empty($this->subject) ? $email->subject : $this->subject;
            $this->content = empty($this->content) ? $email->content : $this->content;
            $this->title = empty($this->title) ? $email->title : $this->title;
            $this->template = empty($this->template) ? $email->template : $this->template;
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
        $data = array('title'=> $this->title,'content'=>$this->content);
        if(!empty($this->template)){           
           //1为使用系统统一空白模板，0为不使用模板
           if("1" == $this->template){//Markdown
                $this->view("emails.freeMail")->with($data);
           }else{
                $thie->html($this->content);
           }
        }
        
        return $this;
    }
}
