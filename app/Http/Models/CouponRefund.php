<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 退款的抵用券
 * Class CouponRefund
 *
 * @package App\Http\Models
 * @mixin \Eloquent
 */
class CouponRefund extends Model
{
    protected $table = 'coupon_refund';
    protected $primaryKey = 'sn';

}
