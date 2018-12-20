<?php
namespace App\Http\Controllers;

use App\Components\Helpers;
use App\Http\Models\Email;
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
 * 数据中心控制器
 *
 * Class DataCenterController
 *
 * @package App\Http\Controllers
 */
class DataCenterController extends Controller
{
    protected static $systemConfig;

    function __construct()
    {
        self::$systemConfig = Helpers::systemConfig();
    }

   public function index(){
        return Response::view('admin.dataCenter');
   }
    
   public function nodeUsedMonthly(){
       $dbdata = DB::table('ss_node')
                    ->selectRaw('ss_node.id,ss_node.`name`,if(sum(user_traffic_log.u+user_traffic_log.d) is null ,0,sum(user_traffic_log.u+user_traffic_log.d))/(1024*1024*1024) used')
                    ->leftJoin('user_traffic_log','user_traffic_log.node_id','=','ss_node.id')
                    ->groupBy('user_traffic_log.node_id')
                    ->groupBy('ss_node.name')
                    ->orderBy('used','desc')
                    ->get();
       $x = array_column($dbdata,'name');
       $y = array_column($dbdata,'used');
       return Response::json(['status' => 'success', 'data' => ['x'=>$x,'y'=>$y], 'message' => '成功']);
   }
    
    
}
