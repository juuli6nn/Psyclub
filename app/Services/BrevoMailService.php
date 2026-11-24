<?php

namespace App\Services;

use App\Models\MembershipApplication;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use RuntimeException;

class BrevoMailService
{
    public function sendVerificationEmail(User $user): void
    {
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes((int) config('auth.verification.expire', 60)),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ],
        );

        $this->send(
            $user->getEmailForVerification(),
            $user->name,
            'Verify your PsyClub email address',
            view('emails.verify-email', [
                'user' => $user,
                'verificationUrl' => $verificationUrl,
            ])->render(),
        );
    }

    public function sendMembershipApplicationEmail(MembershipApplication $application): void
    {
        $this->send(
            $application->email,
            $application->full_name,
            'PsyClub membership application received',
            view('emails.membership-application-submitted', [
                'application' => $application,
            ])->render(),
        );
    }

    private function send(string $recipient, string $recipientName, string $subject, string $htmlContent): void
    {
        $apiKey = config('services.brevo.api_key');

        if (! is_string($apiKey) || trim($apiKey) === '') {
            throw new RuntimeException('Brevo API key is not configured.');
        }

        Http::acceptJson()
            ->withHeaders(['api-key' => $apiKey])
            ->timeout(10)
            ->post(config('services.brevo.endpoint'), [
                'sender' => [
                    'email' => config('mail.from.address'),
                    'name' => config('mail.from.name'),
                ],
                'to' => [[
                    'email' => $recipient,
                    'name' => $recipientName,
                ]],
                'subject' => $subject,
                'htmlContent' => $htmlContent,
            ])
            ->throw();
    }
}
