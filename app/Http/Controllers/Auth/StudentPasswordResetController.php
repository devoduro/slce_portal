<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use App\Services\PastechSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StudentPasswordResetController extends Controller
{
    // ──────────────────────────────────────────────
    // Step 1 – show the phone number entry form
    // ──────────────────────────────────────────────
    public function showRequestForm()
    {
        return view('auth.student-forgot-password');
    }

    // ──────────────────────────────────────────────
    // Step 2 – verify phone, generate token, send SMS
    // ──────────────────────────────────────────────
    public function sendResetLink(Request $request, PastechSmsService $sms)
    {
        $request->validate([
            'phone' => ['required', 'string'],
        ]);

        $phone = $request->phone;

        // Find student by phone number
        $student = Student::where('phone', $phone)->first();

        if (! $student) {
            return back()->withErrors(['phone' => 'No student account found with that phone number.'])->withInput();
        }

        // Find the user account linked to this student
        $user = User::where('student_id', $student->id)->first();

        if (! $user) {
            return back()->withErrors(['phone' => 'No login account exists for this student. Contact the administrator.'])->withInput();
        }

        // Generate a secure token
        $token = Str::random(64);

        // Store / replace token in password_reset_tokens
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['token' => Hash::make($token), 'created_at' => now()]
        );

        // Build reset URL – use the student-specific reset route
        $resetUrl = url(route('student.password.reset', ['token' => $token, 'email' => $user->email], false));

        $message = "Hello {$student->full_name},\n\nUse the link below to reset your SLCE portal password. It expires in 60 minutes.\n\n{$resetUrl}\n\nIf you did not request this, ignore this message.";

        $result = $sms->sendSms($phone, $message);

        if (! $result['success']) {
            return back()->with('error', 'We could not send the SMS: ' . $result['message'])->withInput();
        }

        return back()->with('status', 'A password reset link has been sent to your phone number.');
    }

    // ──────────────────────────────────────────────
    // Step 3 – show the new password form
    // ──────────────────────────────────────────────
    public function showResetForm(Request $request, string $token)
    {
        return view('auth.student-reset-password', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    // ──────────────────────────────────────────────
    // Step 4 – validate token and update password
    // ──────────────────────────────────────────────
    public function reset(Request $request)
    {
        $request->validate([
            'token'                 => ['required'],
            'email'                 => ['required', 'email'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required'],
        ]);

        // Retrieve the stored token record
        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (! $record) {
            return back()->withErrors(['token' => 'This password reset link is invalid or has expired.']);
        }

        // Check expiry (60 minutes)
        if (now()->diffInMinutes($record->created_at) > 60) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return back()->withErrors(['token' => 'This password reset link has expired. Please request a new one.']);
        }

        if (! Hash::check($request->token, $record->token)) {
            return back()->withErrors(['token' => 'This password reset link is invalid.']);
        }

        // Update the user's password
        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return back()->withErrors(['email' => 'No account found for this email.']);
        }

        $user->password    = Hash::make($request->password);
        $user->first_login = false;
        $user->save();

        // Remove used token
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return redirect()->route('student.login')
            ->with('status', 'Your password has been reset. You can now log in.');
    }
}
