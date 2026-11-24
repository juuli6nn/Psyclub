<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_sends_verification_email_through_brevo(): void
    {
        Http::fake([
            'https://api.brevo.com/*' => Http::response(['messageId' => 'test-message'], 201),
        ]);
        config([
            'services.brevo.api_key' => 'test-brevo-api-key',
            'mail.from.address' => 'no-reply@example.test',
            'mail.from.name' => 'PsyClub',
        ]);

        $response = $this->post(route('register.attempt'), [
            'username' => 'anaadmin',
            'email' => 'ana@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('verification.notice'));
        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://api.brevo.com/v3/smtp/email'
                && $request->header('api-key') === ['test-brevo-api-key']
                && data_get($request->data(), 'to.0.email') === 'ana@example.test';
        });
    }
}
