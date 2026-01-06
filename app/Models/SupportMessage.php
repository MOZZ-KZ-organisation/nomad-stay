<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportMessage extends Model
{
    protected $fillable = [
        'support_chat_id',
        'sender_id',
        'body',
        'read',
    ];

    protected $casts = [
        'read' => 'bool',
    ];

    public function chat()
    {
        return $this->belongsTo(SupportChat::class, 'support_chat_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}