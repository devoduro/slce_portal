@extends('components.applicant-app-layout')

@section('header')
    <div class="flex flex-col space-y-1">
        <h2 class="text-2xl font-bold text-gray-800">{{ Auth::user()->first_login ? 'Set New Password' : 'Change Password' }}</h2>
        <p class="text-gray-600">{{ Auth::user()->first_login ? 'Please set a new password for your account to continue' : 'Please change your password for security' }}</p>
    </div>
@endsection

@section('content')
<div class="max-w-2xl mx-auto py-6">
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="p-6">
            <form action="{{ route('applicant.update-password') }}" method="POST" class="space-y-6">
                @csrf

                @if($errors->any())
                    <div class="p-4 bg-red-50 border border-red-100 rounded-lg">
                        <ul class="list-disc pl-5 space-y-1 text-sm text-red-700">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="space-y-4">
                    @if(!Auth::user()->first_login)
                    <div>
                        <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
                        <input type="password" name="current_password" id="current_password" required
                               class="mt-1 shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 rounded-md">
                    </div>
                    @endif

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">New Password</label>
                        <input type="password" name="password" id="password" required
                               class="mt-1 shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 rounded-md">
                        <p class="mt-1 text-xs text-gray-500">Must be at least 8 characters long</p>
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" required
                               class="mt-1 shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 rounded-md">
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">
                        Change Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
