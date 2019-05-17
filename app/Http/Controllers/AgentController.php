<?php

namespace App\Http\Controllers;

use App\Components\Helpers;
use App\Components\ServerChan;
use App\Http\Models\Agent;
use App\Http\Models\Article;
use App\Http\Models\Coupon;
use App\Http\Models\CouponAgent;
use App\Http\Models\CouponRefund;
use App\Http\Models\CouponWillUse;
use App\Http\Models\Goods;
use App\Http\Models\GoodsLabel;
use App\Http\Models\Invite;
use App\Http\Models\Level;
use App\Http\Models\Order;
use App\Http\Models\ReferralApply;
use App\Http\Models\ReferralLog;
use App\Http\Models\SsGroup;
use App\Http\Models\SsNodeInfo;
use App\Http\Models\SsNodeLabel;
use App\Http\Models\Ticket;
use App\Http\Models\TicketReply;
use App\Http\Models\User;
use App\Http\Models\UserLabel;
use App\Http\Models\UserLoginLog;
use App\Http\Models\UserSubscribe;
use App\Http\Models\UserTrafficDaily;
use App\Http\Models\UserTrafficHourly;
use App\Mail\newTicket;
use App\Mail\replyTicket;
use Illuminate\Http\Request;
use Redirect;
use Response;
use Session;
use Mail;
use Log;
use DB;
use Auth;
use Hash;

/**
 * 经销商代理商控制器
 *
 * Class AgentController
 *
 * @package App\Http\Controllers
 */
class AgentController extends Controller
{
    protected static $systemConfig;

    function __construct()
    {
        self::$systemConfig = Helpers::systemConfig();
    }
    
       
    // 用户列表
    public function userList(Request $request)
    {
        $agent = Agent::query()->where("user_id",Auth::user()->id)->where("status",1)->first();
        
        if(!$agent){
           return Redirect::to('/');
        }        
        $username = $request->get('username');
        $wechat = $request->get('wechat');
        $qq = $request->get('qq');
        $port = $request->get('port');
        $pay_way = $request->get('pay_way');
        $status = $request->get('status');
        $enable = $request->get('enable');
        $online = $request->get('online');
        $unActive = $request->get('unActive');
        $flowAbnormal = $request->get('flowAbnormal');
        $expireWarning = $request->get('expireWarning');
        $largeTraffic = $request->get('largeTraffic');
        $query = User::query();
        if (empty($username) && empty($port)) {
            $uids = CouponAgent::query()->where('user_id', Auth::user()->id)->orderBy('order_user_id','desc')->pluck('order_user_id')->toArray();
            $query->whereIn('id',$uids);     
        }
        if (!empty($username)) {
            $query->where('username', 'like', '%' . $username . '%');
        }
        if (!empty($wechat)) {
            $query->where('wechat', 'like', '%' . $wechat . '%');
        }
        if (!empty($qq)) {
            $query->where('qq', 'like', '%' . $qq . '%');
        }
        if (!empty($port)) {
            $query->where('port', intval($port));
        }
        if ($pay_way != '') {
            $query->where('pay_way', intval($pay_way));
        }
        if ($status != '') {
            $query->where('status', intval($status));
        }
        if ($enable != '') {
            $query->where('enable', intval($enable));
        }
        // 流量超过100G的
        if ($largeTraffic) {
            $query->whereIn('status', [0, 1])->whereRaw('(u + d) >= 107374182400');
        }
        // 临近过期提醒
        if ($expireWarning) {
            $query->where('expire_time', '>=', date('Y-m-d', strtotime("now")))->where('expire_time', '<=', date('Y-m-d', strtotime("+" . self::$systemConfig['expire_days'] . " days")));
        }
        // 当前在线
        if ($online) {
            $query->where('t', '>=', time() - 600);
        }
        // 不活跃用户
        if ($unActive) {
            $query->where('t', '>', 0)->where('t', '<=', strtotime(date('Y-m-d', strtotime("-" . self::$systemConfig['expire_days'] . " days"))))->where('enable', 1);
        }
        // 1小时内流量异常用户
        if ($flowAbnormal) {
            $tempUsers = [];
            $userTotalTrafficList = UserTrafficHourly::query()->where('node_id', 0)->where('total', '>', 104857600)->where('created_at', '>=', date('Y-m-d H:i:s', time() - 3900))->groupBy('user_id')->selectRaw("user_id, sum(total) as totalTraffic")->get(); // 只统计100M以上的记录，加快速度
            if (!$userTotalTrafficList->isEmpty()) {
                foreach ($userTotalTrafficList as $vo) {
                    if ($vo->totalTraffic > (self::$systemConfig['traffic_ban_value'] * 1024 * 1024 * 1024)) {
                        $tempUsers[] = $vo->user_id;
                    }
                }
            }
            $query->whereIn('id', $tempUsers);
        }
        $userList = $query->orderBy('user.id', 'desc')->paginate(15)->appends($request->except('page'));
        foreach ($userList as &$user) {
            $user->transfer_enable = flowAutoShow($user->transfer_enable);
            $user->used_flow = flowAutoShow($user->u + $user->d);
            if ($user->expire_time < date('Y-m-d', strtotime("now"))) {
                $user->expireWarning = -1; // 已过期
            } elseif ($user->expire_time == date('Y-m-d', strtotime("now"))) {
                $user->expireWarning = 0; // 今天过期
            } elseif ($user->expire_time > date('Y-m-d', strtotime("now")) && $user->expire_time <= date('Y-m-d', strtotime("+30 days"))) {
                $user->expireWarning = 1; // 最近一个月过期
            } else {
                $user->expireWarning = 2; // 大于一个月过期
            }
            // 流量异常警告
            $time = date('Y-m-d H:i:s', time() - 3900);
            $totalTraffic = UserTrafficHourly::query()->where('user_id', $user->id)->where('node_id', 0)->where('created_at', '>=', $time)->sum('total');
            $user->trafficWarning = $totalTraffic > (self::$systemConfig['traffic_ban_value'] * 1024 * 1024 * 1024) ? 1 : 0;
        }
        $view['userList'] = $userList;
        return Response::view('user.agent', $view);
    }

