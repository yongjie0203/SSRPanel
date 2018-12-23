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
        //$view['list'] = Marketing::query()->where('type', 1)->paginate(15);
        $view['list'] = DB::table('email')->selectRaw('email.id,email.subject,email.status,email.read,email.send,email.total,email.created_at,group_concat(email_range_group.name) groups ') 
                                          ->leftJoin('email_group','email_group.email_id','=','email.id')
                                          ->leftJoin('email_range_group','email_range_group.id','=','email_group.group_id')
                                          ->groupBy('email.id')
                                          ->groupBy('email.subject')
                                          ->groupBy('email.status')
                                          ->groupBy('email.read')
                                          ->groupBy('email.send')
                                          ->groupBy('email.total')
                                          ->groupBy('email.created_at')
                                          ->orderBy('email.id','desc')
                                          ->get();

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
            $email->expression = $request->get('expression');
            $email->status = 0;
            $email->user_id = Auth::user()->id;
            $email->created_at = date('Y-m-d H:i:s');
            $email->save();
            foreach(explode(",",$request->get('groups')) as $key => $group_id ){
                $emailGroup = new EmailGroup();
                $emailGroup->email_id = $email->id;
                $emailGroup->group_id = $group_id;
                $emailGroup->save();
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

    // 编辑邮件
    public function editEmail(Request $request)
    {
        $id = $request->get('id');

        if ($request->method() == 'POST') {
            $to = $request->get('to');
            $cc = $request->get('cc');
            $bcc = $request->get('bcc');
            $from = $request->get('from');
            $content = $request->get('content');
            $subject = $request->get('subject');
            $expression = $request->get('expression');

            $data = [
                'to'   => $to,
                'cc'    => $cc,
                'bcc'  => $bcc,
                'from' => $from,
                'content' => $content,
                'subject'    => $subject,
                'expression' => $expression
            ];

            $ret = Email::query()->where('id', $id)->update($data);
            if ($ret) {
                return Response::json(['status' => 'success', 'data' => '', 'message' => '保存成功']);
            } else {
                return Response::json(['status' => 'fail', 'data' => '', 'message' => '保存失败']);
            }
        } else {
            $view['email'] = Email::query()->where('id', $id)->first();
            $view['labelList'] = Label::query()->orderBy('sort', 'desc')->orderBy('id', 'asc')->get();
            $view['levelList'] = Helpers::levelList();
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
            $mail = new freeMail("<html><div><h1> this is a test mail </h1> </div></html>");
            $mail -> subject = "TEST";
           
            Mail::bcc($bcc) -> send($mail);
        } catch (\Exception $e) {
           return $e->getMessage();
        }
        return "ok";
        
    }
  
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
    
    public function addGroup(Request $request){
        if ($request->method() == 'POST') {
            $u = trim($request->get('u'));
            $t = trim($request->get('t'));
            //tr 多个之间的关系有可能为 or 、 and，如果不传默认为or
            $tr = trim($request->get('tr'));
            $l = trim($request->get('l'));
            
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
    
    private function getConditionItemXml($tableName,$column,$relation,$values){
        $item = '<condition table="'.$tableName.'" column="'.$column .'" relation="'.$relation.'">'.$values.'</condition>';
        return $item;
    }
    
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
                $group->userStatus = array_merge($group->userStatus,  explode(',',$v));
            }
        }
        if(!empty($s->xpath("/conditions/condition[@table='user' and @column='level' and @relation='or']"))){
            foreach($s->xpath("/conditions/condition[@table='user' and @column='level' and @relation='or']") as $v){
                $group->userLevel = array_merge($group->userLevel,  explode(',',$v));
            }
        }
        if(!empty($s->xpath("/conditions/condition[@table='user_label' and @column='label_id' and @relation='or']"))){
            foreach($s->xpath("/conditions/condition[@table='user_label' and @column='label_id' and @relation='or']") as $v){
                $group->userLabel = array_merge($group->userLabel,  explode(',',$v));
            }
        }
        return $group;
    }

    
    
}
