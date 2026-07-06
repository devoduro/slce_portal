<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Student') }}
            </h2>
            <x-button href="{{ route('students.index') }}" variant="secondary" icon="fas fa-arrow-left">
                {{ __('Back to Students') }}
            </x-button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form action="{{ route('students.update', $student->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        
                        @if ($errors->any())
                            <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-800 rounded-md">
                                <p class="font-medium">Please fix the following errors:</p>
                                <ul class="mt-2 list-disc list-inside">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Left Column -->
                            <div class="space-y-6">
                                <!-- Index Number -->
                                <div>
                                    <label for="index_number" class="block text-sm font-medium text-gray-700">{{ __('Index Number') }}</label>
                                    <input id="index_number" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm" type="text" name="index_number" value="{{ old('index_number', $student->index_number) }}" required />
                                    @error('index_number')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Full Name -->
                                <div>
                                    <label for="full_name" class="block text-sm font-medium text-gray-700">{{ __('Full Name') }}</label>
                                    <input id="full_name" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm" type="text" name="full_name" value="{{ old('full_name', $student->full_name) }}" required />
                                    @error('full_name')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Date of Birth -->
                                <div>
                                    <label for="date_of_birth" class="block text-sm font-medium text-gray-700">{{ __('Date of Birth') }}</label>
                                    <input id="date_of_birth" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm" type="date" name="date_of_birth" value="{{ old('date_of_birth', $student->date_of_birth ? $student->date_of_birth->format('Y-m-d') : '') }}" />
                                    @error('date_of_birth')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Gender -->
                                <div>
                                    <label for="gender" class="block text-sm font-medium text-gray-700">{{ __('Gender') }}</label>
                                    <select id="gender" name="gender" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm" required>
                                        <option value="">Select Gender</option>
                                        <option value="Male" {{ old('gender', $student->gender) == 'Male' ? 'selected' : '' }}>Male</option>
                                        <option value="Female" {{ old('gender', $student->gender) == 'Female' ? 'selected' : '' }}>Female</option>
                                        <option value="Other" {{ old('gender', $student->gender) == 'Other' ? 'selected' : '' }}>Other</option>
                                    </select>
                                    @error('gender')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Programme -->
                                <div>
                                    <label for="programme_id" class="block text-sm font-medium text-gray-700">{{ __('Programme') }}</label>
                                    <select id="programme_id" name="programme_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm" required>
                                        <option value="">Select Programme</option>
                                        @foreach($programmes as $programme)
                                            <option value="{{ $programme->id }}" {{ old('programme_id', $student->programme_id) == $programme->id ? 'selected' : '' }}>
                                                {{ $programme->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('programme_id')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Level -->
                                <div>
                                    <label for="level" class="block text-sm font-medium text-gray-700">{{ __('Level') }}</label>
                                    <select id="level" name="level" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                        <option value="">Select Level</option>
                                        @foreach([100, 200, 300, 400] as $levelOption)
                                            <option value="{{ $levelOption }}" {{ old('level', $student->level) == $levelOption ? 'selected' : '' }}>
                                                {{ $levelOption }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('level')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Class -->
                                <div>
                                    <label for="class_group_id" class="block text-sm font-medium text-gray-700">{{ __('Class') }}</label>
                                    <select id="class_group_id" name="class_group_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                        <option value="">Select Class (optional)</option>
                                        @foreach($classGroups as $classGroup)
                                            <option value="{{ $classGroup->id }}" {{ old('class_group_id', $student->class_group_id) == $classGroup->id ? 'selected' : '' }}>
                                                {{ $classGroup->name }} &mdash; {{ $classGroup->programme->name ?? '' }}, Level {{ $classGroup->level }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('class_group_id')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Right Column -->
                            <div class="space-y-6">
                                <!-- Email -->
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700">{{ __('Email') }}</label>
                                    <input id="email" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm" type="email" name="email" value="{{ old('email', $student->email) }}" />
                                    @error('email')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Phone -->
                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700">{{ __('Phone') }}</label>
                                    <input id="phone" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm" type="text" name="phone" value="{{ old('phone', $student->phone) }}" />
                                    @error('phone')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Address -->
                                <div>
                                    <label for="address" class="block text-sm font-medium text-gray-700">{{ __('Address') }}</label>
                                    <textarea id="address" name="address" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm" rows="3">{{ old('address', $student->address) }}</textarea>
                                    @error('address')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Emergency Contact Name -->
                                <div>
                                    <label for="emergency_contact_name" class="block text-sm font-medium text-gray-700">{{ __('Emergency Contact Name') }}</label>
                                    <input id="emergency_contact_name" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm" type="text" name="emergency_contact_name" value="{{ old('emergency_contact_name', $student->emergency_contact_name) }}" />
                                    @error('emergency_contact_name')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Emergency Contact Phone -->
                                <div>
                                    <label for="emergency_contact_phone" class="block text-sm font-medium text-gray-700">{{ __('Emergency Contact Phone') }}</label>
                                    <input id="emergency_contact_phone" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm" type="text" name="emergency_contact_phone" value="{{ old('emergency_contact_phone', $student->emergency_contact_phone) }}" />
                                    @error('emergency_contact_phone')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Profile Photo -->
                        <div class="mt-6">
                            <label for="profile_photo" class="block text-sm font-medium text-gray-700">{{ __('Profile Photo') }}</label>
                            
                            @if($student->profile_photo)
                                <div class="mt-2 mb-4">
                                    <p class="text-sm text-gray-500 mb-2">Current Photo:</p>
                                    <img src="{{ asset('storage/' . $student->profile_photo) }}" alt="{{ $student->full_name }}" class="h-20 w-20 object-cover rounded-full">
                                </div>
                            @endif
                            
                            <input id="profile_photo" name="profile_photo" type="file" class="block mt-1 w-full text-sm text-gray-500
                                file:mr-4 file:py-2 file:px-4
                                file:rounded-md file:border-0
                                file:text-sm file:font-semibold
                                file:bg-primary-50 file:text-primary-700
                                hover:file:bg-primary-100
                            "/>
                            <p class="mt-1 text-sm text-gray-500">Upload a new photo (optional)</p>
                            @error('profile_photo')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-end mt-8">
                            <x-button type="submit" icon="fas fa-save">
                                {{ __('Update Student') }}
                            </x-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
