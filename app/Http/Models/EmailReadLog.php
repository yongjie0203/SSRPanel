<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 用户邮件阅读记录
 * Class EmailReadLog
 *
 * @package App\Http\Models
 * @mixin \Eloquent
 */
class EmailReadLog extends Model
{
    protected $table = 'email_read_log';
    protected $primaryKey = 'id';

}
