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
        return Response::view('user.datacenter');
   }
    
    
}
