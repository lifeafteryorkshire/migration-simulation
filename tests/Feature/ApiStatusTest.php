<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiStatusTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_status_endpoint(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
