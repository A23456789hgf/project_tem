<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ManualLoginFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_login_flow()
    {
        // 1. Inspect the user in database first
        $user = User::where('phone', '775540028')->orWhere('username', '775540028')->first();

        dump('[1] User lookup: '.($user ? "Found (ID: {$user->id}, Status: {$user->status}, Org: {$user->organization_type})" : 'Not Found in DB!'));
        if (! $user) {
            return;
        }

        // 2. Check hash
        $hashCheck = Hash::check('775540028', $user->password);
        dump('[2] Hash check: '.($hashCheck ? 'True' : 'False'));

        // 3. Attempt Login Request
        dump('[3] Sending HTTP POST to /login...');
        $response = $this->post('/login', [
            'phone' => '775540028',
            'password' => '775540028',
        ]);

        dump('[4] Login Response Status: '.$response->status());

        if ($response->isRedirect()) {
            dump('[4] Redirect Target: '.$response->headers->get('Location'));
            dump('[4] Errors: '.json_encode(session('errors')?->all()));
        } else {
            dump('[4] Content: '.substr($response->getContent(), 0, 100));
        }

        // 5. Check Authentication
        dump('[5] Auth::check() immediately after POST: '.(Auth::check() ? 'True (ID: '.Auth::id().')' : 'False'));

        if (Auth::check()) {
            // 6. Follow the redirect to Dashboard
            dump('[6] Following redirect to /dashboard...');
            $dashboardResponse = $this->get('/dashboard');

            dump('[7] Dashboard Response Status: '.$dashboardResponse->status());
            if ($dashboardResponse->isRedirect()) {
                dump('[7] Dashboard Redirect Target: '.$dashboardResponse->headers->get('Location'));
                dump('[7] Dashboard Session Errors: '.json_encode(session('errors')?->all()));
            }

            dump('[8] Auth::check() AFTER Dashboard: '.(Auth::check() ? 'True' : 'False'));
        }

        $this->assertTrue(true);
    }
}
