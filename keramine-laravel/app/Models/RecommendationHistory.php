<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecommendationHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_title',
        'nama_toko',
        'pof_name',
        'basket_type',
        'item_level',
        'trigger_items',
        'recommendations_json',
        'source_mode',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'trigger_items' => 'array',
            'recommendations_json' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

