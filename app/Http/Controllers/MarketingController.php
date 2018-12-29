<?php
namespace App\Http\Controllers;

use App\Components\Helpers;
use App\Http\Models\Marketing;
use App\Http\Models\Email;
use App\Http\Models\EmailGroup;
use App\Http\Models\EmailRangeGroup;
use App\Http\Models\Label;
use App\Http\Models\Level;
use App\Http\Models\User;
use App\Mail\freeMail;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Mail\Markdown;
use Response;
use Log;
use DB;
use Auth;
use Mail;

/**
 * 促销控制器
 *
 * Class MarketingController
 *
 * @package App\Http\Controllers
 */
class MarketingController extends Controller
{
    protected static $systemConfig;

    function __construct()
    {
        self::$systemConfig = Helpers::systemConfig();
    }

    // 邮件群发消息列表
    public function emailList(Request $request)
    {
        $status = $request->get('status');
        //$view['list'] = Marketing::query()->where('type', 1);
        $query = DB::table('email')->selectRaw("email.id,email.subject,email.status,(case when  email.status = 0 then '未发送' when  email.status =  1 then '已发送' when  email.status =  2 then '发送中' when  email.status =  3 then '发送中' when  email.status =  4 then '暂停' when  email.status =  -1 then '删除' else email.status end) statusLabel ,email.read,email.send,email.total,email.created_at,group_concat(email_range_group.name) groups ") 
                                          ->leftJoin('email_group','email_group.email_id','=','email.id')
                                          ->leftJoin('email_range_group','email_range_group.id','=','email_group.group_id')
                                          ->where('email.status','!=','-1')
                                          ->groupBy('email.id')
                                          ->groupBy('email.subject')
                                          ->groupBy('email.status')                                          
                                          ->groupBy('email.read')
                                          ->groupBy('email.send')
                                          ->groupBy('email.total')
                                          ->groupBy('email.updated_at')
                                          ->orderBy('email.id','desc');
                                       
        if ($status != '') {
            $query->where('email.status', $status);
        }

        $view['list'] = $query->paginate(15);
        return Response::view('marketing.emailList', $view);
    }

    // 消息通道群发列表
    public function pushList(Request $request)
    {
        $status = $request->get('status');

        $query = Marketing::query()->where('type', 2);

        if ($status != '') {
            $query->where('status', $status);
        }

        $view['list'] = $query->paginate(15);

        return Response::view('marketing.pushList', $view);
    }

    // 添加推送消息
    public function addPushMarketing(Request $request)
    {
        $title = trim($request->get('title'));
        $content = $request->get('content');

        if (!self::$systemConfig['is_push_bear']) {
            return Response::json(['status' => 'fail', 'data' => '', 'message' => '推送失败：请先启用并配置PushBear']);
        }

        DB::beginTransaction();
        try {
            $client = new Client();
            $response = $client->request('GET', 'https://pushbear.ftqq.com/sub', [
                'query' => [
                    'sendkey' => self::$systemConfig['push_bear_send_key'],
                    'text'    => $title,
                    'desp'    => $content
                ]
            ]);

            $result = json_decode($response->getBody());
            if ($result->code) { // 失败
                $this->addMarketing(2, $title, $content, -1, $result->message);

                throw new \Exception($result->message);
            }

            $this->addMarketing(2, $title, $content, 1);

            DB::commit();

            return Response::json(['status' => 'success', 'data' => '', 'message' => '推送成功']);
        } catch (\Exception $e) {
            Log::info('PushBear消息推送失败：' . $e->getMessage());

            DB::rollBack();

            return Response::json(['status' => 'fail', 'data' => '', 'message' => '推送失败：' . $e->getMessage()]);
        }
    }

    private function addMarketing($type = 1, $title = '', $content = '', $status = 1, $error = '', $receiver = '')
    {
        $marketing = new Marketing();
        $marketing->type = $type;
        $marketing->receiver = $receiver;
        $marketing->title = $title;
        $marketing->content = $content;
        $marketing->error = $error;
        $marketing->status = $status;

        return $marketing->save();
    }
    
