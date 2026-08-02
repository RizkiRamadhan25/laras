<?php

namespace Tests\Feature;

use Tests\TestCase;

class RequestIdTest extends TestCase
{
    public function test_web_response_receives_generated_request_id(): void
    {
        $response = $this->get('/login');

        $response
            ->assertOk()
            ->assertHeader('X-Request-ID');

        $requestId = (string) $response
            ->headers
            ->get('X-Request-ID');

        $this->assertMatchesRegularExpression(
            '/\A[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/i',
            $requestId
        );
    }

    public function test_valid_upstream_request_id_is_preserved(): void
    {
        $requestId = 'laras-test-request-0001';

        $this
            ->withHeader(
                'X-Request-ID',
                $requestId
            )
            ->get('/login')
            ->assertOk()
            ->assertHeader(
                'X-Request-ID',
                $requestId
            );
    }

    public function test_invalid_upstream_request_id_is_replaced(): void
    {
        $response = $this
            ->withHeader(
                'X-Request-ID',
                'invalid request id ***'
            )
            ->get('/login');

        $response->assertOk();

        $this->assertNotSame(
            'invalid request id ***',
            $response->headers->get(
                'X-Request-ID'
            )
        );
    }

    public function test_unmatched_route_receives_request_id(): void
    {
        config()->set(
            'app.debug',
            false
        );

        $response = $this->get(
            '/__laras_route_that_does_not_exist__'
        );

        $response
            ->assertNotFound()
            ->assertHeader('X-Request-ID')
            ->assertSee('Halaman tidak ditemukan')
            ->assertSee('Kode referensi:');

        $requestId = (string) $response
            ->headers
            ->get('X-Request-ID');

        $this->assertNotSame(
            '',
            $requestId
        );

        $this->assertStringContainsString(
            $requestId,
            $response->getContent()
        );
    }
}
