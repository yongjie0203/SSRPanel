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
                    ->get()
                    ->toArray();
       $x = array_column($dbdata,'name');
       $y = array_column($dbdata,'used');
       return Response::json(['status' => 'success', 'data' => ['x'=>$x,'y'=>$y], 'message' => '成功']);
   }
    
   //统计近30天的用户上网时间分布
   public function userOnlineDataMonthly(){
        $dbdata = DB::table('user_traffic_log')
                    ->selectRaw("FROM_UNIXTIME( user_traffic_log.log_time,'%H') hours, count(distinct user_traffic_log.user_id) users, count(user_traffic_log.id ) time")
                    ->groupBy("hours")
                    ->orderBy("hours","asc")
                    ->get()
                    ->toArray();
       $hours = array_column($dbdata,'hours');
       $users = array_column($dbdata,'users');
       $time = array_column($dbdata,'time');
       return Response::json(['status' => 'success', 'data' => ['hours'=>$hours,'users'=>$users,'time'=>$time], 'message' => '成功']);
   }
   
    //最近30天订单额数据
   public function orderDataMonthly(){
        $sql = "SELECT t.date,if(o.amount is null ,0,o.amount) amount from ( ";
        $sql .= "SELECT DATE_FORMAT(date_add(now(), interval -1*x.d day),'%Y-%m-%d') date ";
        $sql .= " FROM ";
        $sql .= " (SELECT 0 AS d UNION ALL  SELECT 1   UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL ";
        $sql .= " SELECT 8 UNION ALL SELECT 9 UNION ALL SELECT 10 UNION ALL SELECT 11 UNION ALL SELECT 12 UNION ALL SELECT 13 UNION ALL SELECT 14 UNION ALL ";
        $sql .= " SELECT 15 UNION ALL SELECT 16 UNION ALL SELECT 17 UNION ALL SELECT 18 UNION ALL SELECT 19 UNION ALL SELECT 20 UNION ALL SELECT 21 UNION ALL ";
        $sql .= " SELECT 22 UNION ALL SELECT 23 UNION ALL SELECT 24 UNION ALL SELECT 25 UNION ALL SELECT 26 UNION ALL SELECT 27 UNION ALL SELECT 28 UNION ALL ";
        $sql .= " SELECT 29 UNION ALL SELECT 30 ) x) ";
        $sql .= "     t  ";
        $sql .= "     LEFT JOIN ( ";
        $sql .= "     SELECT DATE_FORMAT(`order`.created_at,'%Y-%m-%d') date ,sum(if(amount = 0 ,origin_amount,amount))/100 amount from `order`  ";
        $sql .= "     where `order`.`status` in (1,2) ";
        $sql .= "     GROUP BY DATE_FORMAT(`order`.created_at,'%Y-%m-%d') ";
        $sql .= "     ) o on t.date = o.date ";
        $sql .= "     order by t.date";
        $dbdata = DB::table(DB::raw('('.$sql.') t'))
                    ->selectRaw('date,amount')
                    ->get()
                    ->toArray();
        $date = array_column($dbdata,'date');
        $amount = array_column($dbdata,'amount');
        return Response::json(['status' => 'success', 'data' => ['date'=>$date,'amount'=>$amount], 'message' => '成功']);       
   }
   
    //节点流量重置周期内用量比例
   public function nodeUsedCyclicity(){
        $sql = " select t.id,t.name,t.traffic,t.used , t.used/t.traffic*100 percent from(
                 select ss_node.id,ss_node.name, ss_node.traffic,sum((user_traffic_log.u+user_traffic_log.d)/user_traffic_log.rate)/(1024*1024*1024) used from ss_node
                 left join (
                 select ss_node.id, case when DATEDIFF(concat( date_format( now(),'%Y-%m-' ), ss_node.traffic_reset_date),now()) > 0 then DATE_ADD(str_to_date(concat( date_format( now(),'%Y-%m-' ), ss_node.traffic_reset_date),'%Y-%m-%d'),INTERVAL -1 month)
                 when DATEDIFF(concat( date_format( now(),'%Y-%m-' ), ss_node.traffic_reset_date),now()) = 0 then str_to_date(concat( date_format( now(),'%Y-%m-' ), ss_node.traffic_reset_date),'%Y-%m-%d') 
                 when DATEDIFF(concat( date_format( now(),'%Y-%m-' ), ss_node.traffic_reset_date),now()) < 0 then str_to_date(concat( date_format( now(),'%Y-%m-' ), ss_node.traffic_reset_date),'%Y-%m-%d') 
                 else str_to_date(concat( date_format( now(),'%Y-%m-' ), ss_node.traffic_reset_date),'%Y-%m-%d')  end as startdate
                 from ss_node) startdate
                 on startdate.id = ss_node.id
                 left join user_traffic_log on user_traffic_log.node_id = ss_node.id
                 and FROM_UNIXTIME(user_traffic_log.log_time,'%Y-%m-%d') >= startdate.startdate
                 group by ss_node.id,ss_node.name, ss_node.traffic
                 ) t";
       $dbdata = DB::table(DB::raw('('.$sql.') t'))
                    ->selectRaw('id,t.name,traffic,used ,percent')
                    ->orderBy('percent','desc')
                    ->get()
                    ->toArray();
        $name = array_column($dbdata,'name');
        $percent = array_column($dbdata,'percent');
        return Response::json(['status' => 'success', 'data' => ['name'=>$name,'percent'=>$percent], 'message' => '成功']); 
   }
    
   //最近30天用户在线数量
   public function onlineUsersMonthly(){
        $sql = "SELECT t.date,l.users from (
                SELECT DATE_FORMAT(date_add(now(), interval -1*x.d day),'%Y-%m-%d') date
                FROM
                    (SELECT 0 AS d UNION ALL  SELECT 1   UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL
                    SELECT 8 UNION ALL SELECT 9 UNION ALL SELECT 10 UNION ALL SELECT 11 UNION ALL SELECT 12 UNION ALL SELECT 13 UNION ALL SELECT 14 UNION ALL
                    SELECT 15 UNION ALL SELECT 16 UNION ALL SELECT 17 UNION ALL SELECT 18 UNION ALL SELECT 19 UNION ALL SELECT 20 UNION ALL SELECT 21 UNION ALL
                    SELECT 22 UNION ALL SELECT 23 UNION ALL SELECT 24 UNION ALL SELECT 25 UNION ALL SELECT 26 UNION ALL SELECT 27 UNION ALL SELECT 28 UNION ALL
                    SELECT 29 UNION ALL SELECT 30 ) x)
                t
                LEFT JOIN (
                SELECT FROM_UNIXTIME(user_traffic_log.log_time,'%Y-%m-%d') date ,count(DISTINCT user_traffic_log.user_id) users from user_traffic_log 
                GROUP BY FROM_UNIXTIME(user_traffic_log.log_time,'%Y-%m-%d')
                ) l on t.date = l.date
                order by t.date ";
       $dbdata = DB::table(DB::raw('('.$sql.') t'))
                    ->selectRaw('date,users')
                    ->orderBy('date','asc')
                    ->get()
                    ->toArray();
        $date = array_column($dbdata,'date');
        $users = array_column($dbdata,'users');
        return Response::json(['status' => 'success', 'data' => ['date'=>$date,'users'=>$users], 'message' => '成功']); 
   }
    
}
