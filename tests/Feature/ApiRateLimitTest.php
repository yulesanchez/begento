<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('limita las solicitudes por cliente y responde 429 con Retry-After', function () {
    for ($i = 0; $i < 60; $i++) {
        $this->getJson('/api/v1/products')->assertOk();
    }

    $response = $this->getJson('/api/v1/products');

    $response->assertStatus(429)
        ->assertHeader('Retry-After')
        ->assertJsonPath('error', 'TOO_MANY_REQUESTS')
        ->assertJsonPath('data', null);
});
