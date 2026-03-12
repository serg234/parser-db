<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    use HasFactory;

    // Разрешаем массово заполнять эти поля
    protected $fillable = [
        'user_id',
        'room_id',
        'message',
    ];

    // Опционально: связь с пользователем
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
