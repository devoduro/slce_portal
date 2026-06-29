<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function edit()
    {
        $user = Auth::user();
        return view('auth.change-password', [
            'is_first_login' => $user->first_login,
            'role' => $user->role
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'current_password' => ['required', function ($attribute, $value, $fail) {
                if (!Hash::check($value, Auth::user()->password)) {
                    $fail('The current password is incorrect.');
                }
            }],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
            ],
        ]);

        $user = Auth::user();
        
        $user->update([
            'password' => Hash::make($request->password),
            'first_login' => false,
        ]);

        $redirectRoute = $user->role === 'student' ? 'student.dashboard' : 'dashboard';
        return redirect()->route($redirectRoute)
            ->with('success', 'Password updated successfully. Please use your new password for future logins.');
    }
}
