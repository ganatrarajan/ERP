<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentGatewayConfig extends Model
{
    protected $table = 'payment_gateway_configs';

    protected $fillable = [
        'school_id',
        'gateway_name',
        'key_id',
        'key_secret',
        'webhook_secret',
        'mode',
        'currency',
        'active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'key_secret' => 'encrypted',
        'webhook_secret' => 'encrypted',
        'active' => 'boolean',
    ];

    /**
     * School relationship.
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Creator user relationship.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Updater user relationship.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
