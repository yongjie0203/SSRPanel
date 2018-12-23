<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 邮件和群发分组关系
 * Class EmailGroup
 *
 * @package App\Http\Models
 * @mixin \Eloquent
 */
class EmailGroup extends Model
{
    protected $table = 'email_group';
    protected $primaryKey = 'id';

}
