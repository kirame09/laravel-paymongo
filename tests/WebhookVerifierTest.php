<?php

namespace Kirame\PayMongo\Tests;

use Kirame\PayMongo\Exceptions\PayMongoException;
use Kirame\PayMongo\WebhookVerifier;

class WebhookVerifierTest extends TestCase
{
    protected WebhookVerifier $verifier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->verifier = app(WebhookVerifier::class);
    }

    public function test_it_resolves_from_container(): void
    {
        $verifier = app(WebhookVerifier::class);
        $this->assertInstanceOf(WebhookVerifier::class, $verifier);
    }

    public function test_it_verifies_valid_live_signature(): void
    {
        $secret = 'whsec_test_secret';
        $timestamp = '1234567890';
        $payload = '{"data":{"id":"evt_123","attributes":{"type":"payment.paid"}}}';

        $signedPayload = $timestamp.'.'.$payload;
        $computedSignature = hash_hmac('sha256', $signedPayload, $secret);

        $signature = "t={$timestamp},li={$computedSignature}";

        $result = $this->verifier->verify($payload, $signature, $secret, 'production');

        $this->assertEquals('evt_123', $result->data->id);
    }

    public function test_it_verifies_valid_test_signature(): void
    {
        $secret = 'whsec_test_secret';
        $timestamp = '1234567890';
        $payload = '{"data":{"id":"evt_456"}}';

        $signedPayload = $timestamp.'.'.$payload;
        $computedSignature = hash_hmac('sha256', $signedPayload, $secret);

        $signature = "t={$timestamp},te={$computedSignature}";

        $result = $this->verifier->verify($payload, $signature, $secret, 'sandbox');

        $this->assertEquals('evt_456', $result->data->id);
    }

    public function test_it_rejects_invalid_signature(): void
    {
        $this->expectException(PayMongoException::class);
        $this->expectExceptionMessage('Invalid PayMongo webhook signature');

        $this->verifier->verify(
            '{"data":{}}',
            't=12345,li=invalid_signature',
            'whsec_test_secret',
            'production'
        );
    }

    public function test_it_rejects_missing_timestamp(): void
    {
        $this->expectException(PayMongoException::class);

        $this->verifier->verify(
            '{"data":{}}',
            'li=some_signature',
            'whsec_test_secret',
            'production'
        );
    }

    public function test_it_rejects_missing_signature_for_environment(): void
    {
        $this->expectException(PayMongoException::class);

        // Production expects 'li' but only 'te' is provided
        $this->verifier->verify(
            '{"data":{}}',
            't=12345,te=some_test_signature',
            'whsec_test_secret',
            'production'
        );
    }

    public function test_it_rejects_invalid_json(): void
    {
        $secret = 'whsec_test_secret';
        $timestamp = '1234567890';
        $payload = 'not valid json';

        $signedPayload = $timestamp.'.'.$payload;
        $computedSignature = hash_hmac('sha256', $signedPayload, $secret);

        $signature = "t={$timestamp},li={$computedSignature}";

        $this->expectException(PayMongoException::class);
        $this->expectExceptionMessage('Invalid JSON payload');

        $this->verifier->verify($payload, $signature, $secret, 'production');
    }

    public function test_is_valid_returns_boolean(): void
    {
        $secret = 'whsec_test_secret';
        $timestamp = '1234567890';
        $payload = '{"data":{}}';

        $signedPayload = $timestamp.'.'.$payload;
        $computedSignature = hash_hmac('sha256', $signedPayload, $secret);

        $validSignature = "t={$timestamp},li={$computedSignature}";
        $invalidSignature = "t={$timestamp},li=wrong";

        $this->assertTrue($this->verifier->isValid($payload, $validSignature, $secret, 'production'));
        $this->assertFalse($this->verifier->isValid($payload, $invalidSignature, $secret, 'production'));
    }

    public function test_sandbox_falls_back_to_live_signature(): void
    {
        $secret = 'whsec_test_secret';
        $timestamp = '1234567890';
        $payload = '{"data":{"id":"evt_789"}}';

        $signedPayload = $timestamp.'.'.$payload;
        $computedSignature = hash_hmac('sha256', $signedPayload, $secret);

        // Only 'li' provided, but environment is sandbox — should still work
        $signature = "t={$timestamp},li={$computedSignature}";

        $this->assertTrue($this->verifier->isValid($payload, $signature, $secret, 'sandbox'));
    }
}
