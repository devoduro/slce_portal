<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ApplicantAuthController extends Controller
{
    /**
     * Show the applicant login form.
     */
    public function showLoginForm()
    {
        return view('auth.applicant-login');
    }

    /**
     * Handle applicant login request. Mirrors StudentAuthController::login(), keyed on
     * applicant_number instead of index_number.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'applicant_number' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()->route('applicant.login')
                ->withErrors($validator)
                ->withInput();
        }

        $admission = Admission::where('applicant_number', $request->applicant_number)->first();

        if (!$admission) {
            return redirect()->route('applicant.login')
                ->withErrors(['applicant_number' => 'Invalid credentials'])
                ->withInput();
        }

        $user = User::where('admission_id', $admission->id)->first();

        if (!$user || $user->role !== User::ROLE_APPLICANT) {
            return redirect()->route('applicant.login')
                ->withErrors(['applicant_number' => 'Invalid applicant account'])
                ->withInput();
        }

        if (Auth::attempt(['email' => $user->email, 'password' => $request->password, 'role' => User::ROLE_APPLICANT])) {
            $request->session()->regenerate();

            app(ActivityLogger::class)->log('login', 'Applicant logged in successfully');

            if ($user->first_login) {
                return redirect()->route('applicant.change-password')
                    ->with('warning', 'Please change your password before continuing.');
            }

            return redirect()->route('applicant.dashboard');
        }

        return redirect()->route('applicant.login')
            ->withErrors(['applicant_number' => 'Invalid credentials'])
            ->withInput();
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            app(ActivityLogger::class)->log('logout', 'Applicant logged out');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('applicant.login')->with('message', 'You have been logged out successfully.');
    }

    public function showChangePasswordForm()
    {
        return view('applicant.change-password');
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        if ($user->first_login) {
            $request->validate([
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);
        } else {
            $request->validate([
                'current_password' => ['required', 'string'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);

            if (!Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => 'The current password is incorrect.']);
            }
        }

        $user->password = Hash::make($request->password);
        $user->first_login = false;
        $user->save();

        return redirect()->route('applicant.dashboard')->with('success', 'Password updated successfully.');
    }
}
