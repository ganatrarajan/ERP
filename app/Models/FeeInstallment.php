<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeInstallment extends Model
{
    protected $fillable = [
        'school_id',
        'fee_structure_id',
        'installment_name',
        'due_date',
        'amount',
        'sort_order',
        'status',
        'is_delete',
    ];

    protected $casts = [
        'due_date' => 'date',
        'amount' => 'decimal:2',
        'sort_order' => 'integer',
        'is_delete' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function feeStructure(): BelongsTo
    {
        return $this->belongsTo(FeeStructure::class, 'fee_structure_id');
    }
}
