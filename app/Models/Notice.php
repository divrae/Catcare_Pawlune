<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notice extends Model
{
    protected $fillable = ['user_id', 'visit_id', 'type', 'title', 'body', 'url', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public static function send(int $userId, string $title, ?string $body = null, string $type = 'info', ?int $visitId = null, ?string $url = null): self
    {
        return static::create([
            'user_id' => $userId, 'visit_id' => $visitId, 'type' => $type,
            'title' => $title, 'body' => $body, 'url' => $url,
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