    public function read($email_id,$task_id,Request $request){
        $to = $request->get('to');
        if(!empty($to)){
            $tos = explode(";",$to);
            /* $unionQuery = DB::table('email_blacklist')
                            ->selectRaw('DISTINCT null username,  email_blacklist.email blacked, email_blacklist.forward forward')
                            ->whereNull('email_blacklist.email')
                            ->where('email_blacklist.status', '=', 1);*/
            
            $unionQuery = DB::table('')->selectRaw('null username,null,null');
             foreach($tos as $key => $a){
              /*  $unionQuery->union(DB::table('email_blacklist')
                            ->selectRaw("DISTINCT '".$a."' username,  email_blacklist.email blacked, email_blacklist.forward forward")
                            ->where('email_blacklist.email', '=',$a)
                            ->where('email_blacklist.status', '=', 1)
                           );*/
                 $unionQuery->union(DB::table('')->selectRaw(''.$a.' username,null,null'));
             }
             return $unionQuery->toSql();
           
         }
        return "to is empty";
    }
 

    // 添加邮件
    public function addEmail(Request $request)
    {
        if ($request->method() == 'POST') {
            $message = '保存成功';
            $status = 'success';
            $email = new Email();            
            $email->to = $request->get('to');
            $email->template = $request->get('template');
            $email->mode = $request->get('mode');
            $email->format = $request->get('format');
            $email->content = $request->get('content');
            $email->subject = $request->get('subject');
            $email->title = $request->get('title');
            $email->expression = $this->buildGroupsConditionsXml($request->get('groups'));
            $action = $request->get('action');
            $email->status = 0;//未发送
            if('start' == $action){
                $email->status = 2;//启动发送
                $email->send = 0;//启动发送发送次数重置
                $email->read = 0;//启动发送阅读次数重置
                $message = '启动成功';
            }                        
            $email->user_id = Auth::user()->id;
            $email->created_at = date('Y-m-d H:i:s');
            $email->save();
            foreach(explode(",",$request->get('groups')) as $key => $group_id ){
                $emailGroup = new EmailGroup();
                $emailGroup->email_id = $email->id;
                $emailGroup->group_id = $group_id;
                $emailGroup->save();
            }
            if('test' == $action){
                //发送测试邮件
                return $this->preview($request);
            }
             
            return Response::json(['status' => $status, 'data' => '', 'message' => $message]);
        } else {
          
            $view['groupList'] = DB::table('email_range_group')
            ->selectRaw('email_range_group.id,email_range_group.name')
            ->where('email_range_group.status','=',1)
            ->get();
            
            return Response::view('marketing.addEmail',$view);
        }
    }
    
    //邮件查看
    public function email(Request $request){
        $id = $request->get('id');
        $email = Email::query()->where('id', $id)->first();
        if('2'==$email->format){
           // $email->content =  Markdown::parse($email->content);
            $mailable = new freeMail($id);
            $mailable->email_id = $id;
            $email->content = $mailable->render();
        }
        
        $view['email'] = $email;
        //return var_dump($email);
        return Response::view('marketing.email', $view);
    }
    
    //在邮箱中预览邮件
    public function preview(Request $request){
        $id = $request->get('id');
        $preto = $request->get('preto');
        $email = Email::query()->where('id', $id)->first();
        $mailable = new freeMail($id);
        Mail::bcc(explode(";",$preto)) -> queue($mailable);
        return Response::json(['status' => 'success', 'data' => '', 'message' => '发送成功']);
    }

