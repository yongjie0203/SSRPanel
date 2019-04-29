<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 抵用券代理商对应信息
 * Class CouponAgent
 *
 * @package App\Http\Models
 * @mixin \Eloquent
 */
class CouponAgent extends Model
{
    protected $table = 'coupon_agent';
    protected $primaryKey = 'id';

}
