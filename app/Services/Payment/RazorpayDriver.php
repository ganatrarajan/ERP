<?php

namespace App\Services\Payment;

use App\Models\PaymentGatewayConfig;
use App\Models\OnlinePaymentTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RazorpayDriver implements PaymentGatewayInterface
{
    protected PaymentGatewayConfig $config;

    /**
     * Initialize driver with configuration parameters.
     */
    public function initialize(PaymentGatewayConfig $config): self
    {
        $this->config = $config;
        return $this;
    }

    /**
     * Get the loaded configuration model.
     */
    public function getConfig(): PaymentGatewayConfig
    {
        return $this->config;
    }

    /**
     * Call gateway API to create an order.
     */
    public function createOrder(OnlinePaymentTransaction $transaction): array
    {
        $keyId = $this->config->key_id;
        $keySecret = $this->config->key_secret;

        if (!$keyId || !$keySecret) {
            throw new \Exception("Razorpay gateway keys are not properly configured.");
        }

        $amountInPaise = (int) round($transaction->amount * 100);

        // Call Razorpay Order Creation API
        $response = Http::withBasicAuth($keyId, $keySecret)
            ->post('https://api.razorpay.com/v1/orders', [
                'amount' => $amountInPaise,
                'currency' => $this->config->currency ?? 'INR',
                'receipt' => 'TXN-' . $transaction->id,
            ]);

        if (!$response->successful()) {
            Log::error("Razorpay API Order Creation Failed", [
                'school_id' => $transaction->school_id,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            $errorDesc = $response->json('error.description') ?? 'Unknown error occurred on Razorpay server.';
            throw new \Exception("Razorpay Error: " . $errorDesc);
        }

        return [
            'order_id' => $response->json('id'),
            'gateway_response' => $response->json(),
        ];
    }

    /**
     * Verify checkout payment signature returned by the client.
     */
    public function verifySignature(array $payload): bool
    {
        $orderId = $payload['razorpay_order_id'] ?? '';
        $paymentId = $payload['razorpay_payment_id'] ?? '';
        $signature = $payload['razorpay_signature'] ?? '';

        if (!$orderId || !$paymentId || !$signature) {
            return false;
        }

        $keySecret = $this->config->key_secret;
        $expectedSignature = hash_hmac('sha256', $orderId . '|' . $paymentId, $keySecret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Handle incoming gateway webhook callback payload and verify signature.
     */
    public function handleWebhook(Request $request): array
    {
        $signature = $request->header('X-Razorpay-Signature');
        $webhookSecret = $this->config->webhook_secret;

        if (!$signature || !$webhookSecret) {
            return [
                'verified' => false,
                'message' => 'Missing signature or webhook secret configuration.'
            ];
        }

        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

        if (!hash_equals($expectedSignature, $signature)) {
            return [
                'verified' => false,
                'message' => 'Signature mismatch.'
            ];
        }

        $data = $request->all();
        $event = $data['event'] ?? '';

        // Supported events
        if (in_array($event, ['order.paid', 'payment.captured'])) {
            $payment = $data['payload']['payment']['entity'] ?? [];
            $orderId = $payment['order_id'] ?? '';
            $paymentId = $payment['id'] ?? '';
            $amount = isset($payment['amount']) ? ($payment['amount'] / 100) : 0;
            $method = $payment['method'] ?? 'Online';

            return [
                'verified' => true,
                'status' => 'successful',
                'order_id' => $orderId,
                'payment_id' => $paymentId,
                'amount' => $amount,
                'payment_method' => 'Online - ' . ucfirst($method),
                'gateway_response' => $data,
            ];
        }

        return [
            'verified' => true,
            'status' => 'ignored',
            'event' => $event,
        ];
    }
}
