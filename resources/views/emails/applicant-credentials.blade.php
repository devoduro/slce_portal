<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Your Admission Portal Login</title>
</head>
<body style="margin:0; padding:0; background-color:#f3f4f6; font-family:Arial, Helvetica, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6; padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="480" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:8px; overflow:hidden;">
                    <tr>
                        <td style="background-color:#0369a1; padding:20px 24px;">
                            <p style="margin:0; color:#ffffff; font-size:18px; font-weight:bold;">{{ $institutionName }}</p>
                            <p style="margin:4px 0 0; color:#e0f2fe; font-size:13px;">Admission Portal</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px;">
                            <p style="margin:0 0 16px; color:#111827; font-size:15px;">Dear {{ $admission->title ? $admission->title . ' ' : '' }}{{ $admission->full_name }},</p>

                            <p style="margin:0 0 16px; color:#374151; font-size:14px; line-height:1.6;">
                                Your admission record has been created. Use the details below to log in to the applicant portal, view your admission bill, make payment, and complete the remaining steps of your admission.
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f9fafb; border:1px solid #e5e7eb; border-radius:6px; margin-bottom:20px;">
                                <tr>
                                    <td style="padding:14px 18px;">
                                        <p style="margin:0 0 6px; font-size:12px; color:#6b7280; text-transform:uppercase; letter-spacing:.05em;">Applicant Number</p>
                                        <p style="margin:0 0 14px; font-size:16px; color:#111827; font-weight:bold; font-family:'Courier New', monospace;">{{ $admission->applicant_number }}</p>

                                        <p style="margin:0 0 6px; font-size:12px; color:#6b7280; text-transform:uppercase; letter-spacing:.05em;">Temporary Password</p>
                                        <p style="margin:0; font-size:16px; color:#111827; font-weight:bold; font-family:'Courier New', monospace;">{{ $temporaryPassword }}</p>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 20px; color:#374151; font-size:14px; line-height:1.6;">
                                You will be asked to set a new password the first time you log in. Keep this email private - do not share your password with anyone.
                            </p>

                            <table role="presentation" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="background-color:#0369a1; border-radius:6px;">
                                        <a href="{{ route('applicant.login') }}" style="display:inline-block; padding:10px 20px; color:#ffffff; font-size:14px; font-weight:bold; text-decoration:none;">
                                            Log In to the Applicant Portal
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 24px; background-color:#f9fafb; border-top:1px solid #e5e7eb;">
                            <p style="margin:0; color:#9ca3af; font-size:11px; text-align:center;">
                                This is an automated message from {{ $institutionName }}. If you did not expect this email, please contact the Admissions Office.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
