@extends('components.student-app-layout')

@section('header')
    <div class="relative overflow-hidden bg-gradient-to-r from-blue-600 to-blue-800 text-white rounded-2xl p-8 mb-6 shadow-lg">
        <div class="relative z-10">
            <h2 class="text-3xl font-bold mb-2">Welcome back, {{ $student->full_name }}!</h2>
            <p class="text-blue-100 flex items-center">
                <i class="fas fa-graduation-cap mr-2"></i>
                {{ $student->programme->name }} • Level {{ $student->current_level }}
            </p>
        </div>
        <div class="absolute right-0 top-0 h-full w-1/2 bg-gradient-to-l from-blue-500 to-transparent opacity-50"></div>
    </div>
@endsection

@section('content')
<div class="max-w-7xl mx-auto space-y-8">
    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- CGPA Card -->
        <div class="bg-gradient-to-br from-purple-500 to-purple-700 rounded-2xl p-6 text-white shadow-lg transform transition-all duration-200 hover:scale-[1.02]">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-white/10 rounded-xl">
                    <i class="fas fa-chart-line text-2xl"></i>
                </div>
                @if($cgpaChange !== 0)
                    <span class="px-3 py-1 rounded-full text-sm {{ $cgpaChange > 0 ? 'bg-green-400/20 text-green-100' : 'bg-red-400/20 text-red-100' }}">
                        <i class="fas fa-{{ $cgpaChange > 0 ? 'arrow-up' : 'arrow-down' }} mr-1"></i>
                        {{ $cgpaChange > 0 ? '+' : '' }}{{ number_format($cgpaChange, 2) }}
                    </span>
                @endif
            </div>
            <h3 class="text-lg font-medium text-purple-100 mb-1">Current CGPA</h3>
            <div class="flex items-baseline">
                <span class="text-4xl font-bold">{{ number_format($cgpa, 2) }}</span>
                <span class="text-purple-200 ml-2">/4.00</span>
            </div>
        </div>

  
        <!-- Current Level Card
        <div class="bg-gradient-to-br from-amber-500 to-amber-700 rounded-2xl p-6 text-white shadow-lg transform transition-all duration-200 hover:scale-[1.02]">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-white/10 rounded-xl">
                    <i class="fas fa-user-graduate text-2xl"></i>
                </div>
                <span class="px-3 py-1 rounded-full text-sm bg-amber-400/20 text-amber-100">
                    {{ now()->format('Y') - $student->admission_year }} Years
                </span>
            </div>
            <h3 class="text-lg font-medium text-amber-100 mb-1">Academic Level</h3>
            <div class="flex items-baseline">
                <span class="text-4xl font-bold">Level {{ $student->current_level }}</span>
            </div>
        </div>
    </div>
 -->
    <!-- Recent Results -->
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100">
        <div class="p-6 border-b border-gray-100">
            <div class="flex justify-between items-center">
                <h3 class="text-xl font-bold text-gray-800">Recent Results</h3>
                <a href="{{ route('student.results') }}" 
                   class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors duration-200">
                    View All Results
                </a>
            </div>
        </div>
        @if($results->isNotEmpty())
            <div class="divide-y divide-gray-100">
                @foreach($results->take(5) as $result)
                    <div class="p-6 hover:bg-gray-50 transition-colors duration-200">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3">
                                    <div class="p-2 bg-blue-100 rounded-lg">
                                        <i class="fas fa-book text-blue-600"></i>
                                    </div>
                                    <div>
                                        <h4 class="text-lg font-semibold text-gray-800">{{ $result->course->code }}</h4>
                                        <p class="text-sm text-gray-500">{{ $result->course->title }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="ml-6 text-right">
                                <div class="inline-flex items-center px-3 py-1 rounded-lg {{ in_array($result->grade, ['A', 'A-']) ? 'bg-green-100 text-green-800' : (in_array($result->grade, ['B+', 'B', 'B-']) ? 'bg-blue-100 text-blue-800' : (in_array($result->grade, ['C+', 'C']) ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800')) }}">
                                    <span class="text-lg font-bold">{{ $result->grade }}</span>
                                </div>
                                <div class="text-sm text-gray-500 mt-1">{{ number_format($result->grade_point, 1) }} GP</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="p-12 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-100 text-blue-600 mb-4">
                    <i class="fas fa-clipboard-list text-2xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No Results Yet</h3>
                <p class="text-gray-500">Your academic results will appear here once they are available.</p>
            </div>
        @endif
    </div>
</div>


    <!-- Password Change Modal -->
    @if(session('show_password_change'))
        <div class="fixed inset-0 bg-gray-900 bg-opacity-50 overflow-y-auto h-full w-full z-50" id="passwordChangeModal">
            <div class="relative top-20 mx-auto p-6 border w-[28rem] shadow-xl rounded-xl bg-white">
                <div class="flex flex-col items-center">
                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-yellow-100 mb-4">
                        <i class="fas fa-key text-2xl text-yellow-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Password Change Required</h3>
                    <div class="text-center mb-6">
                        <p class="text-gray-600">
                            For security reasons, please change your default password before continuing.
                        </p>
                        <p class="text-sm text-gray-500 mt-2 bg-gray-50 p-3 rounded-lg">
                            Your current password is your date of birth (YYYYMMDD)
                        </p>
                    </div>
                    <div class="w-full">
                        <a href="{{ route('password.change') }}" 
                           class="w-full inline-flex items-center justify-center px-6 py-3 bg-primary-600 text-white text-base font-medium rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors duration-200">
                            <i class="fas fa-shield-alt mr-2"></i>
                            Set New Password
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const modal = document.getElementById('passwordChangeModal');
                modal.classList.add('backdrop-blur-sm');
                modal.style.display = 'block';
            });
        </script>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('passwordChangeModal').style.display = 'block';
            });
        </script>
    @endif
@endsection