   public function coupons(Request $request){
       $status = $request->get('status',0);
       $limit = $request->get('limit',5);
       $amount = $request->get('amount');
       $order_by = $status == 0 ? 'available_end' : 'updated_at';
       $soft = $status == 0 ? 'asc' : 'desc';
       if($status == 0){//正常
            //$willUses = CouponWillUse::query()->where('user_id', Auth::user()->id)->pluck('sn')->toArray();
            $couponList = Coupon::query()->where('holder',Auth::user()->id)->whereNotIn('sn', function ($query) {
            $query->select('sn')->from('coupon_will_use')->where('coupon_will_use.user_id', Auth::user()->id);
            })->where('status',$status)->where('amount',$amount)->orderBy($order_by,$soft)->limit($limit)->get()->toArray();
            return Response::json(['status' => 'success', 'data' => $couponList , 'message' => '']);
       }else if($status == -1){
            //$willUses = CouponWillUse::query()->where('user_id', Auth::user()->id)->pluck('sn')->toArray();            
            $couponList = Coupon::query()->where('holder',Auth::user()->id)->whereIn('sn', function ($query) {
            $query->select('sn')->from('coupon_will_use')->where('coupon_will_use.user_id', Auth::user()->id);
            })->where('status',0)->orderBy($order_by,$soft)->limit($limit)->get()->toArray();
            return Response::json(['status' => 'success', 'data' => $couponList , 'message' => '']);
       }else{//失效
            $couponList = Coupon::query()->where('holder',Auth::user()->id)->where('status',$status)->orderBy($order_by,$soft)->limit($limit)->get()->toArray();
            return Response::json(['status' => 'success', 'data' => $couponList , 'message' => '']);
       }
       
   }

    public function refund(Request $request){
        $coupon_sn = $request->get('coupon_sn');
        if(!empty($coupon_sn) && strlen($coupon_sn) > 7){
            $head = substr($coupon_sn,0,1);
            $coupon_sn = substr($coupon_sn,1,strlen($coupon_sn)-1);           
        }
        if (!empty($coupon_sn)) {                               
            $coupon = Coupon::query()->where('sn', $coupon_sn)->first();
            if(!$coupon){
                return Response::json(['status' => 'fail', 'data' => '' , 'message' => '该码无效']);
            }
            $coupon_refund = new CouponRefund();
            
            $order = Order::query()->where('coupon_id', $coupon->id)->first();
            if($order){
                   
                $order->status = 3;
            }
            
        }
    }

