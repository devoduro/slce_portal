<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Acceptance Form - {{ $admission->full_name }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; color: #1a1a1a; font-size: 10pt; line-height: 1.5; }
        @page { margin: 15mm; }
        .title { text-align: center; font-weight: bold; font-size: 12pt; margin: 0 0 4px; }
        .subtitle { text-align: center; font-weight: bold; font-size: 10pt; margin: 0 0 4px; }
        .instruction { text-align: center; font-size: 9pt; margin: 0 0 4px; }
        .photo-note { text-align: center; font-weight: bold; font-size: 9pt; margin: 0 0 20px; }
        .name-cols { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        .name-cols td { text-align: center; font-weight: bold; font-size: 9pt; width: 33.33%; }
        .field { margin-bottom: 16px; }
        .field .label { font-weight: bold; }
        .dots { border-bottom: 1px dotted #444; display: inline-block; min-width: 60%; }
        .field-line { border-bottom: 1px dotted #444; height: 16px; }
        .checkbox { display: inline-block; width: 12px; height: 12px; border: 1px solid #1a1a1a; margin-right: 6px; vertical-align: middle; }
        .signature-area { margin-top: 40px; }
        .notice { margin-top: 60px; font-size: 9pt; font-weight: bold; }
        .motto-band {
            background-color: #1a7a2e;
            color: #ffffff;
            text-align: center;
            font-weight: bold;
            font-size: 11pt;
            letter-spacing: 1px;
            padding: 5px 0;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="title">{{ strtoupper($settings['institution_name'] ?? 'St. Louis College of Education') }}, {{ $settings['institution_town'] ?? 'KUMASI' }}</div>
    <div class="subtitle">ACCEPTANCE FORM &ndash; OFFER OF ADMISSION</div>
    <div class="subtitle">{{ $admission->academicYear->name ?? '' }}</div>
    <div class="instruction">(This form should be filled and returned to the Secretary, {{ $settings['institution_name'] ?? 'St. Louis College of Education' }}.)</div>
    <div class="photo-note">Please affix a passport-size photograph of yourself at the top-left corner of this form</div>

    <table class="name-cols">
        <tr><td>SURNAME</td><td>FIRST NAME</td><td>OTHERNAME(S)</td></tr>
    </table>

    <div class="field">
        <span class="label">Candidate's Name:</span> <span class="dots">{{ strtoupper($admission->full_name) }}</span>
    </div>

    <div class="field">
        <span class="label">Residence:</span> <span class="dots">&nbsp;</span>
    </div>

    <div class="field">
        <span class="label">Phone Number:</span> <span class="dots">{{ $admission->phone }}</span>
    </div>

    <div class="field">
        <span class="label">Hall of Residence:</span> <span class="dots">{{ $admission->hall }}</span>
    </div>

    <div class="field">
        <span class="label">Date:</span> <span class="dots">&nbsp;</span>
    </div>

    <p>
        To:<br>
        <strong>THE SECRETARY</strong><br>
        <strong>{{ strtoupper($settings['institution_name'] ?? 'ST. LOUIS COLLEGE OF EDUCATION') }}</strong><br>
        <strong>{{ strtoupper($settings['institution_town'] ?? 'KUMASI') }}, GHANA.</strong>
    </p>

    <p>Dear Sir/Madam,</p>

    <p>This is to acknowledge receipt of your letter dated <span class="dots">{{ $admission->created_at->format('F j, Y') }}</span> offering me a place on the {{ $admission->programme->name ?? '' }} programme.</p>

    <p><span class="checkbox"></span> I have great pleasure in accepting the offer</p>
    <p><span class="checkbox"></span> I regret I cannot accept the offer owing to the following reasons: <span class="dots">&nbsp;</span></p>

    <p>I shall report for admission on <span class="dots">&nbsp;</span></p>

    <div class="signature-area">
        <p>Yours faithfully,</p>
        <p style="margin-top:30px;">................................................<br>(Signature of Applicant)</p>
    </div>

    <div class="notice">
        IMPORTANT NOTICE: Any applicant who fails to report to school two weeks after the re-opening date without any notice to the administration shall be deemed to have withdrawn their admission.
    </div>

    <div class="motto-band">UT SINT UNUM, {{ strtoupper($settings['institution_slogan'] ?? 'DIEU LE VEUT') }}!</div>
</body>
</html>
