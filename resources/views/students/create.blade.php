<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ isset($student) ? __('Edit Student') : __('Add Student') }}
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
                    <form method="POST" action="{{ isset($student) ? route('students.update', $student) : route('students.store') }}" enctype="multipart/form-data">
                        @csrf
                        @if(isset($student))
                            @method('PUT')
                        @endif

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Left Column -->
                            <div class="space-y-6">
                                <!-- Index Number -->
                                <div>
                                    <x-input
                                        id="index_number"
                                        name="index_number"
                                        type="text"
                                        label="Index Number"
                                        :value="old('index_number', $student->index_number ?? '')"
                                        required
                                        autofocus
                                    />
                                </div>

                                <!-- Full Name -->
                                <div>
                                    <x-input
                                        id="full_name"
                                        name="full_name"
                                        type="text"
                                        label="Full Name"
                                        :value="old('full_name', $student->full_name ?? '')"
                                        required
                                    />
                                </div>

                                <!-- Email -->
                                <div>
                                    <x-input
                                        id="email"
                                        name="email"
                                        type="email"
                                        label="Email Address"
                                        :value="old('email', $student->email ?? '')"
                                        required
                                    />
                                </div>

                                <!-- Phone -->
                                <div>
                                    <x-input
                                        id="phone"
                                        name="phone"
                                        type="tel"
                                        label="Phone Number"
                                        :value="old('phone', $student->phone ?? '')"
                                    />
                                </div>

                                <!-- Date of Birth -->
                                <div>
                                    <x-input
                                        id="date_of_birth"
                                        name="date_of_birth"
                                        type="date"
                                        label="Date of Birth"
                                        :value="old('date_of_birth', isset($student->date_of_birth) ? $student->date_of_birth->format('Y-m-d') : '')"
                                    />
                                </div>
                            </div>

                            <!-- Right Column -->
                            <div class="space-y-6">
                                <!-- Programme -->
                                <div>
                                    <label for="programme_id" class="block text-sm font-medium text-gray-700">Programme</label>
                                    <select id="programme_id" name="programme_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md" required>
                                        <option value="">Select Programme</option>
                                        @foreach($programmes as $programme)
                                            <option value="{{ $programme->id }}" {{ old('programme_id', $student->programme_id ?? '') == $programme->id ? 'selected' : '' }}>
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
                                    <label for="level" class="block text-sm font-medium text-gray-700">Level</label>
                                    <select id="level" name="level" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md">
                                        <option value="">Select Level</option>
                                        @foreach([100, 200, 300, 400] as $levelOption)
                                            <option value="{{ $levelOption }}" {{ old('level', $student->level ?? '') == $levelOption ? 'selected' : '' }}>
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
                                    <label for="class_group_id" class="block text-sm font-medium text-gray-700">Class</label>
                                    <select id="class_group_id" name="class_group_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md">
                                        <option value="">Select Class (optional)</option>
                                        @foreach($classGroups as $classGroup)
                                            <option value="{{ $classGroup->id }}" {{ old('class_group_id', $student->class_group_id ?? '') == $classGroup->id ? 'selected' : '' }}>
                                                {{ $classGroup->name }} &mdash; {{ $classGroup->programme->name ?? '' }}, Level {{ $classGroup->level }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('class_group_id')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Gender -->
                                <div>
                                    <label for="gender" class="block text-sm font-medium text-gray-700">Gender</label>
                                    <select id="gender" name="gender" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md" required>
                                        <option value="">Select Gender</option>
                                        <option value="Male" {{ old('gender', $student->gender ?? '') == 'Male' ? 'selected' : '' }}>Male</option>
                                        <option value="Female" {{ old('gender', $student->gender ?? '') == 'Female' ? 'selected' : '' }}>Female</option>
                                        <option value="Other" {{ old('gender', $student->gender ?? '') == 'Other' ? 'selected' : '' }}>Other</option>
                                    </select>
                                    @error('gender')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Emergency Contact Name -->
                                <div>
                                    <label for="emergency_contact_name" class="block text-sm font-medium text-gray-700">Emergency Contact Name</label>
                                    <input type="text" name="emergency_contact_name" id="emergency_contact_name" value="{{ old('emergency_contact_name', $student->emergency_contact_name ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                    @error('emergency_contact_name')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <!-- Emergency Contact Phone -->
                                <div>
                                    <label for="emergency_contact_phone" class="block text-sm font-medium text-gray-700">Emergency Contact Phone</label>
                                    <input type="text" name="emergency_contact_phone" id="emergency_contact_phone" value="{{ old('emergency_contact_phone', $student->emergency_contact_phone ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                    @error('emergency_contact_phone')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Address -->
                                <div>
                                    <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
                                    <textarea id="address" name="address" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">{{ old('address', $student->address ?? '') }}</textarea>
                                    @error('address')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Profile Photo -->
                                <div>
                                    <label for="profile_photo" class="block text-sm font-medium text-gray-700">Profile Photo</label>
                                    <div class="mt-1 flex items-center">
                                        <div class="flex-shrink-0 h-12 w-12 rounded-full overflow-hidden bg-gray-100">
                                            @if(isset($student) && $student->profile_photo)
                                                <img src="{{ $student->profile_photo }}" alt="{{ $student->name }}" class="h-full w-full object-cover">
                                            @else
                                                <svg class="h-full w-full text-gray-300" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" />
                                                </svg>
                                            @endif
                                        </div>
                                        <input type="file" id="profile_photo" name="profile_photo" class="ml-5 bg-white py-2 px-3 border border-gray-300 rounded-md shadow-sm text-sm leading-4 font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                    </div>
                                    @error('profile_photo')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>



                        <div class="mt-6 flex justify-end space-x-3">
                            <x-button type="button" variant="secondary" onclick="window.history.back()">
                                {{ __('Cancel') }}
                            </x-button>
                            <x-button type="submit">
                                {{ isset($student) ? __('Update Student') : __('Create Student') }}
                            </x-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
