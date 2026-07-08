<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnlinePaymentTransaction extends Model
{
    protected $table = 'online_payment_transactions';

    protected $fillable = [
        'school_id',
        'student_id',
        'installment_id',
        'receipt_no',
        'gateway_name',
        'order_id',
        'payment_id',
        'signature',
        'amount',
        'currency',
        'payment_method',
        'status',
        'transaction_date',
        'gateway_response',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'datetime',
        'gateway_response' => 'array',
    ];

    /**
     * School relationship.
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Student relationship.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Installment relationship.
     */
    public function installment(): BelongsTo
    {
        return $this->belongsTo(FeeInstallment::class, 'installment_id');
    }
}
