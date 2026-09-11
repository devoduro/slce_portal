<?php

namespace App\Mail;

use App\Models\Admission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

/**
 * Sent once, right after an Admission Officer imports an applicant - the applicant's
 * only way to log in and see their bill, so this has to go out at import time rather
 * than waiting for payment confirmation. Sent synchronously (no ShouldQueue): this app
 * has no queue worker running, and a stuck "database" queue entry would silently never
 * reach the applicant.
 */
class ApplicantCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $institutionName;

    public function __construct(public Admission $admission, public string $temporaryPassword)
    {
        $this->institutionName = DB::table('settings')
            ->where('category', 'institution')
            ->where('key', 'institution_name')
            ->value('value') ?: config('app.name');
    }

    public function build(): self
    {
        return $this->subject("Your {$this->institutionName} Admission Portal Login")
            ->view('emails.applicant-credentials');
    }
}
