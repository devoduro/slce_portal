<?php

namespace App\Http\Controllers;

use App\Models\BiometricDevice;
use App\Models\BiometricLog;
use App\Models\BiometricRegistration;
use App\Models\Semester;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Handles the ZKTeco ADMS/PUSH protocol endpoints that biometric devices
 * call directly over HTTP (no authentication - the device cannot log in).
 *
 * These endpoints follow the conventional paths ZKTeco firmware is hardcoded
 * (or commonly configured) to call: /iclock/cdata and /iclock/getrequest.
 */
class BiometricAdmsController extends Controller
{
    /**
     * Device handshake / options request: GET /iclock/cdata?SN=...&options=all
     * Also handles the device pushing attendance data via GET with a "table" query param
     * on some firmware versions, though POST is the common case (see store()).
     */
    public function handshake(Request $request): Response
    {
        $serial = $request->query('SN');

        if ($serial) {
            $device = BiometricDevice::firstOrCreate(
                ['serial_number' => $serial],
                ['name' => $serial]
            );
            $device->update(['last_seen_at' => now()]);
        }

        $body = implode("\r\n", [
            "GET OPTION FROM: {$serial}",
            'Stamp=9999',
            'OpStamp=9999',
            'ErrorDelay=60',
            'Delay=30',
            'TransFlag=1111000000',
            'TransInterval=1',
            'Realtime=1',
            'Encrypt=0',
        ]) . "\r\n";

        return response($body, 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Attendance push: POST /iclock/cdata?SN=...&table=ATTLOG
     * Body is raw tab-separated lines: PIN\tTIME\tSTATUS\tVERIFY...
     */
    public function store(Request $request): Response
    {
        $serial = $request->query('SN');
        $table = $request->query('table');

        $device = null;
        if ($serial) {
            $device = BiometricDevice::firstOrCreate(
                ['serial_number' => $serial],
                ['name' => $serial]
            );
            $device->update(['last_seen_at' => now()]);
        }

        $processed = 0;

        if ($table === 'ATTLOG') {
            $lines = preg_split('/\r\n|\r|\n/', trim($request->getContent()));

            foreach ($lines as $line) {
                if (trim($line) === '') {
                    continue;
                }

                $processed += $this->ingestAttendanceLine($line, $device);
            }
        }

        return response("OK: {$processed}", 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Command polling: GET /iclock/getrequest?SN=...
     * We don't queue remote commands for the device, so always respond with no-op.
     */
    public function getRequest(Request $request): Response
    {
        return response('OK', 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Parse a single ATTLOG line, record the raw log, and mark biometric
     * registration for the matched student if their current semester's
     * biometric window is open. Attendance itself (lesson and STS/Internship) is entered
     * manually by lecturers/admins via the CA and STS score forms, not derived from device
     * punches - this only feeds the separate semester check-in gate.
     */
    protected function ingestAttendanceLine(string $line, ?BiometricDevice $device): int
    {
        $fields = preg_split('/\t/', $line);
        $devicePin = trim($fields[0] ?? '');
        $rawTime = trim($fields[1] ?? '');
        $verifyMode = trim($fields[3] ?? '') ?: null;

        if ($devicePin === '' || $rawTime === '') {
            return 0;
        }

        $punchedAt = strtotime($rawTime);
        if ($punchedAt === false) {
            return 0;
        }

        $student = Student::where('index_number', $devicePin)->first();

        $log = BiometricLog::create([
            'biometric_device_id' => $device?->id,
            'device_user_id' => $devicePin,
            'punched_at' => date('Y-m-d H:i:s', $punchedAt),
            'verify_mode' => $verifyMode,
            'raw_payload' => $line,
            'student_id' => $student?->id,
        ]);

        if ($student) {
            $currentSemester = Semester::where('is_current', true)
                ->where('biometric_window_open', true)
                ->first();

            if ($currentSemester) {
                BiometricRegistration::firstOrCreate(
                    [
                        'student_id' => $student->id,
                        'semester_id' => $currentSemester->id,
                    ],
                    [
                        'verified_at' => $log->punched_at,
                        'source' => 'device',
                        'biometric_log_id' => $log->id,
                    ]
                );
            }
        }

        return 1;
    }
}