    // 购买服务
    public function buy(Request $request, $id)
    {        
        $goods_id = intval($id);
        $coupon_sn = $request->get('coupon_sn');
        $uid = $request->get('uid');
        $aid = Auth::user()->id;
        if(!empty($coupon_sn) && strlen($coupon_sn) > 7){
            $head = substr($coupon_sn,0,1);
            $coupon_sn = substr($coupon_sn,1,strlen($coupon_sn)-1);
            if($head == "1"){
                $goods_id = 3;
            }
            if($head == "2"){
                $goods_id = 10;
            }
            if($head == "3"){
                $goods_id = 9;
            }
            if($head == "4"){
                $goods_id = 8;
            }
        }

        if ($request->method() == 'POST') {
            $goods = Goods::query()->with(['label'])->where('is_del', 0)->where('status', 1)->where('id', $goods_id)->first();
            if (!$goods) {
                return Response::json(['status' => 'fail', 'data' => '', 'message' => '支付失败：商品或服务已下架']);
            }

            // 限购控制：all-所有商品限购, free-价格为0的商品限购, none-不限购（默认）
            $strategy = self::$systemConfig['goods_purchase_limit_strategy'];
            if ($strategy == 'all' || ($strategy == 'package' && $goods->type == 2) || ($strategy == 'free' && $goods->price == 0) || ($strategy == 'package&free' && ($goods->type == 2 || $goods->price == 0))) {
                $noneExpireGoodExist = Order::query()->where('status', '>=', 0)->where('is_expire', 0)->where('user_id', $uid)->where('goods_id', $goods_id)->exists();
                if ($noneExpireGoodExist) {
                    return Response::json(['status' => 'fail', 'data' => '', 'message' => '支付失败：商品不可重复购买']);
                }
            }

            // 单个商品限购
            if ($goods->is_limit == 1) {
                $noneExpireOrderExist = Order::query()->where('status', '>=', 0)->where('user_id', $uid)->where('goods_id', $goods_id)->exists();
                if ($noneExpireOrderExist) {
                    return Response::json(['status' => 'fail', 'data' => '', 'message' => '创建支付单失败：此商品每人限购1次']);
                }
            }

            // 使用优惠券
            if (!empty($coupon_sn)) {                               
                $coupon = Coupon::query()->where('status', 0)->where('is_del', 0)->whereIn('type', [1, 2])->where('sn', $coupon_sn)->first();
                if (empty($coupon)) {
                    return Response::json(['status' => 'fail', 'data' => '', 'message' => '支付失败：优惠券不存在']);
                }

                // 计算实际应支付总价
                $amount = $coupon->type == 2 ? $goods->price * $coupon->discount / 10 : $goods->price - $coupon->amount;
                $amount = $amount > 0 ? $amount : 0;
            } else {
                $amount = $goods->price;
            }

            // 价格异常判断
            if ($amount < 0) {
                return Response::json(['status' => 'fail', 'data' => '', 'message' => '支付失败：订单总价异常']);
            }

            // 验证账号余额是否充足
            $user = User::query()->where('id', $uid)->first();
            if ($user->balance < $amount) {
                return Response::json(['status' => 'fail', 'data' => '', 'message' => '支付失败：您的余额不足，请先充值']);
            }

            // 验证账号是否存在有效期更长的套餐
            /* 因套餐可提前续费了，所以取消掉套餐长度验证
            if ($goods->type == 2) {
                $existOrderList = Order::query()
                    ->with(['goods'])
                    ->whereHas('goods', function ($q) {
                        $q->where('type', 2);
                    })
                    ->where('user_id', $uid)
                    ->where('is_expire', 0)
                    ->whereIn('status', [2,-2])
                    ->get();

                foreach ($existOrderList as $vo) {
                    if ($vo->goods->days > $goods->days) {
                        return Response::json(['status' => 'fail', 'data' => '', 'message' => '支付失败：您已存在有效期更长的套餐，只能购买流量包']);
                    }
                }
            }
            */

            DB::beginTransaction();
            try {
                // 生成订单
                $order = new Order();
                $order->order_sn = date('ymdHis') . mt_rand(100000, 999999);
                $order->user_id = $user->id;
                $order->goods_id = $goods_id;
                $order->coupon_id = !empty($coupon) ? $coupon->id : 0;
                $order->origin_amount = $goods->price;
                $order->amount = $amount;
                $order->expire_at = date("Y-m-d H:i:s", strtotime("+" . $goods->days . " days"));
                $order->is_expire = 0;
                $order->pay_way = 1;
                $order->status = 2;
                
                $has_not_expire_order = false;

                // 验证是否存在未过期订单
                if ($goods->type == 2) {
                    $not_expire_order = Order::query()
                        ->with(['goods'])
                        ->whereHas('goods', function ($q) {
                            $q->where('type', 2);
                        })
                        ->where('user_id', $uid)
                        ->where('is_expire', 0)
                        ->whereIn('status', [2,-2])
                        ->orderBy('expire_at', 'desc')
                        ->orderBy('created_at', 'desc')
                        ->first();
                    // 如果存在未过期的套餐订单 
                    if($not_expire_order){
                        // 重新计算到期时间
                        $order->expire_at = date("Y-m-d H:i:s", strtotime("+" . $goods->days . " days", strtotime($not_expire_order->expire_at) ));
                        // 订单生效时间
                        $order->effective_at = date("Y-m-d H:i:s", strtotime($not_expire_order->expire_at));
                        // 订单状态：待生效
                        $order->status = -2;
                        
                        Log::info('未过期订单：' . $not_expire_order->order_sn);

                        $has_not_expire_order = true;
                        
                    }
                }

                $expireTime = $order->expire_at;
                
                $order->save();

                // 扣余额
                User::query()->where('id', $user->id)->decrement('balance', $amount * 100);

                // 记录余额操作日志
                $this->addUserBalanceLog($user->id, $order->oid, $user->balance, $user->balance - $amount, -1 * $amount, '购买服务：' . $goods->name);

                // 优惠券置为已使用
                if (!empty($coupon)) {
                    if ($coupon->usage == 1) {
                        $coupon->status = 1;
                        $coupon->save();
                        
                        if($coupon->holder){
                            $coupon_agent = CouponAgent::query()->where('coupon_id',$coupon->id)->first();
                            $coupon_agent-> status = 1;
                            $coupon_agent-> order_id = $order->oid;
                            $coupon_agent-> order_user_id = $order->user_id;
                            $coupon_agent->save();
                            //从即将使用的状态移除
                            CouponWillUse::where('sn', $coupon_sn)->delete();
                        }
                        
                        
                    }

                    // 写入日志
                    Helpers::addCouponLog($coupon->id, $goods_id, $order->oid, '余额支付订单使用');
                }

                // 如果买的是套餐，则先将之前购买的所有套餐置都无效，并扣掉之前所有套餐的流量，重置用户已用流量为0
                /*
                if ($goods->type == 2) {
                    $existOrderList = Order::query()
                        ->with(['goods'])
                        ->whereHas('goods', function ($q) {
                            $q->where('type', 2);
                        })
                        ->where('user_id', $order->user_id)
                        ->where('oid', '<>', $order->oid)
                        ->where('is_expire', 0)
                        ->where('status', 2)
                        ->get();

                    foreach ($existOrderList as $vo) {
                        Order::query()->where('oid', $vo->oid)->update(['is_expire' => 1]);

                        // 先判断，防止手动扣减过流量的用户流量被扣成负数
                        if ($order->user->transfer_enable - $vo->goods->traffic * 1048576 <= 0) {
                            // 写入用户流量变动记录
                            Helpers::addUserTrafficModifyLog($user->id, $order->oid, 0, 0, '[余额支付]用户购买套餐，先扣减之前套餐的流量(扣完)');

                            User::query()->where('id', $order->user_id)->update(['u' => 0, 'd' => 0, 'transfer_enable' => 0]);
                        } else {
                            // 写入用户流量变动记录
                            $user = User::query()->where('id', $user->id)->first(); // 重新取出user信息
                            Helpers::addUserTrafficModifyLog($user->id, $order->oid, $user->transfer_enable, ($user->transfer_enable - $vo->goods->traffic * 1048576), '[余额支付]用户购买套餐，先扣减之前套餐的流量(未扣完)');

                            User::query()->where('id', $order->user_id)->update(['u' => 0, 'd' => 0]);
                            User::query()->where('id', $order->user_id)->decrement('transfer_enable', $vo->goods->traffic * 1048576);
                        }
                    }
                }
                */

                // 写入用户流量变动记录
                $user = User::query()->where('id', $user->id)->first(); // 重新取出user信息
                Helpers::addUserTrafficModifyLog($user->id, $order->oid, $user->transfer_enable, ($user->transfer_enable + $goods->traffic * 1048576), '[余额支付]用户购买商品，加上流量');

                // 把商品的流量加到账号上
                User::query()->where('id', $user->id)->increment('transfer_enable', $goods->traffic * 1048576);

                // 计算账号过期时间
                if ($user->expire_time < $expireTime ) {
                   // $expireTime = date('Y-m-d', strtotime("+" . $goods->days . " days"));
                } else {
                    $expireTime = $user->expire_time;
                }

                // 套餐就改流量重置日，流量包不改,如果有未到期套餐重置日不修改
                if ($goods->type == 2 && !$has_not_expire_order) {
                    if (date('m') == 2 && date('d') == 29) {
                        $traffic_reset_day = 28;
                    } else {
                        $traffic_reset_day = date('d') == 31 ? 30 : abs(date('d'));
                    }
                    User::query()->where('id', $order->user_id)->update(['traffic_reset_day' => $traffic_reset_day, 'expire_time' => $expireTime, 'enable' => 1]);
                } else {
                    User::query()->where('id', $order->user_id)->update(['expire_time' => $expireTime, 'enable' => 1]);
                }

                // 写入用户标签
                if ($goods->label) {
                    // 用户默认标签
                    $defaultLabels = [];
                    if (self::$systemConfig['initial_labels_for_user']) {
                        $defaultLabels = explode(',', self::$systemConfig['initial_labels_for_user']);
                    }

                    // 取出现有的标签
                    $userLabels = UserLabel::query()->where('user_id', $user->id)->pluck('label_id')->toArray();
                    $goodsLabels = GoodsLabel::query()->where('goods_id', $goods_id)->pluck('label_id')->toArray();

                    // 标签去重
                    $newUserLabels = array_values(array_unique(array_merge($userLabels, $goodsLabels, $defaultLabels)));

                    // 删除用户所有标签
                    UserLabel::query()->where('user_id', $user->id)->delete();

                    // 生成标签
                    foreach ($newUserLabels as $vo) {
                        $obj = new UserLabel();
                        $obj->user_id = $user->id;
                        $obj->label_id = $vo;
                        $obj->save();
                    }
                }

                // 写入返利日志
                if ($user->referral_uid) {
                    $this->addReferralLog($user->id, $user->referral_uid, $order->oid, $amount, $order->origin_amount * self::$systemConfig['referral_percent']);
                }

                // 取消重复返利
                User::query()->where('id', $order->user_id)->update(['referral_uid' => 0]);

                DB::commit();

                return Response::json(['status' => 'success', 'data' => '', 'message' => '支付成功']);
            } catch (\Exception $e) {
                DB::rollBack();

                Log::error('支付订单失败：' . $e->getMessage());
                Log::error('支付订单失败：' . $e->getTraceAsString() );

                return Response::json(['status' => 'fail', 'data' => '', 'message' => '支付失败：' . $e->getMessage()]);
            }
        } else {
            $goods = Goods::query()->where('id', $goods_id)->where('is_del', 0)->where('status', 1)->first();
            if (empty($goods)) {
                return Redirect::to('services');
            }

            $view['goods'] = $goods;
            $view['is_youzan'] = self::$systemConfig['is_youzan'];
            $view['is_alipay'] = self::$systemConfig['is_alipay'];

            return Response::view('user.buy', $view);
        }
    }
    
    
    public function willUse(Request $request){
        $will_use = new CouponWillUse();
        $will_use->sn = $request->get('sn');
        $will_use->user_id = Auth::user()->id;
        $will_use->save();
        return Response::json(['status' => 'success', 'data' => '', 'message' => "操作成功"]);
    }
    
    public function notWillUse(Request $request){
        CouponWillUse::where('sn', $request->get('sn'))->delete();
        return Response::json(['status' => 'success', 'data' => '', 'message' => "操作成功"]);
    }

    

    // 转换成管理员的身份
    public function switchToAdmin(Request $request)
    {
        if (!Session::has('admin')) {
            return Response::json(['status' => 'fail', 'data' => '', 'message' => '非法请求']);
        }

        // 管理员信息重新写入user
        Auth::loginUsingId(Session::get('admin'));
        Session::forget('admin');

        return Response::json(['status' => 'success', 'data' => '', 'message' => "身份切换成功"]);
    }

    
}
