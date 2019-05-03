<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 即将使用的券
 * Class CouponWillUse
 *
 * @package App\Http\Models
 * @mixin \Eloquent
 */
class CouponWillUse extends Model
{
    protected $table = 'coupon_will_use';
    protected $primaryKey = 'sn';

}
