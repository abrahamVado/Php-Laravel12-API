<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    //1.- Validate that the health endpoint reports the application as ready.
    public function test_health_endpoint_returns_success_payload(): void
    {
        //2.- Send a JSON request to the /api/health route without authentication.
        $response = $this->getJson('/api/health');

        //3.- Confirm the HTTP status code indicates success.
        $response->assertOk();

        //4.- Ensure the payload contains the expected ok flag set to true.
        $response->assertJson([
            'ok' => true,
        ]);
    }
}
