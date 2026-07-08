<?php

namespace App\Services\Payment;

use App\Models\PaymentGatewayConfig;
use Exception;

class PaymentGatewayFactory
{
    /**
     * Get active payment gateway driver for a specific school.
     */
    public static function createForSchool(int $schoolId): PaymentGatewayInterface
    {
        $config = PaymentGatewayConfig::where('school_id', $schoolId)
            ->where('active', true)
            ->first();

        if (!$config) {
            throw new Exception("Online payments are not active or configured for this school.");
        }

        return self::createFromConfig($config);
    }

    /**
     * Resolve dynamic driver mapping.
     */
    public static function createFromConfig(PaymentGatewayConfig $config): PaymentGatewayInterface
    {
        $driverName = strtolower($config->gateway_name);
        
        $driverClass = match ($driverName) {
            'razorpay' => RazorpayDriver::class,
            default => throw new Exception("Unsupported payment gateway driver: {$config->gateway_name}"),
        };

        return app($driverClass)->initialize($config);
    }
}
