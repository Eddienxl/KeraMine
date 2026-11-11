<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_returns_a_successful_response(): void
    {
        Http::fake(['*' => Http::response(null, 503)]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('KeraMine B2B');
    }
}
