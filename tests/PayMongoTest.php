<?php

namespace Kirame\PayMongo\Tests;

use Illuminate\Support\Facades\Http;
use Kirame\PayMongo\Exceptions\PayMongoException;
use Kirame\PayMongo\PayMongo;

class PayMongoTest extends TestCase
{
    protected PayMongo $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = app(PayMongo::class);
    }

    public function test_it_resolves_from_container(): void
    {
        $client = app(PayMongo::class);
        $this->assertInstanceOf(PayMongo::class, $client);
        $this->assertEquals('sk_test_abc123', $client->getSecretKey());
    }

    public function test_it_creates_customer(): void
    {
        Http::fake([
            'api.paymongo.com/v1/customers' => Http::response([
                'data' => [
                    'id' => 'cust_abc123',
                    'attributes' => [
                        'first_name' => 'John',
                        'last_name' => 'Doe',
                        'email' => 'john@example.com',
                    ],
                ],
            ]),
        ]);

        $result = $this->client->createCustomer([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
        ]);

        $this->assertEquals('cust_abc123', $result['id']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/customers')
                && $request->data()['data']['attributes']['first_name'] === 'John';
        });
    }

    public function test_it_gets_customer(): void
    {
        Http::fake([
            'api.paymongo.com/v1/customers/cust_abc123' => Http::response([
                'data' => [
                    'id' => 'cust_abc123',
                    'attributes' => ['email' => 'john@example.com'],
                ],
            ]),
        ]);

        $result = $this->client->getCustomer('cust_abc123');

        $this->assertEquals('cust_abc123', $result['id']);
    }

    public function test_it_creates_payment_intent(): void
    {
        Http::fake([
            'api.paymongo.com/v1/payment_intents' => Http::response([
                'data' => [
                    'id' => 'pi_abc123',
                    'attributes' => [
                        'amount' => 10000,
                        'currency' => 'PHP',
                        'status' => 'awaiting_payment_method',
                        'client_key' => 'client_key_abc',
                    ],
                ],
            ]),
        ]);

        $result = $this->client->createPaymentIntent([
            'amount' => 10000,
            'currency' => 'PHP',
            'payment_method_allowed' => ['qrph'],
        ]);

        $this->assertEquals('pi_abc123', $result['id']);
        $this->assertEquals(10000, $result['attributes']['amount']);
    }

    public function test_it_retrieves_payment_intent(): void
    {
        Http::fake([
            'api.paymongo.com/v1/payment_intents/pi_abc123' => Http::response([
                'data' => [
                    'id' => 'pi_abc123',
                    'attributes' => ['status' => 'succeeded'],
                ],
            ]),
        ]);

        $result = $this->client->retrievePaymentIntent('pi_abc123');

        $this->assertEquals('succeeded', $result['attributes']['status']);
    }

    public function test_it_creates_payment_method(): void
    {
        Http::fake([
            'api.paymongo.com/v1/payment_methods' => Http::response([
                'data' => [
                    'id' => 'pm_abc123',
                    'attributes' => ['type' => 'qrph'],
                ],
            ]),
        ]);

        $result = $this->client->createPaymentMethod([
            'type' => 'qrph',
            'billing' => ['name' => 'John Doe'],
        ]);

        $this->assertEquals('pm_abc123', $result['id']);
    }

    public function test_it_attaches_payment_method(): void
    {
        Http::fake([
            'api.paymongo.com/v1/payment_intents/pi_abc123/attach' => Http::response([
                'data' => [
                    'id' => 'pi_abc123',
                    'attributes' => [
                        'status' => 'awaiting_next_action',
                        'next_action' => [
                            'type' => 'code',
                            'code' => ['image_url' => 'data:image/png;base64,qrcode_data'],
                        ],
                    ],
                ],
            ]),
        ]);

        $result = $this->client->attachPaymentMethod('pi_abc123', 'pm_abc123', 'client_key');

        $this->assertEquals('data:image/png;base64,qrcode_data', $result['attributes']['next_action']['code']['image_url']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/attach')
                && $request->data()['data']['attributes']['payment_method'] === 'pm_abc123'
                && $request->data()['data']['attributes']['client_key'] === 'client_key';
        });
    }

    public function test_it_creates_checkout_session(): void
    {
        Http::fake([
            'api.paymongo.com/v1/checkout_sessions' => Http::response([
                'data' => [
                    'id' => 'cs_abc123',
                    'attributes' => [
                        'checkout_url' => 'https://checkout.paymongo.com/cs_abc123',
                        'status' => 'active',
                    ],
                ],
            ]),
        ]);

        $result = $this->client->createCheckoutSession([
            'line_items' => [[
                'name' => 'Test Item',
                'amount' => 10000,
                'currency' => 'PHP',
                'quantity' => 1,
            ]],
            'payment_method_types' => ['gcash', 'card'],
            'success_url' => 'https://example.com/success',
            'cancel_url' => 'https://example.com/cancel',
        ]);

        $this->assertEquals('cs_abc123', $result['id']);
        $this->assertEquals('https://checkout.paymongo.com/cs_abc123', $result['attributes']['checkout_url']);
    }

    public function test_it_retrieves_checkout_session(): void
    {
        Http::fake([
            'api.paymongo.com/v1/checkout_sessions/cs_abc123' => Http::response([
                'data' => [
                    'id' => 'cs_abc123',
                    'attributes' => ['status' => 'paid'],
                ],
            ]),
        ]);

        $result = $this->client->retrieveCheckoutSession('cs_abc123');

        $this->assertEquals('paid', $result['attributes']['status']);
    }

    public function test_it_creates_refund(): void
    {
        Http::fake([
            'api.paymongo.com/v1/refunds' => Http::response([
                'data' => [
                    'id' => 'ref_abc123',
                    'attributes' => [
                        'amount' => 5000,
                        'status' => 'pending',
                    ],
                ],
            ]),
        ]);

        $result = $this->client->createRefund('pay_abc123', 5000);

        $this->assertEquals('ref_abc123', $result['id']);
    }

    public function test_it_creates_subscription(): void
    {
        Http::fake([
            'api.paymongo.com/v1/subscriptions' => Http::response([
                'data' => [
                    'id' => 'sub_abc123',
                    'attributes' => ['status' => 'active'],
                ],
            ]),
        ]);

        $result = $this->client->createSubscription([
            'customer_id' => 'cust_abc123',
            'plan_id' => 'plan_abc123',
        ]);

        $this->assertEquals('sub_abc123', $result['id']);
    }

    public function test_it_cancels_subscription(): void
    {
        Http::fake([
            'api.paymongo.com/v1/subscriptions/sub_abc123/cancel' => Http::response([
                'data' => [
                    'id' => 'sub_abc123',
                    'attributes' => ['status' => 'cancelled'],
                ],
            ]),
        ]);

        $result = $this->client->cancelSubscription('sub_abc123');

        $this->assertEquals('cancelled', $result['attributes']['status']);
    }

    public function test_it_lists_webhooks(): void
    {
        Http::fake([
            'api.paymongo.com/v1/webhooks' => Http::response([
                'data' => [
                    ['id' => 'hook_1', 'attributes' => ['url' => 'https://example.com/webhook']],
                ],
            ]),
        ]);

        $result = $this->client->listWebhooks();

        $this->assertIsArray($result);
    }

    public function test_it_throws_exception_on_api_error(): void
    {
        Http::fake([
            'api.paymongo.com/v1/payment_intents' => Http::response([
                'errors' => [['code' => 'parameter_invalid', 'detail' => 'Amount is required']],
            ], 400),
        ]);

        $this->expectException(PayMongoException::class);

        $this->client->createPaymentIntent([]);
    }

    public function test_exception_contains_status_code_and_body(): void
    {
        Http::fake([
            'api.paymongo.com/v1/customers/invalid' => Http::response([
                'errors' => [['code' => 'resource_not_found', 'detail' => 'Not found']],
            ], 404),
        ]);

        try {
            $this->client->getCustomer('invalid');
            $this->fail('Expected PayMongoException');
        } catch (PayMongoException $e) {
            $this->assertEquals(404, $e->getStatusCode());
            $this->assertEquals('resource_not_found', $e->getErrorType());
            $this->assertEquals('Not found', $e->getErrorDetail());
        }
    }

    public function test_full_qr_ph_flow(): void
    {
        Http::fake([
            'api.paymongo.com/v1/payment_intents' => Http::response([
                'data' => [
                    'id' => 'pi_qr123',
                    'attributes' => [
                        'amount' => 50000,
                        'status' => 'awaiting_payment_method',
                        'client_key' => 'client_key_qr',
                    ],
                ],
            ]),
            'api.paymongo.com/v1/payment_methods' => Http::response([
                'data' => [
                    'id' => 'pm_qr123',
                    'attributes' => ['type' => 'qrph'],
                ],
            ]),
            'api.paymongo.com/v1/payment_intents/pi_qr123/attach' => Http::response([
                'data' => [
                    'id' => 'pi_qr123',
                    'attributes' => [
                        'status' => 'awaiting_next_action',
                        'next_action' => [
                            'type' => 'code',
                            'code' => ['image_url' => 'data:image/png;base64,QRCODE'],
                        ],
                    ],
                ],
            ]),
        ]);

        // Step 1: Create intent
        $intent = $this->client->createPaymentIntent([
            'amount' => 50000,
            'currency' => 'PHP',
            'payment_method_allowed' => ['qrph'],
        ]);
        $this->assertEquals('pi_qr123', $intent['id']);

        // Step 2: Create method
        $method = $this->client->createPaymentMethod([
            'type' => 'qrph',
            'billing' => ['name' => 'Customer'],
        ]);
        $this->assertEquals('pm_qr123', $method['id']);

        // Step 3: Attach
        $result = $this->client->attachPaymentMethod(
            $intent['id'],
            $method['id'],
            $intent['attributes']['client_key']
        );

        $qrImage = $result['attributes']['next_action']['code']['image_url'];
        $this->assertEquals('data:image/png;base64,QRCODE', $qrImage);
    }
}
