<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conversation extends Model
{
    use SoftDeletes;
    use HasFactory;
    protected $fillable = [
        'name',
        'type',
        'owner_id',
        'avatar'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    public function messages()
    {
        return $this->hasMany(Message::class, 'conversation_id');
    }

    public function participants()
    {
        return $this->hasMany(Participant::class, 'conversation_id');
    }
}
