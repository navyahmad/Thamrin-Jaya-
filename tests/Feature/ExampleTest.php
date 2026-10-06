<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_site_is_not_public_and_published_empty_gateway_has_an_empty_state(): void
    {
        $this->get('/')->assertNotFound();
        $this->publishSite();
        $this->get('/')->assertOk()->assertSee('Informasi unit bisnis sedang diperbarui');
    }
}
