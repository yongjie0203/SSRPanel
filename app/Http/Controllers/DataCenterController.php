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
       $dbdata = DB::table('ss_node sn')
                    ->selectRaw('sn.id,sn.`name`,if(sum(l.u+l.d) is null ,0,sum(l.u+l.d))/(1024*1024*1024) used')
                    ->leftJoin('user_traffic_log l','l.node_id','=','sn.id')
                    ->groupBy('l.node_id')
                    ->groupBy('sn.name')
                    ->orderBy('sum(l.u+l.d)','desc')
                    ->get();
       return Response::json(['status' => 'success', 'data' => $dbdata, 'message' => '成功']);
   }
    
    
}
