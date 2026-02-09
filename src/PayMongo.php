<?php

namespace Kirame\PayMongo;

use Illuminate\Support\Facades\Http;
use Kirame\PayMongo\Exceptions\PayMongoException;

class PayMongo
{
    public function __construct(
        protected string $secretKey,
        protected string $baseUrl = 'https://api.paymongo.com/v1',
        protected int $timeout = 15,
        protected int $retries = 2
    ) {
        if (empty($this->secretKey)) {
            throw new PayMongoException('PayMongo secret key is not configured. Set PAYMONGO_SECRET_KEY in your .env file.');
        }
    }

    // ==================
    // Customers
    // ==================

    public function createCustomer(array $attributes): array
    {
        return $this->post('/customers', ['data' => ['attributes' => $attributes]]);
    }

    public function getCustomer(string $id): array
    {
        return $this->get("/customers/{$id}");
    }

    // ==================
    // Payment Intents
    // ==================

    public function createPaymentIntent(array $attributes): array
    {
        return $this->post('/payment_intents', ['data' => ['attributes' => $attributes]]);
    }

    public function retrievePaymentIntent(string $id): array
    {
        return $this->get("/payment_intents/{$id}");
    }

    // ==================
    // Payment Methods
    // ==================

    public function createPaymentMethod(array $attributes): array
    {
        return $this->post('/payment_methods', ['data' => ['attributes' => $attributes]]);
    }

    // ==================
    // Attach Payment Method to Intent
    // ==================

    public function attachPaymentMethod(string $intentId, string $methodId, ?string $clientKey = null): array
    {
        $attributes = ['payment_method' => $methodId];

        if ($clientKey) {
            $attributes['client_key'] = $clientKey;
        }

        return $this->post("/payment_intents/{$intentId}/attach", [
            'data' => ['attributes' => $attributes],
        ]);
    }

    // ==================
    // Checkout Sessions
    // ==================

    public function createCheckoutSession(array $attributes): array
    {
        return $this->post('/checkout_sessions', ['data' => ['attributes' => $attributes]]);
    }

    public function retrieveCheckoutSession(string $id): array
    {
        return $this->get("/checkout_sessions/{$id}");
    }

    // ==================
    // Payments
    // ==================

    public function retrievePayment(string $id): array
    {
        return $this->get("/payments/{$id}");
    }

    // ==================
    // Refunds
    // ==================

    public function createRefund(string $paymentId, int $amount, string $reason = 'requested_by_customer'): array
    {
        return $this->post('/refunds', [
            'data' => [
                'attributes' => [
                    'amount' => $amount,
                    'payment_id' => $paymentId,
                    'reason' => $reason,
                ],
            ],
        ]);
    }

    // ==================
    // Subscriptions
    // ==================

    public function createPlan(array $attributes): array
    {
        return $this->post('/subscriptions/plans', ['data' => ['attributes' => $attributes]]);
    }

    public function createSubscription(array $attributes): array
    {
        return $this->post('/subscriptions', ['data' => ['attributes' => $attributes]]);
    }

    public function cancelSubscription(string $id): array
    {
        return $this->post("/subscriptions/{$id}/cancel");
    }

    public function updateSubscriptionPlan(string $id, string $planId): array
    {
        return $this->post("/subscriptions/{$id}/change_plan", [
            'data' => [
                'attributes' => ['plan_id' => $planId],
            ],
        ]);
    }

    public function getSubscription(string $id): array
    {
        return $this->get("/subscriptions/{$id}");
    }

    public function changePaymentMethod(string $subscriptionId, string $paymentMethodId): array
    {
        return $this->post("/subscriptions/{$subscriptionId}/change_payment_method", [
            'data' => [
                'attributes' => ['payment_method_id' => $paymentMethodId],
            ],
        ]);
    }

    public function payInvoice(string $invoiceId): array
    {
        return $this->post("/invoices/{$invoiceId}/pay");
    }

    // ==================
    // Webhooks
    // ==================

    public function listWebhooks(): array
    {
        return $this->get('/webhooks');
    }

    public function createWebhook(string $url, array $events): array
    {
        return $this->post('/webhooks', [
            'data' => [
                'attributes' => [
                    'url' => $url,
                    'events' => $events,
                ],
            ],
        ]);
    }

    public function enableWebhook(string $id): array
    {
        return $this->post("/webhooks/{$id}/enable");
    }

    public function disableWebhook(string $id): array
    {
        return $this->post("/webhooks/{$id}/disable");
    }

    // ==================
    // HTTP Helpers
    // ==================

    public function getSecretKey(): string
    {
        return $this->secretKey;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    protected function request(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withBasicAuth($this->secretKey, '')
            ->timeout($this->timeout)
            ->retry($this->retries, 100, throw: false);
    }

    protected function get(string $endpoint): array
    {
        $response = $this->request()->get("{$this->baseUrl}{$endpoint}");

        if (! $response->successful()) {
            $this->throwException($response);
        }

        return $response->json('data') ?? [];
    }

    protected function post(string $endpoint, array $data = []): array
    {
        $response = empty($data)
            ? $this->request()->post("{$this->baseUrl}{$endpoint}")
            : $this->request()->post("{$this->baseUrl}{$endpoint}", $data);

        if (! $response->successful()) {
            $this->throwException($response);
        }

        return $response->json('data') ?? [];
    }

    protected function throwException(\Illuminate\Http\Client\Response $response): never
    {
        $body = $response->json();
        $detail = $body['errors'][0]['detail'] ?? $response->body();

        throw new PayMongoException(
            "PayMongo API error: {$detail}",
            $response->status(),
            $body
        );
    }
}
