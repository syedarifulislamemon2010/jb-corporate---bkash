<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test verifying the application redirects root requests to the admin portal.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        // Root URL '/' redirects to Filament admin '/admin'
        $response->assertRedirect('/admin');
    }
}
