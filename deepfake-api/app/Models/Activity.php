<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'action', 'model_name', 'count'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
