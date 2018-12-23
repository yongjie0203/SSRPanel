<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 邮件
 * Class Email
 *
 * @package App\Http\Models
 * @mixin \Eloquent
 */
class Email extends Model
{
    protected $table = 'email';
    protected $primaryKey = 'id';

}