    // 编辑邮件
    public function editEmail(Request $request)
    {
        $id = $request->get('id');
        $message = '保存成功';
        $status = 'success';
        if ($request->method() == 'POST') {            
            $to = $request->get('to');
            $template = $request->get('template');
            $mode = $request->get('mode');
            $format = $request->get('format');
            $content = $request->get('content');
            $subject = $request->get('subject');
            $title = $request->get('title');
            $expression = $this->buildGroupsConditionsXml($request->get('groups'));
            $action = $request->get('action');
            $status = 0;//未发送
            if('start' == $action){
                $status = 2;//启动发送
                $email->send = 0;//启动发送发送次数重置
                $email->read = 0;//启动发送阅读次数重置
                $message = '启动成功';
            }                        
            
            $updated_at = date('Y-m-d H:i:s');
            EmailGroup::query()->where('email_id', $id)->delete();
            foreach(explode(",",$request->get('groups')) as $key => $group_id ){
                $emailGroup = new EmailGroup();
                $emailGroup->email_id = $id;
                $emailGroup->group_id = $group_id;
                $emailGroup->save();
            }
            
            $data = [
                'to'   => $to,
                'template' =>$template,
                'mode' =>$mode,
                'format' => $format,
                'content' => $content,
                'subject'    => $subject,
                'title' => $title,
                'expression' => $expression,
                'status' => $status,
                'updated_at' => $updated_at
            ];

            $ret = Email::query()->where('id', $id)->update($data);
            if ($ret) {
                if('test' == $action){
                    //发送测试邮件
                    return $this->preview($request);
                }
                return Response::json(['status' => 'success', 'data' => '', 'message' => '操作成功']);
            } else {
                return Response::json(['status' => 'fail', 'data' => '', 'message' => '操作失败']);
            }
        } else {
            //Email内容
            $view['email'] = Email::query()->where('id', $id)->first();
            //可选分组
            $view['groupList'] = DB::table('email_range_group')
            ->selectRaw("email_range_group.id,email_range_group.name,if(email_group.email_id is null,'','checked') checked")            
            ->leftJoin('email_group',function($join) use ($id) {
                  $join->on('email_group.group_id', '=', 'email_range_group.id')
                       ->where('email_group.email_id', '=', $id);
             })
            ->where('email_range_group.status','=',1)
            ->get();
            $view['selectedGroups'] = EmailGroup::query()->where('email_id', $id)->get();
            return Response::view('marketing.editEmail', $view);
        }
    }
    
    //根据过滤条件构建query
    private function getCountQuery($u,$t,$l){
              
         $blackQuery = DB::table('user')->selectRaw('count(DISTINCT user.username) selected, count(DISTINCT email_blacklist.email) blacked,count(DISTINCT email_blacklist.forward) forward'); 
         $blackQuery ->leftJoin('email_blacklist',function($join){
              $join->on('email_blacklist.email', '=', 'user.username')
                   ->where('email_blacklist.status', '=', 1);
         });
         if (!empty($t)) {
             $blackQuery ->leftJoin('user_label', 'user.id', '=', 'user_label.user_id');
             $blackQuery ->whereIn('user_label.label_id', explode(",",$t));
         }
         if(!empty($l)){        
             $blackQuery->whereIn('user.level', explode(",",$l));
         }
         if($u!=""){        
             $blackQuery->whereIn('user.status', explode(",",$u));
         }
         return  $blackQuery;        
    }
    
    //统计选择的用户信息
    public function getCount(Request $request)
    {
         $u = trim($request->get('u'));
         $t = trim($request->get('t'));
         //tr 多个之间的关系有可能为 or 、 and，如果不传默认为or
         $tr = trim($request->get('tr'));
         $l = trim($request->get('l'));
         $blackQuery = $this->getCountQuery($u,$t,$l);
         $black = $blackQuery->get();
         $total = User::query()->count();    
         return Response::json(['status' => 'success', 'data' => ['total'=>$total,'selected'=>$black], 'message' => '成功']);
    }
    
    //统计选择分组的用户信息
    public function getGroupCount(Request $request){
         $groups = trim($request->get('groups'));
         $expressions = DB::table('email_range_group')->selectRaw('email_range_group.expression')
                                       ->whereIn('email_range_group.id', explode(",",$groups))
                                       ->get()
                                       ->toArray();
         $group = new EmailRangeGroup();
         $group->userStatus = array();
         $group->userLevel = array();
         $group->userLabel = array();
         foreach ($expressions as $key => $expression) {
            $item = $this->xmlToArray($expression->expression);
            $group->userStatus = array_merge($group->userStatus, $item->userStatus);
            $group->userLevel = array_merge($group->userLevel , $item->userLevel);
            $group->userLabel = array_merge($group->userLabel , $item->userLabel);
         }
         $u = join(',',$group->userStatus);
         $t = join(',',$group->userLabel);
         $l = join(',',$group->userLevel);
         $blackQuery = $this->getCountQuery($u,$t,$l);
         $black = $blackQuery->get();
         $total = User::query()->count();    
         return Response::json(['status' => 'success', 'data' => ['total'=>$total,'selected'=>$black,'u'=>$u,'t'=>$t,'l'=>$l], 'message' => '成功']);
    }
    
