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
use ZipArchive;

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
        $link = self::$systemConfig['subscribe_domain'] ? self::$systemConfig['subscribe_domain'] . '/s/' . $code : self::$systemConfig['website_url'] . '/s/' . $code;

        // 节点列表
        $userLabelIds = UserLabel::query()->where('user_id', Auth::user()->id)->pluck('label_id');
        if (empty($userLabelIds)) {
            $nodeList = [];
            $index = 0;
        }else{
            $nodeList = DB::table('ss_node')
                ->selectRaw('ss_node.*')
	    	->leftjoin("ss_node_label", "ss_node.id", "=", "ss_node_label.node_id")
	    	->where('ss_node.type', 1)
	    	->where('ss_node.status', 1)
	    	->where('ss_node.is_subscribe', 1)
	    	->whereIn('ss_node_label.label_id', $userLabelIds)
	    	->groupBy('ss_node.id')
	    	->orderBy('ss_node.sort', 'desc')
	    	->orderBy('ss_node.id', 'asc')
	    	->get()
            $index = rand(0, sizeof($nodeList));
        }
        
       $configs = "[";
               
        $groupName = "";
	$i = 0;
        foreach ($nodeList as &$node) {
	    $i++;
            // 获取分组名称
            $group = SsGroup::query()->where('id', $node->group_id)->first();

            if ($node->type == 1) {
                $singleProtocolParam  = $user->port.':'.$user->passwd;              
              	$obfs_param = $user->obfs_param ? $user->obfs_param : $node->obfs_param;
                $protocol_param = $node->single ? $user->port . ':' . $user->passwd : $user->protocol_param;
		$configs .= '{';
                $configs .= '"remarks" : "' . $node->name .'",';
                //$configs .= '"id" : "E6B6B8932A9908852F5EC5D90B4155E4",';
                $configs .= '"server" : "' . ($node->server ? $node->server : $node->ip) . '",';
                $configs .= '"server_port" : '. ($node->single ? $node->single_port : $user->port) .',';
                $configs .= '"server_udp_port" : 0,';
                $configs .= '"password" : "' . $user->passwd . '",';
                $configs .= '"method" : "' . ($node->single ? $node->single_method : $user->method) . '",';
                $configs .= '"protocol" : "'.($node->single ? $node->single_protocol : $user->protocol).'",';
                $configs .= '"protocolparam" : "'.($node->single ? $singleProtocolParam : $protocol_param).'",';
                $configs .= '"obfs" : "'.($node->single ? $node->single_obfs : $user->obfs) .'",';
                $configs .= '"obfsparam" : "'. $obfs_param .'",';
                $configs .= '"remarks_base64" : "'. base64url_encode($node->name) .'",';
                $configs .= '"group" : "'. (empty($group) ? '' : $group->name) .'",';
                $configs .= '"enable" : true,';
                $configs .= '"udp_over_tcp" : false';
                $configs .= '}';
              
                $groupName = $group->name;
            } 
	    if($i != sizeof($nodeList) ){
		$configs .= ',';
	    }
        }

	$configs .= ']';

        
        list($msec, $sec) = explode(' ', microtime());
        $serverSubscribes ="";
        $serverSubscribes .= '[';
        $serverSubscribes .= '{';
        $serverSubscribes .= '"URL" : "'. $link .'",';
        $serverSubscribes .= '"Group" : "'. $groupName .'",';
        $serverSubscribes .= '"LastUpdateTime" : '.$sec;
        $serverSubscribes .= '}';
        $serverSubscribes .= ']';
  
        $configJsonString = $this-> getTemplate();
        $configJsonString = str_replace('$configs',$configs,$configJsonString); 
        $configJsonString = str_replace('$index',$index,$configJsonString); 
        $configJsonString = str_replace('$serverSubscribes',$serverSubscribes,$configJsonString);
	
	$tempdir =  $_SERVER['DOCUMENT_ROOT']."/clients/temp/";
	$usertempdir = $tempdir.$user->id;
	if(!is_dir($tempdir)){
		mkdir($tempdir);
	}
	if(!is_dir($usertempdir)){
		mkdir($usertempdir);
	}
	$sourceFile = $_SERVER['DOCUMENT_ROOT']."/clients/ShadowsocksR-win-4.9.0.zip";
	$targetFile = $tempdir .$user->id.  "/ShadowsocksR-win-4.9.0.zip";
	copy($sourceFile,$targetFile);
	$zip=new ZipArchive(); 
	if($zip->open($targetFile) ===  TRUE){
		$zip->addFromString("ShadowsocksR-win-4.9.0/gui-config.json",$configJsonString);
	}
	$zip->close();
	$file_size=filesize($targetFile);
	header("Content-type: application/octet-stream");
	header("Accept-Length:".$file_size);
	header('Content-disposition:attachment;filename=ShadowsocksR-win-4.9.0.zip');
	readfile($targetFile);
	unlink($targetFile);
    }



    private function getTemplate(){
        $templateFilePath = $_SERVER["DOCUMENT_ROOT"]. "/clients/gui-config.template.json";
	return file_get_contents($templateFilePath);
    }
}
