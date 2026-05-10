<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
    'sender_id', 
    'message', 
    'sentiment', 
    'intent', 
    'confidence'
];

public function resolver()
{
    return $this->belongsTo(User::class, 'resolved_by');
}
}
