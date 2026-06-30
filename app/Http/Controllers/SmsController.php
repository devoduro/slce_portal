<?php

namespace App\Http\Controllers;

use App\Models\Programme;
use App\Models\SmsCampaign;
use App\Models\SmsLog;
use App\Models\SmsTemplate;
use App\Models\Student;
use App\Services\PastechSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SmsController extends Controller
{
    /**
     * Show the bulk SMS send form with student filters.
     */
    public function index(Request $request)
    {
        $programmes = Programme::orderBy('name')->get();
        $templates = SmsTemplate::orderBy('name')->get();

        $query = Student::query()->with('programme');

        if ($request->filled('programme_id')) {
            $query->where('programme_id', $request->programme_id);
        }

        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('index_number', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $recipientCount = (clone $query)->whereNotNull('phone')->where('phone', '!=', '')->count();

        $students = $query->orderBy('full_name')->paginate(20)->withQueryString();

        return view('sms.index', compact('programmes', 'templates', 'students', 'recipientCount'));
    }

    /**
     * Send a bulk SMS to students matching the given filters.
     */
    public function send(Request $request, PastechSmsService $sms)
    {
        $request->validate([
            'message'      => ['required', 'string', 'max:459'],
            'programme_id' => ['nullable', 'exists:programmes,id'],
            'gender'       => ['nullable', 'in:Male,Female,Other'],
            'search'       => ['nullable', 'string'],
        ]);

        $filters = $request->only(['programme_id', 'gender', 'search']);

        $query = Student::query();

        if (!empty($filters['programme_id'])) {
            $query->where('programme_id', $filters['programme_id']);
        }

        if (!empty($filters['gender'])) {
            $query->where('gender', $filters['gender']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('index_number', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $students = $query->whereNotNull('phone')->where('phone', '!=', '')->get();

        if ($students->isEmpty()) {
            return back()->with('error', 'No students with a phone number match the selected filters.')->withInput();
        }

        $campaign = SmsCampaign::create([
            'message'          => $request->message,
            'filters'          => $filters,
            'total_recipients' => $students->count(),
            'sent_by'          => Auth::id(),
        ]);

        $successCount = 0;
        $failedCount = 0;

        foreach ($students as $student) {
            $result = $sms->sendSms($student->phone, $request->message);

            SmsLog::create([
                'sms_campaign_id'   => $campaign->id,
                'student_id'        => $student->id,
                'phone'             => $student->phone,
                'message'           => $request->message,
                'success'           => $result['success'],
                'response_code'     => $result['code'],
                'response_message'  => $result['message'],
            ]);

            $result['success'] ? $successCount++ : $failedCount++;
        }

        $campaign->update([
            'success_count' => $successCount,
            'failed_count'  => $failedCount,
        ]);

        return redirect()->route('sms.history')
            ->with('success', "SMS campaign sent: {$successCount} succeeded, {$failedCount} failed out of {$students->count()} recipients.");
    }

    /**
     * Show sent message history (campaigns).
     */
    public function history()
    {
        $campaigns = SmsCampaign::with('sender')
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('sms.history', compact('campaigns'));
    }

    /**
     * Show detailed delivery logs for a single campaign.
     */
    public function showCampaign(SmsCampaign $campaign)
    {
        $logs = $campaign->logs()->with('student')->orderByDesc('created_at')->paginate(50);

        return view('sms.campaign-detail', compact('campaign', 'logs'));
    }

    // ──────────────────────────────────────────────
    // SMS Templates
    // ──────────────────────────────────────────────

    public function templates()
    {
        $templates = SmsTemplate::with('creator')->orderByDesc('created_at')->get();

        return view('sms.templates.index', compact('templates'));
    }

    public function storeTemplate(Request $request)
    {
        $request->validate([
            'name'    => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:459'],
        ]);

        SmsTemplate::create([
            'name'       => $request->name,
            'message'    => $request->message,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('sms.templates.index')->with('success', 'SMS template created successfully.');
    }

    public function updateTemplate(Request $request, SmsTemplate $template)
    {
        $request->validate([
            'name'    => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:459'],
        ]);

        $template->update($request->only(['name', 'message']));

        return redirect()->route('sms.templates.index')->with('success', 'SMS template updated successfully.');
    }

    public function destroyTemplate(SmsTemplate $template)
    {
        $template->delete();

        return redirect()->route('sms.templates.index')->with('success', 'SMS template deleted successfully.');
    }
}
