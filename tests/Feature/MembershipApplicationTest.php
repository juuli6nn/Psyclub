<?php

namespace Tests\Feature;

use App\Models\MembershipCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MembershipApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_is_stored_and_email_notification_is_sent(): void
    {
        Http::fake([
            'https://api.brevo.com/*' => Http::response(['messageId' => 'test-message'], 201),
        ]);
        config([
            'services.brevo.api_key' => 'test-brevo-api-key',
            'mail.from.address' => 'no-reply@example.test',
            'mail.from.name' => 'PsyClub',
        ]);
        $category = MembershipCategory::create(['name' => 'Regular', 'fee' => 500]);

        $response = $this->post(route('membership.store'), [
            'membership_category_id' => $category->id,
            'full_name' => 'Ana Cruz',
            'email' => 'ana@example.test',
            'latitude' => 14.5995,
            'longitude' => 120.9842,
        ]);

        $response->assertRedirect(route('membership.create'));
        $this->assertDatabaseHas('membership_applications', [
            'email' => 'ana@example.test',
            'status' => 'pending',
        ]);
        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://api.brevo.com/v3/smtp/email'
                && $request->header('api-key') === ['test-brevo-api-key']
                && data_get($request->data(), 'to.0.email') === 'ana@example.test';
        });
    }
}
