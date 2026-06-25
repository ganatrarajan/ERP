<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeReceipt extends Model
{
    protected $fillable = [
        'school_id',
        'receipt_number',
        'collection_id',
        'generated_at',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(FeeCollection::class, 'collection_id');
    }
}
