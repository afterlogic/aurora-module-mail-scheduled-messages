<?php

namespace Aurora\Modules\MailScheduledMessages\Models;

use Aurora\Modules\Mail\Models\MailAccount;
use Aurora\System\Classes\Model;

class Message extends Model
{
    protected $table = 'mail_scheduled_messages';

    protected $foreignModel = MailAccount::class;
    protected $foreignModelIdColumn = 'account_id'; // Column that refers to an external table

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'account_id',
        'folder_full_name',
        'message_uid',
        'schedule_timestamp'
    ];

    /**
    * The attributes that should be hidden for arrays.
    *
    * @var array
    */
    protected $hidden = [
    ];

    protected $casts = [
    ];

    protected $attributes = [
    ];
}
