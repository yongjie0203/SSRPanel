<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 单封邮件发送任务
 * Class EmailTask
 *
 * @package App\Http\Models
 * @mixin \Eloquent
 */
class EmailTask extends Model
{
    protected $table = 'email_task';
    protected $primaryKey = 'id';

}
