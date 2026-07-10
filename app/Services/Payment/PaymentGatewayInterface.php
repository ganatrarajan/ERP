<?php

namespace App\Services\Payment;

use App\Models\PaymentGatewayConfig;
use App\Models\OnlinePaymentTransaction;
use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    /**
     * Initialize driver with configuration parameters.
     */
    public function initialize(PaymentGatewayConfig $config): self;

    /**
     * Get the loaded configuration model.
     */
    public function getConfig(): PaymentGatewayConfig;

    /**
     * Call gateway API to create an order.
     */
    public function createOrder(OnlinePaymentTransaction $transaction): array;

    /**
     * Verify checkout payment signature returned by the client.
     */
    public function verifySignature(array $payload): bool;

    /**
     * Handle incoming gateway webhook callback payload and verify signature.
     */
    public function handleWebhook(Request $request): array;
}
