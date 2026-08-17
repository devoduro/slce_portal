<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Academic Transcript</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #333;
            font-size: 10pt;
        }
        .container {
            width: 100%;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            padding: 10px 0;
            border-bottom: 2px solid #000;
        }
        .header h1 {
            margin: 0;
            font-size: 12pt;
            font-weight: bold;
            color: #000;
        }
        .header h2 {
            margin: 5px 0;
            font-size: 10pt;
            font-weight: bold;
        }
        .header p {
            margin: 5px 0;
            font-size: 10pt;
        }
        .logo {
            width: 80px;
            height: auto;
            margin-bottom: 5px;
        }
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 60pt;
            color: rgba(200, 200, 200, 0.2);
            z-index: -1;
        }
        .student-info {
            width: 100%;
            margin: 15px 0;
            border-collapse: collapse;
        }
        .student-info td {
            padding: 5px;
            vertical-align: top;
        }
        .student-info .label {
            font-weight: bold;
            width: 120px;
        }
        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .results-table th, .results-table td {
            border: 1px solid #ddd;
            padding: 5px;
            text-align: left;
            font-size: 8pt;
        }
        .results-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .semester-header {
            background-color: #e6e6e6;
            font-weight: bold;
            padding: 5px;
            margin: 10px 0 5px 0;
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
            padding: 5px;
            text-align: left;
        }
        .summary table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9pt;
        }
        .page-number {
            position: fixed;
            bottom: 10px;
            right: 20px;
            font-size: 9pt;
            color: #666;
        }
        .page-number:after {
            counter-increment: page;
            content: "Page " counter(page) " of " counter(pages);
        }
        @page {
            margin: 20mm 15mm 25mm 15mm;
            @bottom-right {
                content: "Page " counter(page) " of " counter(pages);
                font-size: 9pt;
                color: #666;
            }
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
        .classification {
            margin-top: 20px;
            
            padding: 10px;
            text-align: center;
        }
        .classification h3 {
            margin: 0;
            font-size: 8pt;
        }
        .page-break {
            page-break-after: always;
        }
        .grade-key {
            margin-top: 20px;
            font-size: 8pt;
        }
        .grade-key table {
            width: 100%;
            border-collapse: collapse;
        }
        .grade-key table th, .grade-key table td {
            border: 1px solid #ddd;
            padding: 3px;
            text-align: left;
        }
        .grade-key table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="watermark">OFFICIAL TRANSCRIPT</div>
        
        <div class="header">
            @php
                // First check if we have a logo path in settings
                $logoPath = null;
                if(isset($settings['institution_logo']) && !empty($settings['institution_logo'])) {
                    $settingsLogoPath = public_path($settings['institution_logo']);
                    if(file_exists($settingsLogoPath)) {
                        $logoPath = $settingsLogoPath;
                    }
                }
                
                // If no logo found in settings, try these fallbacks in order
                if(!$logoPath) {
                    $possibleLogos = [
                        public_path('images/institution_logo.png'),
                        public_path('images/logos/institution_logo.png'),
                        public_path('images/logos/logo.png')
                    ];
                    
                    foreach($possibleLogos as $possibleLogo) {
                        if(file_exists($possibleLogo)) {
                            $logoPath = $possibleLogo;
                            break;
                        }
                    }
                }
            @endphp
            
            @php
                // Get the logo file and encode it to base64 for reliable PDF display
                $logoFile = public_path('images/logos/institution_logo.png');
                $logoBase64 = '';
                
                if (file_exists($logoFile)) {
                    $logoData = file_get_contents($logoFile);
                    $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
                }
            @endphp
            
            @if(!empty($logoBase64))
                <img src="{{ $logoBase64 }}" alt="Institution Logo" class="logo" style="max-width: 150px; max-height: 150px;">
            @else
                <div style="width: 150px; height: 100px; background-color: #007bff; color: white; display: flex; align-items: center; justify-content: center; text-align: center; font-weight: bold;">
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
        
        <table class="student-info">
            <tr>
                <td class="label">Name:</td>
                <td><strong>{{ $student->full_name }}</strong></td>
                <td class="label">Index Number:</td>
                <td><strong>{{ $student->index_number }}</strong></td>
            </tr>
            <tr>
                <td class="label">Date of Birth:</td>
                <td>{{ \Carbon\Carbon::parse($student->date_of_birth)->format('d/m/Y') }}</td>
                <td class="label">Gender:</td>
                <td>{{ $student->gender }}</td>
            </tr>
            <tr>
                <td class="label">Programme:</td>
                <td>{{ $student->programme->name }}</td>
                <td class="label">Date of Issue:</td>
                <td>{{ \Carbon\Carbon::now()->format('d/m/Y') }}</td>
            </tr>
        </table>
        
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
                                if ($result->counts_for_gpa) {
                                    $totalCreditHours += $creditHours;
                                    $totalGradePoints += ($gradePoint * $creditHours);
                                }
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
        
        <div class="summary">
            <table>
                <tr>
                    <th>Total Credit Hours</th>
                    <td>{{ $student->results->filter(fn($result) => $result->counts_for_gpa)->sum(function($result) { return $result->course->credit_hours; }) }}</td>
                </tr>
                <tr>
                    <th>Cumulative GPA (CGPA)</th>
                    <td>{{ number_format($cgpa, 2) }}</td>
                </tr>
            </table>
        </div>
        
        @if($classification)
            <div class="classification">
                <h3>CLASSIFICATION: {{ strtoupper($classification) }}</h3>
            </div>
        @endif
            <div class="signature">
            @php
                $signaturePath = null;
                if (isset($settings['academic_affairs_signature'])) {
                    $signaturePath = storage_path('app/public/' . $settings['academic_affairs_signature']);
                    $signatureBase64 = '';
                    if (file_exists($signaturePath)) {
                        $signatureBase64 = base64_encode(file_get_contents($signaturePath));
                    }
                }
            @endphp

            @if(isset($signatureBase64) && $signatureBase64)
                <img src="data:image/png;base64,{{ $signatureBase64 }}" alt="Academic Affairs Signature" style="height: 80px; margin-bottom: 5px;">
            @endif
          
        </div>
        
        <div class="footer">
            <p>This transcript is not valid without the College seal and signature of the Academic Affairs Officer.</p>
            <p>Any alteration renders this transcript invalid.</p>
        </div>
        <div class="grade-key">
            <h4>Grading System</h4>
            <table>
                <tr>
                    <th>Grade</th>
                    <th>Score Range</th>
                    <th>Grade Point</th>
                    <th>Description</th>
                </tr>
                <tr><td>A</td><td>80-100</td><td>4.0</td><td>Excellent</td></tr>
                <tr><td>B+</td><td>75-79</td><td>3.5</td><td>Very Good</td></tr>
                <tr><td>B</td><td>70-74</td><td>3.0</td><td>Good</td></tr>
                <tr><td>C+</td><td>65-69</td><td>2.5</td><td>Fairly Good</td></tr>
                <tr><td>C</td><td>60-64</td><td>2.0</td><td>Average</td></tr>
                <tr><td>D+</td><td>55-59</td><td>1.5</td><td>Below Average</td></tr>
                <tr><td>D</td><td>50-54</td><td>1.0</td><td>Pass</td></tr>
                <tr><td>E</td><td>0-49</td><td>0.0</td><td>Fail</td></tr>
            </table>
        </div>
        
    
        
        <script type="text/php">
            if (isset($pdf)) {
                $text = "Page {PAGE_NUM} of {PAGE_COUNT}";
                $size = 9;
                $font = $fontMetrics->getFont("Arial");
                $width = $fontMetrics->get_text_width($text, $font, $size) / 2;
                $x = ($pdf->get_width() - $width) / 2;
                $y = $pdf->get_height() - 30;
                $pdf->page_text($x, $y, $text, $font, $size, array(0.4, 0.4, 0.4));
            }
        </script>
    </div>
</body>
</html>
