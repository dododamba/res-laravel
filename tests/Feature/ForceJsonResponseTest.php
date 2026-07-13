<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Route;

class ForceJsonResponseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::post('/api/test-validation', function () {
            request()->validate([
                'name' => 'required',
            ]);
            return response()->json(['success' => true]);
        });
    }

    public function test_api_route_forces_json_response_on_validation_failure()
    {
        // Even when sending a request WITHOUT Accept: application/json header,
        // it should still return a JSON response with status 422 instead of redirecting.
        $response = $this->post('/api/test-validation', [], [
            'Accept' => 'text/html',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }
}
