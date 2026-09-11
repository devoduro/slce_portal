<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionLetterTemplate extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'academic_year_id',
        'provisional_body',
        'final_body',
        'updated_by',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Placeholder tokens an admin can use in either body - shown as a cheat-sheet on the
     * edit screen and substituted by render() at letter-generation time.
     */
    public static function placeholders(): array
    {
        return [
            '{{full_name}}' => "Applicant's full name",
            '{{title}}' => 'Applicant title (Mr/Miss/Mrs), if on file',
            '{{applicant_number}}' => "Applicant's login number",
            '{{programme}}' => 'Programme name',
            '{{academic_year}}' => 'Academic year (e.g. 2026/2027)',
            '{{level}}' => 'Level (e.g. 100)',
            '{{institution_name}}' => 'Institution name',
            '{{total_billed}}' => "This applicant's total billed amount, e.g. 4,194.42 (no GH¢ prefix - add it in the text)",
            '{{amount_paid}}' => 'Total confirmed payments so far',
            '{{outstanding_balance}}' => 'Total billed minus total paid',
            '{{bill_items}}' => 'A plain-language list of the billed line items, e.g. "School Fees (GH¢3,000.00), Examination Fee (GH¢1,000.00)"',
            '{{hall_clause}}' => 'A full numbered sentence naming the applicant\'s assigned hall of residence, or nothing at all if no hall has been assigned yet - place it on its own line inside the <ol>, not inside an <li> of your own',
        ];
    }

    /**
     * The body text used when an academic year has no custom template yet - the
     * institution's real numbered-clause offer letter, word-for-word where the wording
     * doesn't depend on a specific year's figures. Two deliberate departures from the
     * real letter: (1) the fee clauses reference {{total_billed}}/{{outstanding_balance}}/
     * {{bill_items}} instead of one year's hardcoded amounts, since those must always
     * match what was actually billed to *this* applicant, not the sample year's GH¢
     * figures; (2) payment still happens externally (e.g. GCB Eagle Pay) but is now
     * *recorded and confirmed* through this system's own applicant portal, so the
     * portal is referenced alongside rather than instead of the real payment channel.
     * Kept tight enough to fit one A4 page with the letterhead and signature - an admin
     * adding clauses back in the edit screen should watch that.
     */
    public static function defaultBody(bool $isFinal): string
    {
        if ($isFinal) {
            return '<ol class="clauses">'
                . '<li>We refer to your application for admission to <strong>{{institution_name}}</strong> and confirm your admission to pursue the <strong>{{programme}}</strong> programme for the <strong>{{academic_year}}</strong> academic year. All admission requirements, including the required payment of <strong>GH&cent;{{total_billed}}</strong>, have been satisfied.</li>'
                . '<li>This admission is for the <strong>{{academic_year}} academic year only (cannot be deferred)</strong>.</li>'
                . '<li>The College reserves the right to revoke this admission should it later be found that you do not, in fact, possess the qualifications on the basis of which you were admitted. You will also be held personally liable for any false statement or omission made on your application form.</li>'
                . '<li>You will be on probation for the full duration of your programme. Satisfactory academic work and good conduct are required for your continued stay, and you must adhere to all College policies, rules and regulations, including the Students\' Handbook.</li>'
                . '<li>All fresh students are matriculated soon after re-opening. <u><strong>Your admission will be withdrawn if you fail to take part in the matriculation.</strong></u></li>'
                . '<li>Kindly note that you are required to register for your courses at the beginning of each semester for the entire duration of your programme.</li>'
                . '{{hall_clause}}'
                . '<li>Log in to the applicant portal using your applicant number (<strong>{{applicant_number}}</strong>) to view your admission documents and payment history.</li>'
                . '</ol>';
        }

        return '<ol class="clauses">'
            . '<li>We refer to your application for admission to <strong>{{institution_name}}</strong> and inform you of the offer of admission to pursue the <strong>{{programme}}</strong> programme starting from the <strong>{{academic_year}}</strong> academic year.</li>'
            . '<li>This offer of admission is for the {{academic_year}} academic year <strong>only (cannot be deferred)</strong> and is based on available information that you have satisfied the entry requirements for the programme stated above. Your place will be offered to another candidate on the waiting list if you do not indicate your acceptance.</li>'
            . '<li>You are to note that the College reserves the right to revoke this admission offer should we discover later that you do not, in fact, possess the qualifications by virtue of which you have been offered admission to pursue this programme. You will also be held personally liable for any false statement or omission made on your application form.</li>'
            . '<li>You will be on probation for the full duration of your programme. Satisfactory academic work and good conduct are required for your continued stay on the programme. You will be required to adhere to all College policies, rules and regulations, including those contained in the Students\' Handbook, copies of which will be made available to you during matriculation.</li>'
            . '<li><em>You will be required to undergo compulsory medical examination soon after reopening to ascertain if you are medically fit to pursue the programme of study.</em></li>'
            . '<li>It should be noted that the College does not award scholarships to students.</li>'
            . '<li>Be informed that the College re-opens tentatively on <u><strong>the date to be communicated</strong></u>, and you are expected to report on that date. Any change in the re-opening date shall be communicated to you via SMS.</li>'
            . '{{hall_clause}}'
            . '<li><em>In order to confirm the acceptance of this offer, you must make full payment of the outstanding balance of <strong>GH&cent;{{outstanding_balance}}</strong> via <u><strong>the applicant portal</strong></u>. Your total fees for the {{academic_year}} academic year, as billed to you, sum up to <strong>GH&cent;{{total_billed}}</strong>, comprising: <strong>{{bill_items}}</strong>, which must be paid before the deadline.</em></li>'
            . '<li>Log in to the applicant portal using your applicant number (<strong>{{applicant_number}}</strong>) as your reference, <u><strong>make the required payment before the deadline shown on your bill there</strong></u>, and enter/upload proof of payment for Accounts to verify and confirm.</li>'
            . '<li>After payment is confirmed, complete and confirm your personal information on the portal. On reporting, immediately submit A) this admission letter, B) the Acceptance Form duly signed by you and endorsed by your parent/guardian, and C) a copy of your results slip(s) or certificate, Birth Certificate and one passport-size photograph to the Registrar. <strong>You shall forfeit your admission if you fail to submit these documents by the deadline given.</strong></li>'
            . '<li>All fresh students will be matriculated soon after re-opening. You should note carefully that your admission will be withdrawn if you fail to take part in the matriculation.</li>'
            . '<li>Kindly note that you are required to register for your courses at the beginning of each semester for the entire duration of your programme.</li>'
            . '<li>Register for E-Zwich, Ghana Card and SSNIT Number (if you do not have) before reporting to College. Ensure that the name on your <strong>WASSCE Certificate</strong> is the same on your <strong>E-Zwich and SSNIT cards</strong>.</li>'
            . '</ol>';
    }

    /**
     * A plain-language rendering of the admission's actual billed line items, e.g.
     * "School Fees (GH¢3,000.00), Examination Fee (GH¢1,000.00)" - fills {{bill_items}}
     * so the letter always names what was really billed, not a fixed sample list.
     */
    public static function billItemsSummary(Admission $admission): string
    {
        $items = $admission->billItems;

        if ($items->isEmpty()) {
            return 'no items billed yet';
        }

        // GH¢ (cent sign, U+00A2) not GH₵ (cedi sign, U+20B5) - dompdf's default font has
        // no glyph for the real cedi sign and silently renders it as "?".
        return $items->map(fn ($item) => $item->categoryLabel() . ' (GH¢' . number_format($item->amount, 2) . ')')->implode(', ');
    }

    /**
     * A full numbered clause naming the applicant's assigned hall, or an empty string if
     * none has been assigned yet - fills {{hall_clause}}. Built as a complete <li>...</li>
     * (not just the hall name) so a not-yet-assigned applicant's letter simply has one
     * fewer numbered clause instead of an awkward blank one.
     */
    public static function hallClause(Admission $admission): string
    {
        if (!$admission->hall) {
            return '';
        }

        return '<li>You have been assigned to <strong>' . e($admission->hall) . '</strong> as your hall of residence.</li>';
    }

    /**
     * Substitute placeholders in the given body text (either a saved template's body, or
     * defaultBody()) with real values, for both the final rendered letter and the
     * template edit screen's live preview.
     */
    public static function render(string $body, array $data): string
    {
        $tokens = [];
        foreach ($data as $key => $value) {
            $tokens['{{' . $key . '}}'] = $value;
        }

        return strtr($body, $tokens);
    }

    /**
     * The fully-substituted letter body for a real admission - this academic year's
     * saved template if one exists, else defaultBody(). Shared by
     * AdmissionController::buildLetterPdf() and the template edit screen's preview.
     */
    public static function bodyFor(Admission $admission, bool $isFinal, ?string $institutionName = null): string
    {
        $template = static::where('academic_year_id', $admission->academic_year_id)->first();
        $body = $isFinal ? $template?->final_body : $template?->provisional_body;
        $body = $body ?: static::defaultBody($isFinal);

        return static::render($body, [
            'full_name' => $admission->full_name,
            'title' => $admission->title,
            'applicant_number' => $admission->applicant_number,
            'programme' => $admission->programme->name ?? '',
            'academic_year' => $admission->academicYear->name ?? '',
            'level' => (string) $admission->level,
            'institution_name' => $institutionName ?? config('app.name'),
            'total_billed' => number_format($admission->totalBilled(), 2),
            'amount_paid' => number_format($admission->totalPaid(), 2),
            'outstanding_balance' => number_format($admission->outstandingBalance(), 2),
            'bill_items' => static::billItemsSummary($admission),
            'hall_clause' => static::hallClause($admission),
        ]);
    }
}
