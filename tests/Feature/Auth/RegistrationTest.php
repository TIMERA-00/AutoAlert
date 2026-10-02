<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'Awa',
            'last_name' => 'Ndiaye',
            'phone' => '+221 77 000 00 00',
            'email' => 'test@example.com',
            'password' => 'Password@2024',
            'password_confirmation' => 'Password@2024',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $this->assertDatabaseHas('users', [
            'first_name' => 'Awa',
            'last_name' => 'Ndiaye',
            'email' => 'test@example.com',
        ]);
    }

    public function test_registration_requires_a_first_and_last_name(): void
    {
        $this->post('/register', [
            'first_name' => '',
            'last_name' => '',
            'email' => 'test@example.com',
            'password' => 'Password@2024',
            'password_confirmation' => 'Password@2024',
        ])->assertSessionHasErrors(['first_name', 'last_name']);

        $this->assertGuest();
    }
}
