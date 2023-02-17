<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsStatus extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sms_status';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'from',
        'to',
        'message_uuid',
        'status'
    ];
}
