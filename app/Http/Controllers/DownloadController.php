<?php

namespace App\Http\Controllers;

use App\Components\Helpers;
use App\Components\ServerChan;
use App\Http\Models\Article;
use App\Http\Models\Coupon;
use App\Http\Models\Goods;
use App\Http\Models\GoodsLabel;
use App\Http\Models\Invite;
use App\Http\Models\Level;
use App\Http\Models\Order;
use App\Http\Models\ReferralApply;
use App\Http\Models\ReferralLog;
use App\Http\Models\SsConfig;
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
 * 客户端下载控制器
 *
 * Class DownloadController
 *
 * @package App\Http\Controllers
 */
class DownloadController extends Controller
{
    protected static $systemConfig;

    function __construct()
    {
        self::$systemConfig = Helpers::systemConfig();
    }

    public function windowsDownland(Request $request)
    {
        $user = User::query()->where('id', Auth::user()->id)->first();
       
        $subscribe = UserSubscribe::query()->where('user_id', Auth::user()->id)->first();      
        $code = $subscribe->code;     
        $view['link'] = self::$systemConfig['subscribe_domain'] ? self::$systemConfig['subscribe_domain'] . '/s/' . $code : self::$systemConfig['website_url'] . '/s/' . $code;

        // 节点列表
        $userLabelIds = UserLabel::query()->where('user_id', Auth::user()->id)->pluck('label_id');
        if (empty($userLabelIds)) {
            $view['nodeList'] = [];

            return Response::view('user.index', $view);
        }

        $nodeList = DB::table('ss_node')
            ->selectRaw('ss_node.*')
            ->leftJoin('ss_node_label', 'ss_node.id', '=', 'ss_node_label.node_id')
            ->whereIn('ss_node_label.label_id', $userLabelIds)
            ->where('ss_node.status', 1)
            ->groupBy('ss_node.id')
            ->orderBy('ss_node.sort', 'desc')
            ->orderBy('ss_node.id', 'asc')
            ->get();

        foreach ($nodeList as &$node) {
            // 获取分组名称
            $group = SsGroup::query()->where('id', $node->group_id)->first();

            if ($node->type == 1) {
                // 生成ssr scheme
                $obfs_param = $user->obfs_param ? $user->obfs_param : $node->obfs_param;
                $protocol_param = $node->single ? $user->port . ':' . $user->passwd : $user->protocol_param;

                $ssr_str = ($node->server ? $node->server : $node->ip) . ':' . ($node->single ? $node->single_port : $user->port);
                $ssr_str .= ':' . ($node->single ? $node->single_protocol : $user->protocol) . ':' . ($node->single ? $node->single_method : $user->method);
                $ssr_str .= ':' . ($node->single ? $node->single_obfs : $user->obfs) . ':' . ($node->single ? base64url_encode($node->single_passwd) : base64url_encode($user->passwd));
                $ssr_str .= '/?obfsparam=' . base64url_encode($obfs_param);
                $ssr_str .= '&protoparam=' . ($node->single ? base64url_encode($user->port . ':' . $user->passwd) : base64url_encode($protocol_param));
                $ssr_str .= '&remarks=' . base64url_encode($node->name);
                $ssr_str .= '&group=' . base64url_encode(empty($group) ? '' : $group->name);
                $ssr_str .= '&udpport=0';
                $ssr_str .= '&uot=0';
                $ssr_str = base64url_encode($ssr_str);
                $ssr_scheme = 'ssr://' . $ssr_str;

                // 生成ss scheme
                $ss_str = $user->method . ':' . $user->passwd . '@';
                $ss_str .= ($node->server ? $node->server : $node->ip) . ':' . $user->port;
                $ss_str = base64url_encode($ss_str) . '#' . 'VPN';
                $ss_scheme = 'ss://' . $ss_str;

                // 生成文本配置信息
                $txt = "服务器：" . ($node->server ? $node->server : $node->ip) . "\r\n";
                if ($node->ipv6) {
                    $txt .= "IPv6：" . $node->ipv6 . "\r\n";
                }
                $txt .= "远程端口：" . ($node->single ? $node->single_port : $user->port) . "\r\n";
                $txt .= "密码：" . ($node->single ? $node->single_passwd : $user->passwd) . "\r\n";
                $txt .= "加密方法：" . ($node->single ? $node->single_method : $user->method) . "\r\n";
                $txt .= "路由：绕过局域网及中国大陆地址" . "\r\n\r\n";
                $txt .= "协议：" . ($node->single ? $node->single_protocol : $user->protocol) . "\r\n";
                $txt .= "协议参数：" . ($node->single ? $user->port . ':' . $user->passwd : $user->protocol_param) . "\r\n";
                $txt .= "混淆方式：" . ($node->single ? $node->single_obfs : $user->obfs) . "\r\n";
                $txt .= "混淆参数：" . ($user->obfs_param ? $user->obfs_param : $node->obfs_param) . "\r\n";
                $txt .= "本地端口：1080" . "\r\n";

                $node->txt = $txt;
                $node->ssr_scheme = $ssr_scheme;
                $node->ss_scheme = $node->compatible ? $ss_scheme : ''; // 节点兼容原版才显示
            } else {
                // 生成v2ray scheme
                $v2_json = [
                    "v"    => "2",
                    "ps"   => $node->name,
                    "add"  => $node->server ? $node->server : $node->ip,
                    "port" => $node->v2_port,
                    "id"   => $user->vmess_id,
                    "aid"  => $node->v2_alter_id,
                    "net"  => $node->v2_net,
                    "type" => $node->v2_type,
                    "host" => $node->v2_host,
                    "path" => $node->v2_path,
                    "tls"  => $node->v2_tls == 1 ? "tls" : ""
                ];
                $v2_scheme = 'vmess://' . base64url_encode(json_encode($v2_json));

                // 生成文本配置信息
                $txt = "服务器：" . ($node->server ? $node->server : $node->ip) . "\r\n";
                if ($node->ipv6) {
                    $txt .= "IPv6：" . $node->ipv6 . "\r\n";
                }
                $txt .= "端口：" . $node->v2_port . "\r\n";
                $txt .= "用户ID：" . $user->vmess_id . "\r\n";
                $txt .= "额外ID：" . $node->v2_alter_id . "\r\n";
                $txt .= "传输协议：" . $node->v2_net . "\r\n";
                $txt .= "伪装类型：" . $node->v2_type . "\r\n";
                $txt .= $node->v2_host ? "伪装域名：" . $node->v2_host . "\r\n" : "";
                $txt .= $node->v2_path ? "路径：" . $node->v2_path . "\r\n" : "";
                $txt .= $node->v2_tls == 1 ? "TLS：tls\r\n" : "";

                $node->txt = $txt;
                $node->v2_scheme = $v2_scheme;
            }
            
        }

        $view['nodeList'] = $nodeList;

        return Response::view('user.index', $view);
    }



    
}
