<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_portal_displays_graceful_empty_state(): void
    {
        $this->get('/')->assertOk()->assertSee('Informasi unit bisnis sedang diperbarui');
    }
}
