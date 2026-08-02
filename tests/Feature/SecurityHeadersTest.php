<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_web_responses_include_security_headers(): void
    {
        $response = $this->get(
            route('login')
        );

        $response
            ->assertOk()
            ->assertHeader(
                'X-Content-Type-Options',
                'nosniff'
            )
            ->assertHeader(
                'X-Frame-Options',
                'DENY'
            )
            ->assertHeader(
                'Referrer-Policy',
                'strict-origin-when-cross-origin'
            )
            ->assertHeader(
                'Cross-Origin-Opener-Policy',
                'same-origin'
            )
            ->assertHeader(
                'Cross-Origin-Resource-Policy',
                'same-origin'
            );

        $policy = (string) $response->headers->get(
            'Content-Security-Policy'
        );

        $this->assertStringContainsString(
            "frame-ancestors 'none'",
            $policy
        );

        $this->assertStringContainsString(
            "object-src 'none'",
            $policy
        );

        $this->assertStringContainsString(
            "form-action 'self'",
            $policy
        );
    }

    public function test_hsts_is_not_sent_over_plain_http(): void
    {
        $this->get(route('login'))
            ->assertHeaderMissing(
                'Strict-Transport-Security'
            );
    }
}
