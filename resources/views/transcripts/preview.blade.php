<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Transcript Preview') }}
            </h2>
            <div class="flex space-x-2">
                <x-button href="{{ route('transcripts.create') }}" variant="secondary" icon="fas fa-arrow-left">
                    {{ __('Back to Options') }}
                </x-button>
                <x-button href="{{ route('transcripts.generate', $student->id) }}" icon="fas fa-file-download">
                    {{ __('Download Transcript') }}
                </x-button>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Transcript Preview -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <!-- Transcript Document -->
                    <div class="border border-gray-300 rounded-lg overflow-hidden relative">
                        <style>
                            /* Basic Reset */
                            * {
                                margin: 0;
                                padding: 0;
                                box-sizing: border-box;
                            }
                            
                            body {
                                font-family: Arial, sans-serif;
                                line-height: 1.6;
                                color: #333;
                                background: #f5f5f5;
                            }
                            
                            .transcript-container {
                                max-width: 1000px;
                                margin: 0 auto;
                                background: white;
                                box-shadow: 0 0 10px rgba(0,0,0,0.1);
                                padding: 20px;
                                position: relative;
                            }
                            
                            /* Watermark */
                            .watermark {
                                position: absolute;
                                top: 50%;
                                left: 50%;
                                transform: translate(-50%, -50%) rotate(-45deg);
                                font-size: 100px;
                                color: rgba(200, 200, 200, 0.2);
                                font-weight: bold;
                                z-index: 0;
                                white-space: nowrap;
                            }
                            
                            /* Header */
                            .header {
                                text-align: center;
                                margin-bottom: 20px;
                                position: relative;
                                z-index: 1;
                            }
                            
                            .header h1 {
                                font-size: 24px;
                                margin: 10px 0 5px;
                                text-transform: uppercase;
                            }
                            
                            .header h2 {
                                font-size: 20px;
                                margin-bottom: 10px;
                                color: #444;
                            }
                            
                            .header p {
                                font-size: 12px;
                                margin-bottom: 5px;
                            }
                            
                            /* Student Info */
                            .student-info {
                                width: 100%;
                                border-collapse: collapse;
                                margin-bottom: 20px;
                                position: relative;
                                z-index: 1;
                            }
                            
                            .student-info td {
                                padding: 5px;
                                border: 1px solid #ddd;
                            }
                            
                            .student-info .label {
                                font-weight: bold;
                                width: 20%;
                                background-color: #f5f5f5;
                            }
                            
                            /* Semester Headers */
                            .semester-header {
                                background: #007bff;
                                color: white;
                                padding: 8px 10px;
                                font-weight: bold;
                                margin: 15px 0 5px;
                                position: relative;
                                z-index: 1;
                            }
                            
                            /* Results Table */
                            .results-table {
                                width: 100%;
                                border-collapse: collapse;
                                margin-bottom: 15px;
                                position: relative;
                                z-index: 1;
                            }
                            
                            .results-table th, 
                            .results-table td {
                                padding: 8px;
                                text-align: left;
                                border: 1px solid #ddd;
                                font-size: 10px;
                            }
                            
                            .results-table th {
                                background: #f0f0f0;
                                font-weight: bold;
                            }
                            
                            /* Summary Section */
                            .summary-section {
                                margin: 20px 0;
                                position: relative;
                                z-index: 1;
                            }
                            
                            .summary-table {
                                width: 100%;
                                border-collapse: collapse;
                            }
                            
                            .summary-table td {
                                padding: 8px;
                                border: 1px solid #ddd;
                            }
                            
                            /* Grading System */
                            .grading-system {
                                margin: 20px 0;
                                position: relative;
                                z-index: 1;
                            }
                            
                            .grading-system h3 {
                                font-size: 10px;
                                margin-bottom: 10px;
                                text-align: center;
                            }
                            
                            .grading-table {
                                width: 100%;
                                border-collapse: collapse;
                                font-size: 10px;
                            }
                            
                            .grading-table th, 
                            .grading-table td {
                                padding: 5px;
                                text-align: center;
                                border: 1px solid #ddd;
                            }
                            
                            .grading-table th {
                                background: #f0f0f0;
                            }
                            
                            /* Footer */
                            .footer {
                                margin-top: 30px;
                                border-top: 1px solid #ddd;
                                padding-top: 15px;
                                position: relative;
                                z-index: 1;
                            }
                            
                            .signature-section {
                                width: 200px;
                                margin: 20px 0;
                                text-align: center;
                            }
                            
                            .signature-line {
                                border-bottom: 1px solid #000;
                                margin-bottom: 5px;
                            }
                            
                            .verification {
                                font-size: 11px;
                                font-style: italic;
                                margin-top: 20px;
                            }
                            .summary {
                                margin-top: 20px;
                            }
                            .summary table {
                                width: 50%;
                                border-collapse: collapse;
                            }
                            .summary table th, .summary table td {
                                border: 1px solid #ddd;
                                padding: 8px;
                                text-align: left;
                            }
                            .summary table th {
                                background-color: #f2f2f2;
                                font-weight: bold;
                            }
                            .classification {
                                margin-top: 20px;
                                border: 1px solid #000;
                                padding: 10px;
                                text-align: center;
                            }
                            .grade-key {
                                margin-top: 20px;
                                font-size: 12px;
                            }
                            .grade-key table {
                                width: 100%;
                                border-collapse: collapse;
                            }
                            .grade-key table th, .grade-key table td {
                                border: 1px solid #ddd;
                                padding: 5px;
                                text-align: left;
                            }
                            .signature {
                                margin-top: 40px;
                                text-align: center;
                            }
                            .signature-line {
                                width: 200px;
                                border-top: 1px solid #000;
                                margin: 0 auto;
                                padding-top: 5px;
                            }
                            .footer {
                                margin-top: 30px;
                                text-align: center;
                                font-size: 12px;
                            }
                        </style>
                        <div class="transcript-container">
                            <div class="watermark">OFFICIAL TRANSCRIPT</div>
                            
                            <!-- Transcript Header -->
                            <div class="header">
                                @php
                                    // Try to display a logo if available
                                    $logoBase64 = '';
                                    $logoFile = public_path('images/logos/institution_logo.png');
                                    
                                    if (file_exists($logoFile)) {
                                        $logoData = file_get_contents($logoFile);
                                        $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
                                    }
                                @endphp
                                
                                @if(!empty($logoBase64))
                                    <img src="{{ $logoBase64 }}" alt="Institution Logo" style="max-width: 150px; max-height: 150px; margin: 0 auto;">
                                @else
                                    <div style="width: 150px; height: 100px; background-color: #007bff; color: white; display: flex; align-items: center; justify-content: center; text-align: center; font-weight: bold; margin: 0 auto;">
                                        INSTITUTION<br>LOGO
                                    </div>
                                @endif
                                <h1>{{ $settings['institution_name'] ?? 'UNIVERSITY OF EDUCATION' }}</h1>
                                <h2>ACADEMIC TRANSCRIPT</h2>
                                <p>
                                    {{ $settings['institution_address'] ?? '' }}
                                    @if(isset($settings['institution_phone']) && !empty($settings['institution_phone']))
                                        | Tel: {{ $settings['institution_phone'] }}
                                    @endif
                                    @if(isset($settings['institution_email']) && !empty($settings['institution_email']))
                                        | Email: {{ $settings['institution_email'] }}
                                    @endif
                                    @if(isset($settings['institution_website']) && !empty($settings['institution_website']))
                                        | {{ $settings['institution_website'] }}
                                    @endif
                                </p>
                                @if(isset($settings['institution_slogan']) && !empty($settings['institution_slogan']))
                                    <p><em>{{ $settings['institution_slogan'] }}</em></p>
                                @endif
                            </div>
                            
                            <!-- Student Information -->
                            <table class="student-info">
                                <tr>
                                    <td class="label">Name:</td>
                                    <td><strong>{{ $student->full_name }}</strong></td>
                                    <td class="label">Index Number:</td>
                                    <td><strong>{{ $student->index_number }}</strong></td>
                                </tr>
                                <tr>
                                    <td class="label">Date of Birth:</td>
                                    <td>{{ $student->date_of_birth ? (is_string($student->date_of_birth) ? $student->date_of_birth : $student->date_of_birth->format('d/m/Y')) : 'N/A' }}</td>
                                    <td class="label">Gender:</td>
                                    <td>{{ $student->gender }}</td>
                                </tr>
                                <tr>
                                    <td class="label">Programme:</td>
                                    <td>{{ $student->programme->name }}</td>
                                    <td class="label">Date of Issue:</td>
                                    <td>{{ now()->format('d/m/Y') }}</td>
                                </tr>
                            </table>
                            
                            <!-- Academic Results -->
                            @foreach($groupedResults as $academicYear)
                                <div class="semester-header">
                                    {{ $academicYear['academic_year']->name }}
                                </div>
                                
                                @foreach($academicYear['semesters'] as $semesterData)
                                    <div class="semester-header" style="padding-left: 20px;">
                                        {{ $semesterData['semester']->name }}
                                    </div>
                                    
                                    <table class="results-table">
                                        <thead>
                                            <tr>
                                                <th>Course Code</th>
                                                <th>Course Title</th>
                                                <th>Credit Hours</th>
                                                <th>Grade</th>
                                                <th>Grade Point</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $totalCreditHours = 0;
                                                $totalGradePoints = 0;
                                            @endphp
                                            
                                            @foreach($semesterData['results'] as $result)
                                                @php
                                                    $creditHours = $result->course->credit_hours;
                                                    $gradePoint = $result->grade_point;
                                                    $totalCreditHours += $creditHours;
                                                    $totalGradePoints += ($gradePoint * $creditHours);
                                                @endphp
                                                <tr>
                                                    <td>{{ $result->course->code }}</td>
                                                    <td>{{ $result->course->title }}</td>
                                                    <td>{{ $creditHours }}</td>
                                                    <td>{{ $result->grade }}{{ $result->is_repeated ? ' (Resit)' : '' }}</td>
                                                    <td>{{ $gradePoint }}</td>
                                                </tr>
                                            @endforeach
                                            
                                            <tr>
                                                <td colspan="2" style="text-align: right;"><strong>Semester GPA:</strong></td>
                                                <td><strong>{{ $totalCreditHours }}</strong></td>
                                                <td colspan="2"><strong>{{ number_format($semesterData['gpa'], 2) }}</strong></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                @endforeach
                            @endforeach
                            
                            <!-- CGPA and Classification -->
                            <div class="summary-section">
                                <table class="summary-table">
                                    <tr>
                                        <td><strong>CGPA:</strong></td>
                                        <td>{{ number_format($cgpa, 2) }}</td>
                                        <td><strong>Classification:</strong></td>
                                        <td>{{ $classification }}</td>
                                    </tr>
                                </table>
                            </div>
                            
                            <!-- Grading System -->
                            <div class="grading-system">
                                <h3>GRADING SYSTEM</h3>
                                <table class="grading-table">
                                    <tr>
                                        <th>Grade</th>
                                        <th>Mark Range</th>
                                        <th>Grade Point</th>
                                       
                                    </tr>
                                    <tr>
                                        <td>A</td>
                                        <td>80-100</td>
                                        <td>4.0</td>
                                       
                                    </tr>
                                    <tr>
                                        <td>B+</td>
                                        <td>75-79</td>
                                        <td>3.5</td>
                                    </tr>
                                    <tr>
                                        <td>B</td>
                                        <td>70-74</td>
                                        <td>3.0</td>
                                    </tr>
                                    <tr>
                                        <td>C+</td>
                                        <td>65-69</td>
                                        <td>2.5</td>
                                    </tr>
                                    <tr>
                                        <td>C</td>
                                        <td>60-64</td>
                                        <td>2.0</td>
                                       
                                    </tr>
                                    <tr>
                                        <td>D+</td>
                                        <td>55-59</td>
                                        <td>1.5</td>
                                    </tr>
                                    <tr>
                                        <td>D</td>
                                        <td>50-54</td>
                                        <td>1.0</td>
                                      
                                    <tr>
                                        <td>E</td>
                                        <td>0-49</td>
                                        <td>0.0</td>
                                
                                    </tr>
                                </table>
                            </div>
                            
                            <!-- Footer -->
                            <div class="footer">
                                <div class="signature-section">
                                    @if(isset($settings['academic_affairs_signature']))
                                        <img src="{{ asset('storage/' . $settings['academic_affairs_signature']) }}" alt="Academic Affairs Signature" style="height: 50px; margin-bottom: 5px;">
                                    @endif
                                    <div class="signature-line"></div>
                                    <p>Academic Affairs Officer</p>
                                </div>
                                
                                <div class="verification">
                                    <p>This transcript is not valid without the official seal of the institution.</p>
                                    <p>Date Issued: {{ now()->format('d/m/Y') }}</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Transcript Footer -->
                        <div class="p-6 border-t border-gray-300 bg-gray-50">
                            <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                                <div>
                                    <p class="text-sm text-gray-600">Generated on: {{ now()->format('F d, Y') }}</p>
                                    <p class="text-sm text-gray-600">Document ID: SLCE-{{ strtoupper(substr(md5($student->id . now()), 0, 8)) }}</p>
                                </div>
                                
                                @if($options['include_signature'] ?? true)
                                <div class="mt-4 md:mt-0 text-center">
                                    <div class="mb-2">
                                        <img src="https://via.placeholder.com/200x50?text=Registrar+Signature" alt="Registrar Signature" class="h-12">
                                    </div>
                                    <p class="text-sm font-medium">Dr. Jane Smith</p>
                                    <p class="text-xs text-gray-500">University Registrar</p>
                                </div>
                                @endif
                            </div>
                            
                            <div class="mt-4 text-xs text-gray-500">
                                <p>This transcript is not valid without the university seal and signature of the registrar.</p>
                                <p>Key to Grades: A (Excellent: 4.0), B (Good: 3.0), C (Satisfactory: 2.0), D (Passing: 1.0), F (Failing: 0.0)</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex flex-wrap gap-3 justify-end">
                <x-button href="{{ route('transcripts.create') }}" variant="secondary" icon="fas fa-arrow-left">
                    {{ __('Back to Options') }}
                </x-button>
                <x-button href="{{ route('transcripts.generate', $student->id) }}" icon="fas fa-file-download">
                    {{ __('Download Transcript') }}
                </x-button>
            </div>
        </div>
    </div>
</x-app-layout>
