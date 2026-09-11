<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admission Letter - {{ $admission->full_name }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #1a1a1a;
            font-size: 9.5pt;
            line-height: 1.28;
            position: relative;
        }
        @page { margin: 7mm 15mm 5mm 15mm; }
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 320px;
            margin-left: -160px;
            margin-top: -160px;
            opacity: 0.07;
            z-index: 0;
        }
        .page-content { position: relative; z-index: 10; }

        /* ---- Letterhead ---- */
        .institution-name {
            text-align: center;
            font-family: Georgia, 'Times New Roman', serif;
            font-weight: bold;
            font-size: 14pt;
            color: #3b3b7a;
            letter-spacing: 0.3px;
            margin: 0 0 4px;
        }
        .year-band {
            background-color: #1a7a2e;
            color: #ffffff;
            padding: 5px 14px;
            margin-bottom: 4px;
        }
        .year-band table { width: 100%; border-collapse: collapse; }
        .year-band .crest {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background-color: #ffffff;
            text-align: center;
            vertical-align: middle;
        }
        .year-band .crest img { width: 44px; height: 44px; border-radius: 50%; margin: 4px; }
        .year-band .year-text {
            text-align: center;
            font-size: 15pt;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        .year-band .letter-text {
            text-align: center;
            font-size: 10pt;
            font-weight: bold;
            letter-spacing: 1px;
        }
        .contact-row {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
            font-weight: bold;
            margin-bottom: 3px;
        }
        .contact-row td { padding: 0; }
        .letterhead-rule { border: none; border-top: 2px solid #1a7a2e; margin: 0 0 8px; }

        /* ---- Body ---- */
        .date-line { text-align: right; margin-bottom: 8px; }
        .subject {
            text-align: center;
            font-weight: bold;
            text-decoration: underline;
            color: #1a7a2e;
            font-size: 10pt;
            margin: 6px 0 8px;
        }
        ol.clauses { margin: 0; padding-left: 16px; }
        ol.clauses li { margin-bottom: 4px; text-align: justify; }
        .signature-block { margin-top: 12px; }
        .signature-img { max-height: 40px; }
        .footer-note { margin-top: 6px; font-size: 7pt; color: #666; text-align: center; }
        .motto-band {
            background-color: #1a7a2e;
            color: #ffffff;
            text-align: center;
            font-weight: bold;
            font-size: 10pt;
            letter-spacing: 1px;
            padding: 4px 0;
            margin-top: 6px;
        }
        .photo-box {
            float: right;
            width: 85px;
            height: 100px;
            border: 1px solid #999;
            margin: 0 0 8px 12px;
            text-align: center;
        }
        .photo-box img { max-width: 81px; max-height: 96px; object-fit: cover; }
    </style>
</head>
<body>
    @php
        $logoFile = !empty($settings['institution_logo']) && file_exists(public_path('storage/' . $settings['institution_logo']))
            ? public_path('storage/' . $settings['institution_logo'])
            : (file_exists(public_path('images/logos/institution_logo.png')) ? public_path('images/logos/institution_logo.png') : null);
        $logoBase64 = $logoFile ? 'data:' . mime_content_type($logoFile) . ';base64,' . base64_encode(file_get_contents($logoFile)) : null;

        $signatureBase64 = null;
        if ($isFinal && !empty($settings['principal_signature'])) {
            $signatureFile = public_path('storage/' . $settings['principal_signature']);
            if (file_exists($signatureFile)) {
                $signatureBase64 = 'data:' . mime_content_type($signatureFile) . ';base64,' . base64_encode(file_get_contents($signatureFile));
            }
        }

        $institutionName = $settings['institution_name'] ?? 'St. Louis College of Education';
    @endphp

    @if($logoBase64)
        <img src="{{ $logoBase64 }}" alt="" class="watermark">
    @endif

    <div class="page-content">
    <div class="institution-name">{{ $institutionName }}, {{ $settings['institution_town'] ?? 'KUMASI' }}</div>

    <div class="year-band">
        <table>
            <tr>
                <td class="crest" rowspan="2">
                    @if($logoBase64)
                        <img src="{{ $logoBase64 }}" alt="Crest">
                    @endif
                </td>
                <td class="year-text">{{ $admission->academicYear->name ?? '' }} ACADEMIC YEAR</td>
            </tr>
            <tr>
                <td class="letter-text">{{ $isFinal ? 'ADMISSION LETTER' : 'PROVISIONAL ADMISSION LETTER' }}</td>
            </tr>
        </table>
    </div>

    <table class="contact-row">
        <tr>
            <td width="55%">P. O. BOX 3041, {{ $settings['institution_town'] ?? 'KUMASI, GHANA' }}</td>
            <td>{{ $settings['institution_phone'] ?? '' }}</td>
        </tr>
        <tr>
            <td>{{ $settings['institution_email'] ?? '' }}</td>
            <td>{{ preg_replace('#^https?://#', '', $settings['institution_website'] ?? '') }}</td>
        </tr>
    </table>
    <hr class="letterhead-rule">

    <div class="date-line">{{ $admission->created_at->format('F j, Y') }}</div>

    @if($photoDataUri)
        <div class="photo-box"><img src="{{ $photoDataUri }}" alt="Passport Photo"></div>
    @endif

    <p>Dear {{ $admission->title ? $admission->title . ' ' : '' }}{{ strtoupper($admission->full_name) }},</p>

    <div class="subject">
        OFFER OF ADMISSION FOR THE {{ $admission->academicYear->name ?? '' }} ACADEMIC YEAR<br>
        {{ strtoupper($admission->programme->name ?? '') }} PROGRAMME
    </div>

    <div class="body-text">
        {!! $bodyHtml !!}
    </div>

    <div class="signature-block">
        @if($signatureBase64)
            <img src="{{ $signatureBase64 }}" alt="Signature" class="signature-img"><br>
        @endif
        <strong>{{ $settings['principal_name'] ?? '________________________' }}</strong><br>
        (PRINCIPAL)
    </div>

    <div class="footer-note">
        This letter was generated on {{ now()->format('F j, Y g:i A') }} &middot; Applicant Number: {{ $admission->applicant_number }} &middot; Payment status: {{ ucfirst(str_replace('_', ' ', $admission->payment_status)) }}
    </div>

    <div class="motto-band">UT SINT UNUM, {{ strtoupper($settings['institution_slogan'] ?? 'DIEU LE VEUT') }}!</div>
    </div>
</body>
</html>
