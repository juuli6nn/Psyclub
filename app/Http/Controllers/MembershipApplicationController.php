<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMembershipApplicationRequest;
use App\Models\Member;
use App\Models\MembershipApplication;
use App\Models\MembershipCategory;
use App\Models\MembershipLevel;
use App\Models\MembershipTransaction;
use App\Services\BrevoMailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MembershipApplicationController extends Controller
{
    public function __construct(private readonly BrevoMailService $brevoMailService) {}

    public function create(): View
    {
        return view('membership.create', [
            'categories' => MembershipCategory::where('is_active', true)->orderBy('name')->get(),
            'recentApplications' => MembershipApplication::with('category')->latest()->limit(5)->get(),
        ]);
    }

    public function store(StoreMembershipApplicationRequest $request): RedirectResponse
    {
        $application = DB::transaction(function () use ($request) {
            $validated = $request->validated();
            $referenceNo = 'MEM-'.Str::upper(Str::random(10));

            $application = MembershipApplication::create([
                ...$validated,
                'reference_no' => $referenceNo,
            ]);

            // Sync applicant with Admin Members & Transactions TPS tables
            $nameParts = explode(' ', trim($validated['full_name']), 2);
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? '-';

            $category = MembershipCategory::find($validated['membership_category_id']);
            $defaultLevel = MembershipLevel::first();

            $member = Member::updateOrCreate(
                ['email' => $validated['email']],
                [
                    'membership_number' => $referenceNo,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'phone_number' => $validated['phone'] ?? null,
                    'address' => $validated['address'] ?? null,
                    'latitude' => $validated['latitude'] ?? null,
                    'longitude' => $validated['longitude'] ?? null,
                    'is_active' => true,
                    'verification_sent_at' => now(),
                    'membership_category_id' => $validated['membership_category_id'],
                    'membership_level_id' => $defaultLevel?->id,
                ]
            );

            MembershipTransaction::create([
                'transaction_number' => 'TXN-'.now()->format('Ymd').'-'.Str::upper(Str::random(4)),
                'member_id' => $member->id,
                'membership_level_id' => $defaultLevel?->id,
                'type' => 'application',
                'amount' => $category && $category->fee > 0 ? $category->fee : ($defaultLevel ? $defaultLevel->fee : 0),
                'status' => 'Pending',
                'notes' => 'Submitted via Membership Application Portal ('.$referenceNo.').',
            ]);

            $this->brevoMailService->sendMembershipApplicationEmail($application);

            $application->update(['verification_sent_at' => now()]);

            return $application;
        });

        return redirect()->route('membership.create')->with(
            'success',
            "Application saved. Verification email queued. Reference: {$application->reference_no}"
        );
    }
}
