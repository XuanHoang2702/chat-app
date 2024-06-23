<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use SoftDeletes;
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'conversation_id',
        'user_id',
        'content',
        'status'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    public function parent()
    {
        return $this->belongsTo(Message::class, 'parent_id');
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
