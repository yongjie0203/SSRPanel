<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 邮件群发分组
 * Class EmailRangeGroup
 *
 * @package App\Http\Models
 * @mixin \Eloquent
 */
class EmailRangeGroup extends Model
{
    protected $table = 'email_range_group';
    protected $primaryKey = 'id';

}
