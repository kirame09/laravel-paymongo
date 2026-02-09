# Laravel PayMongo

A Laravel package for integrating with the [PayMongo](https://paymongo.com) payment gateway API.

Supports all PayMongo payment methods including GCash, Maya, GrabPay, Card, and QR Ph.

## Requirements

- PHP 8.2+
- Laravel 11 or 12

## Installation

```bash
composer require kirame/laravel-paymongo
```

Publish the config file:

```bash
php artisan vendor:publish --tag=paymongo-config
```

Add your keys to `.env`:

```env
PAYMONGO_PUBLIC_KEY=pk_test_...
PAYMONGO_SECRET_KEY=sk_test_...
PAYMONGO_WEBHOOK_SECRET=whsec_...
PAYMONGO_ENVIRONMENT=sandbox
```

## Usage

### Checkout Session

The simplest way to accept payments. Redirects the customer to a PayMongo-hosted checkout page.

```php
use Kirame\PayMongo\PayMongo;

public function checkout(PayMongo $paymongo)
{
    $session = $paymongo->createCheckoutSession([
        'billing' => [
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@example.com',
        ],
        'line_items' => [[
            'name' => 'Premium Plan',
            'amount' => 99900, // in centavos (₱999.00)
            'currency' => 'PHP',
            'quantity' => 1,
        ]],
        'payment_method_types' => ['gcash', 'card', 'grab_pay', 'paymaya'],
        'success_url' => 'https://example.com/success',
        'cancel_url' => 'https://example.com/cancel',
    ]);

    return redirect($session['attributes']['checkout_url']);
}
```

### QR Ph Payment (Payment Intent API)

Displays an inline QR code scannable by any Philippine e-wallet or bank app.

```php
use Kirame\PayMongo\PayMongo;

public function qrPayment(PayMongo $paymongo)
{
    // Step 1: Create Payment Intent
    $intent = $paymongo->createPaymentIntent([
        'amount' => 50000, // ₱500.00
        'currency' => 'PHP',
        'payment_method_allowed' => ['qrph'],
        'description' => 'Order #123',
    ]);

    // Step 2: Create Payment Method
    $method = $paymongo->createPaymentMethod([
        'type' => 'qrph',
        'billing' => ['name' => 'Juan Dela Cruz'],
    ]);

    // Step 3: Attach to get QR code
    $result = $paymongo->attachPaymentMethod(
        $intent['id'],
        $method['id'],
        $intent['attributes']['client_key']
    );

    $qrImage = $result['attributes']['next_action']['code']['image_url'];

    return view('payment.qr', ['qr_image' => $qrImage]);
}
```

### Webhooks

Verify incoming PayMongo webhook signatures:

```php
use Kirame\PayMongo\WebhookVerifier;

public function handleWebhook(Request $request, WebhookVerifier $verifier)
{
    $payload = $request->getContent();
    $signature = $request->header('Paymongo-Signature');

    try {
        $event = $verifier->verify(
            $payload,
            $signature,
            config('paymongo.webhook_secret'),
            config('paymongo.environment')
        );
    } catch (\Exception $e) {
        return response('OK', 200); // Always return 200 to avoid webhook disabling
    }

    $eventType = $event->data->attributes->type;

    switch ($eventType) {
        case 'checkout_session.payment.paid':
            // Handle successful payment
            break;
        case 'payment.paid':
            // Handle direct payment (QR Ph, etc.)
            break;
        case 'payment.failed':
            // Handle failed payment
            break;
    }

    return response('OK', 200);
}
```

### Customers

```php
$customer = $paymongo->createCustomer([
    'first_name' => 'Juan',
    'last_name' => 'Dela Cruz',
    'email' => 'juan@example.com',
    'phone' => '+639171234567',
]);

$customer = $paymongo->getCustomer('cust_...');
```

### Subscriptions

```php
// Create a plan
$plan = $paymongo->createPlan([
    'name' => 'Pro Plan',
    'amount' => 99900,
    'currency' => 'PHP',
    'interval' => 'month',
    'interval_count' => 1,
]);

// Create a subscription
$subscription = $paymongo->createSubscription([
    'customer_id' => 'cust_...',
    'plan_id' => $plan['id'],
]);

// Cancel
$paymongo->cancelSubscription('sub_...');
```

### Refunds

```php
$refund = $paymongo->createRefund('pay_...', 50000, 'requested_by_customer');
```

### Retrieve Resources

```php
$session = $paymongo->retrieveCheckoutSession('cs_...');
$intent = $paymongo->retrievePaymentIntent('pi_...');
$payment = $paymongo->retrievePayment('pay_...');
```

### Facade

You can also use the facade:

```php
use Kirame\PayMongo\Facades\PayMongo;

$session = PayMongo::createCheckoutSession([...]);
```

## API Reference

### PayMongo Client

| Method | Description |
|--------|-------------|
| `createCustomer(array $attributes)` | Create a customer |
| `getCustomer(string $id)` | Retrieve a customer |
| `createPaymentIntent(array $attributes)` | Create a payment intent |
| `retrievePaymentIntent(string $id)` | Retrieve a payment intent |
| `createPaymentMethod(array $attributes)` | Create a payment method |
| `attachPaymentMethod(string $intentId, string $methodId, ?string $clientKey)` | Attach payment method to intent |
| `createCheckoutSession(array $attributes)` | Create a checkout session |
| `retrieveCheckoutSession(string $id)` | Retrieve a checkout session |
| `retrievePayment(string $id)` | Retrieve a payment |
| `createRefund(string $paymentId, int $amount, string $reason)` | Create a refund |
| `createPlan(array $attributes)` | Create a subscription plan |
| `createSubscription(array $attributes)` | Create a subscription |
| `cancelSubscription(string $id)` | Cancel a subscription |
| `updateSubscriptionPlan(string $id, string $planId)` | Change subscription plan |
| `getSubscription(string $id)` | Retrieve a subscription |
| `changePaymentMethod(string $subscriptionId, string $paymentMethodId)` | Update payment method |
| `payInvoice(string $invoiceId)` | Pay an invoice |
| `listWebhooks()` | List all webhooks |
| `createWebhook(string $url, array $events)` | Create a webhook |
| `enableWebhook(string $id)` | Enable a webhook |
| `disableWebhook(string $id)` | Disable a webhook |

### WebhookVerifier

| Method | Description |
|--------|-------------|
| `verify(string $payload, string $signature, string $secret, string $environment)` | Verify signature and return parsed event (throws on failure) |
| `isValid(string $payload, string $signature, string $secret, string $environment)` | Check if signature is valid (returns boolean) |

## Testing

```bash
composer test
```

## License

MIT