    //测试邮件发送
    public function testEmail(Request $request)
    {
        $bcc = ["360582818@qq.com","yongjie0203@126.com","admin@syyai.com"];
        try {
            $mail = new freeMail();
            $mail -> subject = "TEST";
           
            Mail::bcc($bcc) -> send($mail);
        } catch (\Exception $e) {
           return $e->getMessage();
        }
        return "ok";
        
    }
  
    //群发分组列表
    public function groupList(Request $request){      
        $view['groupList'] = DB::table('email_range_group')
            ->selectRaw('email_range_group.id,email_range_group.name,email_range_group.created_at,  count(DISTINCT email_group.email_id) count ')
            ->leftJoin('email_group','email_group.group_id','=','email_range_group.id')
            ->where('email_range_group.status','=',1)
            ->groupBy('email_range_group.id')
            ->groupBy('email_range_group.name')
            ->groupBy('email_range_group.status')
            ->groupBy('email_range_group.created_at')
            ->paginate(15)->appends($request->except('page'));
        return Response::view('marketing.groupList', $view);
    }
    
    //添加群发分组
    public function addGroup(Request $request){
        if ($request->method() == 'POST') {
            $u = trim($request->get('u'));
            $t = trim($request->get('t'));
            //tr 多个之间的关系有可能为 or 、 and，如果不传默认为or
            $tr = trim($request->get('tr'));
            $l = trim($request->get('l'));
            
            $conditionsxml = $this->buildConditionXml($u,$t,$l);
            $group = new EmailRangeGroup();
            $group->name = $request->get('name');
            $group->expression = $conditionsxml;
            $group->status = 1;
            $group->user_id = Auth::user()->id;
            $group->created_at = date('Y-m-d H:i:s');
            $group->save();
            return Response::json(['status' => 'success', 'data' => '', 'message' => '添加成功']);
        } else {
            $view['labelList'] = Label::query()->orderBy('sort', 'desc')->orderBy('id', 'asc')->get();
            $view['levelList'] = Helpers::levelList();
            return Response::view('marketing.addGroup', $view);
        }
    }
    
    //构建单个群发分组Conditions xml
    private function buildConditionXml($u,$t,$l){
            $conditionsxml = "<conditions>";
            if (!empty($t)) {
                $conditionsxml .= $this-> getConditionItemXml('user_label','label_id','or',$t);
            }
            if(!empty($l)){        
                $conditionsxml .= $this-> getConditionItemXml('user','level','or',$l);
            }
            if($u!=""){        
                $conditionsxml .= $this-> getConditionItemXml('user','status','or',$u);
            }
            $conditionsxml .= "</conditions>";
            return $conditionsxml;
    }
    
    //构建多个群发分组Conditions xml
    private function buildGroupsConditionsXml($groups){
        $expressions = DB::table('email_range_group')->selectRaw('email_range_group.expression')
                                       ->whereIn('email_range_group.id', explode(",",$groups))
                                       ->get()
                                       ->toArray();
         $group = new EmailRangeGroup();
         $group->userStatus = array();
         $group->userLevel = array();
         $group->userLabel = array();
         foreach ($expressions as $key => $expression) {
            $item = $this->xmlToArray($expression->expression);
            $group->userStatus = array_unique(array_merge($group->userStatus, $item->userStatus));
            $group->userLevel = array_unique(array_merge($group->userLevel , $item->userLevel));
            $group->userLabel = array_unique(array_merge($group->userLabel , $item->userLabel));
         }
         $u = join(',',$group->userStatus);
         $t = join(',',$group->userLabel);
         $l = join(',',$group->userLevel);
         return $this->buildConditionXml($u,$t,$l);
    }
    
    //构建单个condition xml
    private function getConditionItemXml($tableName,$column,$relation,$values){
        $item = '<condition table="'.$tableName.'" column="'.$column .'" relation="'.$relation.'">'.$values.'</condition>';
        return $item;
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
