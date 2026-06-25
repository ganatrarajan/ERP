<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FeeCollection extends Model
{
    protected $fillable = [
        'school_id',
        'academic_year_id',
        'student_id',
        'installment_id',
        'amount_due',
        'amount_paid',
        'discount_amount',
        'fine_amount',
        'payment_date',
        'payment_method',
        'transaction_reference',
        'remarks',
        'collected_by',
    ];

    protected $casts = [
        'amount_due' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'fine_amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function installment(): BelongsTo
    {
        return $this->belongsTo(FeeInstallment::class, 'installment_id');
    }

    public function collectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(FeeReceipt::class, 'collection_id');
    }
}
