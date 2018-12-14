<?php
namespace App\Http\Controllers;

use App\Components\Helpers;
use App\Http\Models\Marketing;
use App\Http\Models\Email;
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
        $view['list'] = Marketing::query()->where('type', 1)->paginate(15);

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
            $email = new Email();
            $email->to = $request->get('to');
            $email->cc = $request->get('cc');
            $email->bcc = $request->get('bcc');
            $email->from = $request->get('from');
            $email->content = $request->get('content');
            $email->subject = $request->get('subject');
            $email->expression = $request->get('expression');
            $email->status = 0;
            $email->user_id = Auth::user()->id;
            $email->save();

            return Response::json(['status' => 'success', 'data' => '', 'message' => '保存成功']);
        } else {
            return Response::view('marketing.addEmail');
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

            return Response::view('marketing.editEmail', $view);
        }
    }
    
    //测试邮件发送
    public function testEmail(Request $request){
        Mail::to("360582818@qq.com") ->send("test content");
        var_dump(Mail);
    }
    
}
