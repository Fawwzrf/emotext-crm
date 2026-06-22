<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductFeedback extends Model
{
    use HasFactory;

    protected $table = 'product_feedbacks';

    protected $fillable = [
        'name',
        'email',
        'message',
        'user_id',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
