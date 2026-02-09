<?php

namespace Kirame\PayMongo;

use Kirame\PayMongo\Exceptions\PayMongoException;

class WebhookVerifier
{
    /**
     * Verify a PayMongo webhook signature and return the parsed event.
     *
     * @throws PayMongoException
     */
    public function verify(string $payload, string $signature, string $secret, string $environment = 'production'): object
    {
        if (! $this->isValid($payload, $signature, $secret, $environment)) {
            throw new PayMongoException('Invalid PayMongo webhook signature');
        }

        $data = json_decode($payload);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new PayMongoException('Invalid JSON payload');
        }

        return $data;
    }

    /**
     * Check if a PayMongo webhook signature is valid.
     */
    public function isValid(string $payload, string $signature, string $secret, string $environment = 'production'): bool
    {
        $parts = $this->parseSignature($signature);

        $timestamp = $parts['t'] ?? null;
        $testSignature = $parts['te'] ?? null;
        $liveSignature = $parts['li'] ?? null;

        if (! $timestamp) {
            return false;
        }

        $expectedSignature = $environment === 'production'
            ? $liveSignature
            : ($testSignature ?? $liveSignature);

        if (! $expectedSignature) {
            return false;
        }

        $signedPayload = $timestamp.'.'.$payload;
        $computedSignature = hash_hmac('sha256', $signedPayload, $secret);

        return hash_equals($computedSignature, $expectedSignature);
    }

    /**
     * Parse the PayMongo signature header into key-value pairs.
     *
     * Format: "t=timestamp,te=test_signature,li=live_signature"
     */
    protected function parseSignature(string $signature): array
    {
        $parts = [];

        foreach (explode(',', $signature) as $part) {
            $segments = explode('=', $part, 2);
            if (count($segments) === 2) {
                $parts[$segments[0]] = $segments[1];
            }
        }

        return $parts;
    }
}
