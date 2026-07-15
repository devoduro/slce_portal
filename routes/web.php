<?php

use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\ArrearsController;
use App\Http\Controllers\BiometricAdmsController;
use App\Http\Controllers\BulkResultController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FeeStructureController;
use App\Http\Controllers\GpaDistributionController;
use App\Http\Controllers\ImportExportController;
use App\Http\Controllers\PaymentUploadController;
use App\Http\Controllers\ProgrammeController;
use App\Http\Controllers\ReferenceNumberController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\SemesterController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentPaymentController;
use App\Http\Controllers\TranscriptController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ActivityLogController;
use Illuminate\Support\Facades\Route;

// Authentication Routes
Route::middleware('guest')->group(function () {
    // Admin/Staff Login
    Route::get('login', [\App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [\App\Http\Controllers\Auth\LoginController::class, 'login']);
    
    // Student Login
    Route::get('student/login', [\App\Http\Controllers\Auth\StudentAuthController::class, 'showLoginForm'])->name('student.login');
    Route::post('student/login', [\App\Http\Controllers\Auth\StudentAuthController::class, 'login']);

    // Student Password Reset (via SMS)
    Route::get('student/forgot-password', [\App\Http\Controllers\Auth\StudentPasswordResetController::class, 'showRequestForm'])->name('student.password.request');
    Route::post('student/forgot-password', [\App\Http\Controllers\Auth\StudentPasswordResetController::class, 'sendResetLink'])->name('student.password.email');
    Route::get('student/reset-password/{token}', [\App\Http\Controllers\Auth\StudentPasswordResetController::class, 'showResetForm'])->name('student.password.reset');
    Route::post('student/reset-password', [\App\Http\Controllers\Auth\StudentPasswordResetController::class, 'reset'])->name('student.password.update');
    
    // Password Reset
    Route::get('password/reset', [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('password/email', [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('password/reset/{token}', [\App\Http\Controllers\Auth\ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('password/reset', [\App\Http\Controllers\Auth\ResetPasswordController::class, 'reset'])->name('password.update');
});

Route::post('logout', [\App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');
Route::post('student/logout', [\App\Http\Controllers\Auth\StudentAuthController::class, 'logout'])->name('student.logout');

// ZKTeco Biometric Device Push (ADMS protocol) - unauthenticated, device-facing
Route::get('iclock/cdata', [BiometricAdmsController::class, 'handshake'])->name('iclock.handshake');
Route::post('iclock/cdata', [BiometricAdmsController::class, 'store'])->name('iclock.store');
Route::get('iclock/getrequest', [BiometricAdmsController::class, 'getRequest'])->name('iclock.getrequest');

// Student Portal Routes
Route::prefix('student')->name('student.')->group(function () {
    // Student-only routes
    Route::middleware(['auth'])->group(function () {
        // Password Change Routes (accessible even with first_login)
        Route::get('/change-password', [\App\Http\Controllers\Auth\StudentAuthController::class, 'showChangePasswordForm'])->name('change-password');
        Route::post('/update-password', [\App\Http\Controllers\Auth\StudentAuthController::class, 'updatePassword'])->name('update-password');
        
        // Routes protected by first login password change
        Route::middleware(['first.login'])->group(function () {
            Route::get('/dashboard', [\App\Http\Controllers\Auth\StudentAuthController::class, 'dashboard'])->name('dashboard');
            Route::get('/results', [\App\Http\Controllers\Auth\StudentAuthController::class, 'results'])->name('results');
            Route::get('/transcript', [\App\Http\Controllers\Auth\StudentAuthController::class, 'transcript'])->name('transcript');

            // Profile Routes
            Route::get('/profile/edit', [\App\Http\Controllers\Auth\StudentAuthController::class, 'editProfile'])->name('profile.edit');
            Route::post('/profile/update', [\App\Http\Controllers\Auth\StudentAuthController::class, 'updateProfile'])->name('profile.update');
            Route::post('/profile/photo/remove', [\App\Http\Controllers\Auth\StudentAuthController::class, 'removeProfilePhoto'])->name('profile.photo.remove');
        });
    });
});

// Guest Routes
Route::middleware(['guest'])->group(function () {
    Route::get('/', function () {
        return redirect()->route('login');
    });
});

// Admin Dashboard and Root Routes
Route::middleware(['auth', 'admin'])->group(function () {
    Route::middleware('permission:view-activity-logs')->group(function () {
        Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    });
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/admin/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/gpa-distribution', [GpaDistributionController::class, 'index'])->name('gpa-distribution.index');
});

// Protected Routes
Route::middleware(['auth'])->group(function () {
    // Password Change Routes
    Route::get('/password/change', [\App\Http\Controllers\PasswordController::class, 'edit'])->name('password.change');
    Route::put('/password/update', [\App\Http\Controllers\PasswordController::class, 'update'])->name('password.update');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // User Profile
    Route::get('/profile', [UserController::class, 'profile'])->name('profile');
    Route::put('/profile', [UserController::class, 'updateProfile'])->name('profile.update');

    // Lecturer self-service (not permission-gated - any account linked to a lecturer profile)
    Route::get('/my-profile', [\App\Http\Controllers\LecturerPortalController::class, 'profile'])->name('lecturer.profile.edit');
    Route::put('/my-profile', [\App\Http\Controllers\LecturerPortalController::class, 'updateProfile'])->name('lecturer.profile.update');
    Route::get('/my-timetable', [\App\Http\Controllers\LecturerPortalController::class, 'timetable'])->name('lecturer.timetable');
    Route::get('/my-timetable/print', [\App\Http\Controllers\LecturerPortalController::class, 'printTimetable'])->name('lecturer.timetable.print');

    // STS/Internship supervision (not permission-gated - any account linked to a lecturer profile)
    Route::get('/my-sts-students', [\App\Http\Controllers\StsSupervisionController::class, 'index'])->name('sts-supervision.index');
    Route::get('/my-sts-students/letter', [\App\Http\Controllers\StsSupervisionController::class, 'printLetter'])->name('sts-supervision.letter');
    Route::get('/my-sts-students/{stsPlacement}/score', [\App\Http\Controllers\StsSupervisionController::class, 'scoreForm'])->name('sts-supervision.score.edit');
    Route::post('/my-sts-students/{stsPlacement}/score', [\App\Http\Controllers\StsSupervisionController::class, 'scoreStore'])->name('sts-supervision.score.store');

    // Students
    Route::middleware('permission:manage-students')->group(function () {
        Route::get('/students/{id}/create-account', [StudentController::class, 'createUserAccount'])->name('students.create-account');
        Route::post('/students/bulk-create-accounts', [StudentController::class, 'bulkCreateUserAccounts'])->name('students.bulk-create-accounts');
        Route::post('/students/bulk-reset-passwords', [StudentController::class, 'bulkResetPasswords'])->name('students.bulk-reset-passwords');
        Route::resource('students', StudentController::class);
        Route::get('/students/search', [StudentController::class, 'search'])->name('students.search');
        Route::get('/students/filter/programme', [StudentController::class, 'filterByProgramme'])->name('students.filter.programme');
        Route::get('/students/{student}/results', [StudentController::class, 'results'])->name('students.results');
        Route::get('/students/{student}/transcript', [StudentController::class, 'transcript'])->name('students.transcript');
        Route::get('/students-import', [StudentController::class, 'importForm'])->name('students.import.form');
        Route::post('/students-import', [StudentController::class, 'import'])->name('students.import');
        Route::get('/students/export/excel', [StudentController::class, 'exportExcel'])->name('students.export.excel');
        Route::get('/students/export/pdf', [StudentController::class, 'exportPdf'])->name('students.export.pdf');
        Route::get('/students-import-template', [StudentController::class, 'downloadTemplate'])->name('students.import.template');
    });

    // Student Halls
    Route::middleware('permission:manage-students')->group(function () {
        Route::get('/student-halls', [\App\Http\Controllers\StudentHallController::class, 'index'])->name('student-halls.index');
        Route::get('/student-halls/upload', [\App\Http\Controllers\StudentHallController::class, 'uploadForm'])->name('student-halls.upload');
        Route::post('/student-halls/import', [\App\Http\Controllers\StudentHallController::class, 'import'])->name('student-halls.import');
        Route::get('/student-halls/template', [\App\Http\Controllers\StudentHallController::class, 'downloadTemplate'])->name('student-halls.template');
        Route::get('/student-halls/print', [\App\Http\Controllers\StudentHallController::class, 'print'])->name('student-halls.print');
    });

    // Bulk SMS
    Route::middleware('permission:send-sms')->group(function () {
        Route::get('/sms', [\App\Http\Controllers\SmsController::class, 'index'])->name('sms.index');
        Route::post('/sms/send', [\App\Http\Controllers\SmsController::class, 'send'])->name('sms.send');
        Route::get('/sms/history', [\App\Http\Controllers\SmsController::class, 'history'])->name('sms.history');
        Route::get('/sms/history/{campaign}', [\App\Http\Controllers\SmsController::class, 'showCampaign'])->name('sms.history.show');
        Route::get('/sms/templates', [\App\Http\Controllers\SmsController::class, 'templates'])->name('sms.templates.index');
        Route::post('/sms/templates', [\App\Http\Controllers\SmsController::class, 'storeTemplate'])->name('sms.templates.store');
        Route::put('/sms/templates/{template}', [\App\Http\Controllers\SmsController::class, 'updateTemplate'])->name('sms.templates.update');
        Route::delete('/sms/templates/{template}', [\App\Http\Controllers\SmsController::class, 'destroyTemplate'])->name('sms.templates.destroy');
    });

    // Courses
    Route::middleware('permission:manage-courses')->group(function () {
        Route::resource('courses', CourseController::class);
        Route::get('/courses/{course}/students', [CourseController::class, 'students'])->name('courses.students');
        Route::get('/courses/{course}/students/attendance', [CourseController::class, 'printAttendance'])->name('courses.students.attendance');
        Route::get('/courses/{course}/students/add', [CourseController::class, 'addStudentsForm'])->name('courses.students.add');
        Route::post('/courses/{course}/students/add', [CourseController::class, 'addStudents'])->name('courses.students.store');
        Route::get('/courses/{course}/results', [CourseController::class, 'results'])->name('courses.results');
        Route::get('/courses/{course}/export', [CourseController::class, 'export'])->name('courses.export');

        // Lecturers
        Route::get('/lecturers/import', [\App\Http\Controllers\LecturerController::class, 'importForm'])->name('lecturers.import.form');
        Route::post('/lecturers/import', [\App\Http\Controllers\LecturerController::class, 'import'])->name('lecturers.import.store');
        Route::get('/lecturers/import/template', [\App\Http\Controllers\LecturerController::class, 'downloadTemplate'])->name('lecturers.import.template');
        Route::get('/lecturers/export/excel', [\App\Http\Controllers\LecturerController::class, 'exportExcel'])->name('lecturers.export.excel');
        Route::get('/lecturers/export/pdf', [\App\Http\Controllers\LecturerController::class, 'exportPdf'])->name('lecturers.export.pdf');
        Route::post('/lecturers/{lecturer}/create-account', [\App\Http\Controllers\LecturerController::class, 'createUserAccount'])->name('lecturers.create-account');
        Route::post('/lecturers/{lecturer}/reset-password', [\App\Http\Controllers\LecturerController::class, 'resetPassword'])->name('lecturers.reset-password');
        Route::resource('lecturers', \App\Http\Controllers\LecturerController::class);

        // Departments
        Route::resource('departments', \App\Http\Controllers\DepartmentController::class)->except(['show']);
    });

    // Results Management
    Route::middleware('permission:manage-results')->group(function () {
        Route::resource('results', ResultController::class);
        Route::get('/results/search', [ResultController::class, 'search'])->name('results.search');
        Route::get('/results/filter/academic-year', [ResultController::class, 'filterByAcademicYear'])->name('results.filter.academic-year');
        Route::get('/results/filter/semester', [ResultController::class, 'filterBySemester'])->name('results.filter.semester');
        Route::get('/results/filter/student', [ResultController::class, 'filterByStudent'])->name('results.filter.student');
        Route::get('/results/filter/course', [ResultController::class, 'filterByCourse'])->name('results.filter.course');

        // Bulk Upload Results
        Route::get('/bulkresults/upload', [ResultController::class, 'bulkCreate'])->name('results.bulk-create');
        Route::post('/results/bulk-store', [ResultController::class, 'bulkStore'])->name('results.bulk-store');
        Route::get('/bulkresults/download-template', [ResultController::class, 'downloadTemplate'])->name('results.download-template');
    });

    // Programmes
    Route::middleware('permission:manage-programmes')->group(function () {
        Route::resource('programmes', ProgrammeController::class);
        Route::get('/programmes/search', [ProgrammeController::class, 'search'])->name('programmes.search');
        Route::get('/programmes/{programme}/students', [ProgrammeController::class, 'students'])->name('programmes.students');
        Route::get('/programmes/{programme}/courses', [ProgrammeController::class, 'courses'])->name('programmes.courses');
        Route::get('/programmes/{programme}/export', [ProgrammeController::class, 'export'])->name('programmes.export');
    });

    // Semesters
    Route::middleware('permission:manage-semesters')->group(function () {
        Route::get('/semesters/filter/academic-year', [SemesterController::class, 'filterByAcademicYear'])->name('semesters.filter.academic-year');
        Route::put('/semesters/{semester}/set-current', [SemesterController::class, 'setCurrent'])->name('semesters.set-current');
        Route::put('/semesters/{semester}/toggle-registration', [SemesterController::class, 'toggleRegistration'])->name('semesters.toggle-registration');
        Route::put('/semesters/{semester}/toggle-biometric-window', [SemesterController::class, 'toggleBiometricWindow'])->name('semesters.toggle-biometric-window');
        Route::get('/semesters/{semester}/courses', [SemesterController::class, 'courses'])->name('semesters.courses');
        Route::get('/semesters/{semester}/results', [SemesterController::class, 'results'])->name('semesters.results');
        Route::resource('semesters', SemesterController::class);
    });

    // Fee Management
    Route::middleware('permission:manage-fees')->group(function () {
        // Fee structure upload routes must be registered before the resource's /fee-structures/{fee_structure} wildcard.
        Route::get('/fee-structures/upload', [FeeStructureController::class, 'uploadForm'])->name('fee-structures.upload');
        Route::post('/fee-structures/import', [FeeStructureController::class, 'import'])->name('fee-structures.import');
        Route::get('/fee-structures/template', [FeeStructureController::class, 'downloadTemplate'])->name('fee-structures.template');
        Route::resource('fee-structures', FeeStructureController::class)->except(['show']);

        // Specific student fee charges (graduation fee, resit fee, etc. billed to individual students).
        Route::get('/fees/charges', [\App\Http\Controllers\StudentFeeChargeController::class, 'index'])->name('fees.charges.index');
        Route::get('/fees/charges/upload', [\App\Http\Controllers\StudentFeeChargeController::class, 'uploadForm'])->name('fees.charges.upload');
        Route::post('/fees/charges/import', [\App\Http\Controllers\StudentFeeChargeController::class, 'import'])->name('fees.charges.import');
        Route::get('/fees/charges/template', [\App\Http\Controllers\StudentFeeChargeController::class, 'downloadTemplate'])->name('fees.charges.template');
        Route::post('/fees/charges/bulk-destroy', [\App\Http\Controllers\StudentFeeChargeController::class, 'bulkDestroy'])->name('fees.charges.bulk-destroy');
        Route::delete('/fees/charges/{charge}', [\App\Http\Controllers\StudentFeeChargeController::class, 'destroy'])->name('fees.charges.destroy');

        // Fee categories (tuition, graduation, resit, and any custom ones an admin adds).
        Route::post('/fees/categories', [\App\Http\Controllers\FeeCategoryController::class, 'store'])->name('fees.categories.store');
        Route::delete('/fees/categories/{category}', [\App\Http\Controllers\FeeCategoryController::class, 'destroy'])->name('fees.categories.destroy');

        Route::get('/fees', [StudentPaymentController::class, 'index'])->name('fees.index');
        Route::get('/fees/export/excel', [StudentPaymentController::class, 'exportExcel'])->name('fees.export.excel');
        Route::get('/fees/export/pdf', [StudentPaymentController::class, 'exportPdf'])->name('fees.export.pdf');
        Route::get('/fees/report', [StudentPaymentController::class, 'report'])->name('fees.report');
        Route::get('/fees/report/print', [StudentPaymentController::class, 'printReport'])->name('fees.report.print');

        // Arrears (debtors list) routes must be registered before the /fees/{student} wildcard below.
        Route::get('/fees/arrears', [ArrearsController::class, 'index'])->name('fees.arrears.index');
        Route::get('/fees/arrears/upload', [ArrearsController::class, 'uploadForm'])->name('fees.arrears.upload');
        Route::post('/fees/arrears/import', [ArrearsController::class, 'import'])->name('fees.arrears.import');
        Route::get('/fees/arrears/template', [ArrearsController::class, 'downloadTemplate'])->name('fees.arrears.template');
        Route::post('/fees/arrears/bulk-destroy', [ArrearsController::class, 'bulkDestroy'])->name('fees.arrears.bulk-destroy');
        Route::delete('/fees/arrears/{arrear}', [ArrearsController::class, 'destroy'])->name('fees.arrears.destroy');

        // Reference numbers and payment upload routes must also be registered before /fees/{student}.
        Route::get('/fees/reference-numbers', [ReferenceNumberController::class, 'index'])->name('fees.reference-numbers.index');
        Route::get('/fees/reference-numbers/upload', [ReferenceNumberController::class, 'uploadForm'])->name('fees.reference-numbers.upload');
        Route::post('/fees/reference-numbers/import', [ReferenceNumberController::class, 'import'])->name('fees.reference-numbers.import');
        Route::get('/fees/reference-numbers/template', [ReferenceNumberController::class, 'downloadTemplate'])->name('fees.reference-numbers.template');

        Route::get('/fees/payments/upload', [PaymentUploadController::class, 'uploadForm'])->name('fees.payments.upload');
        Route::post('/fees/payments/import', [PaymentUploadController::class, 'import'])->name('fees.payments.import');
        Route::get('/fees/payments/template', [PaymentUploadController::class, 'downloadTemplate'])->name('fees.payments.template');

        Route::get('/fees/{student}', [StudentPaymentController::class, 'show'])->name('fees.show');
        Route::get('/fees/{student}/print', [StudentPaymentController::class, 'printLedger'])->name('fees.print');
        Route::post('/fees/{student}/payments', [StudentPaymentController::class, 'store'])->name('fees.payments.store');
        Route::put('/fees/payments/{payment}', [StudentPaymentController::class, 'update'])->name('fees.payments.update');
        Route::delete('/fees/payments/{payment}', [StudentPaymentController::class, 'destroy'])->name('fees.payments.destroy');
    });

    // Biometric Registration
    Route::middleware('permission:manage-biometric')->group(function () {
        Route::resource('biometric-devices', \App\Http\Controllers\BiometricDeviceController::class)->except(['show']);
        Route::get('/biometric-verifications', [\App\Http\Controllers\BiometricRegistrationController::class, 'index'])->name('biometric-verifications.index');
        Route::post('/biometric-verifications/bulk-verify', [\App\Http\Controllers\BiometricRegistrationController::class, 'bulkStore'])->name('biometric-verifications.bulk-verify');
        Route::get('/biometric-verifications/{student}', [\App\Http\Controllers\BiometricRegistrationController::class, 'show'])->name('biometric-verifications.show');
        Route::post('/biometric-verifications/{student}', [\App\Http\Controllers\BiometricRegistrationController::class, 'store'])->name('biometric-verifications.store');
        Route::delete('/biometric-verifications/entry/{registration}', [\App\Http\Controllers\BiometricRegistrationController::class, 'destroy'])->name('biometric-verifications.destroy');
    });

    // Classes
    Route::middleware('permission:manage-classes')->group(function () {
        Route::get('/class-groups/import', [\App\Http\Controllers\ClassGroupController::class, 'importForm'])->name('class-groups.import.form');
        Route::post('/class-groups/import', [\App\Http\Controllers\ClassGroupController::class, 'import'])->name('class-groups.import.store');
        Route::get('/class-groups/import/template', [\App\Http\Controllers\ClassGroupController::class, 'downloadTemplate'])->name('class-groups.import.template');
        Route::get('/class-groups/{classGroup}/print', [\App\Http\Controllers\ClassGroupController::class, 'print'])->name('class-groups.print');
        Route::get('/class-groups/{classGroup}/assign-students', [\App\Http\Controllers\ClassGroupController::class, 'assignStudentsForm'])->name('class-groups.assign-students');
        Route::post('/class-groups/{classGroup}/assign-students', [\App\Http\Controllers\ClassGroupController::class, 'assignStudents'])->name('class-groups.assign-students.store');
        Route::resource('class-groups', \App\Http\Controllers\ClassGroupController::class)->except(['show']);

        // Student level promotion (new academic year workflow)
        Route::get('/promotions', [\App\Http\Controllers\PromotionController::class, 'index'])->name('promotions.index');
        Route::post('/promotions/preview', [\App\Http\Controllers\PromotionController::class, 'preview'])->name('promotions.preview');
        Route::post('/promotions', [\App\Http\Controllers\PromotionController::class, 'store'])->name('promotions.store');
    });

    // STS / Internship
    Route::middleware('permission:manage-sts')->group(function () {
        Route::put('/sts-terms/{stsTerm}/activate', [\App\Http\Controllers\StsTermController::class, 'activate'])->name('sts-terms.activate');
        Route::resource('sts-terms', \App\Http\Controllers\StsTermController::class)->except(['show']);

        Route::get('/partner-schools/import', [\App\Http\Controllers\PartnerSchoolController::class, 'importForm'])->name('partner-schools.import.form');
        Route::post('/partner-schools/import', [\App\Http\Controllers\PartnerSchoolController::class, 'import'])->name('partner-schools.import.store');
        Route::get('/partner-schools/import/template', [\App\Http\Controllers\PartnerSchoolController::class, 'downloadTemplate'])->name('partner-schools.import.template');
        Route::resource('partner-schools', \App\Http\Controllers\PartnerSchoolController::class)->except(['show']);

        Route::resource('sts-score-settings', \App\Http\Controllers\StsScoreSettingController::class)->except(['show']);

        Route::get('/sts-placements', [\App\Http\Controllers\StsPlacementController::class, 'index'])->name('sts-placements.index');
        Route::put('/sts-placements/{stsPlacement}/assign-supervisor', [\App\Http\Controllers\StsPlacementController::class, 'assignSupervisor'])->name('sts-placements.assign-supervisor');
    });

    // Timetable
    Route::middleware('permission:manage-timetable')->group(function () {
        Route::get('/timetable/print', [\App\Http\Controllers\TimetableController::class, 'print'])->name('timetable.print');
        Route::resource('timetable', \App\Http\Controllers\TimetableController::class)->except(['show']);
        Route::resource('venues', \App\Http\Controllers\VenueController::class)->except(['show']);
    });

    // Continuous Assessment
    Route::middleware('permission:manage-continuous-assessment')->group(function () {
        Route::get('/continuous-assessment', [\App\Http\Controllers\ContinuousAssessmentController::class, 'index'])->name('continuous-assessment.index');
        Route::get('/continuous-assessment/export/excel', [\App\Http\Controllers\ContinuousAssessmentController::class, 'exportExcel'])->name('continuous-assessment.export.excel');
        Route::get('/continuous-assessment/export/pdf', [\App\Http\Controllers\ContinuousAssessmentController::class, 'exportPdf'])->name('continuous-assessment.export.pdf');
        Route::get('/continuous-assessment/{course}', [\App\Http\Controllers\ContinuousAssessmentController::class, 'show'])->name('continuous-assessment.show');
        Route::post('/continuous-assessment/{course}', [\App\Http\Controllers\ContinuousAssessmentController::class, 'store'])->name('continuous-assessment.store');
    });

    // Transcripts
    Route::middleware('permission:manage-transcripts')->group(function () {
        Route::get('/transcripts', [TranscriptController::class, 'index'])->name('transcripts.index');
        Route::get('/transcripts/create', [TranscriptController::class, 'create'])->name('transcripts.create');
        Route::post('/transcripts', [TranscriptController::class, 'store'])->name('transcripts.store');
        Route::get('/transcripts/{transcript}/show', [TranscriptController::class, 'show'])->name('transcripts.show');
        Route::delete('/transcripts/{transcript}', [TranscriptController::class, 'destroy'])->name('transcripts.destroy');
        Route::get('/transcripts/{transcript}/download', [TranscriptController::class, 'download'])->name('transcripts.download');
        Route::get('/transcripts/{student}/generate', [TranscriptController::class, 'generate'])->name('transcripts.generate');
        Route::get('/transcripts/{student}/preview', [TranscriptController::class, 'preview'])->name('transcripts.preview');
        Route::get('/transcripts/bulk', [TranscriptController::class, 'bulk'])->name('transcripts.bulk');
        Route::post('/transcripts/bulk-generate', [TranscriptController::class, 'bulkGenerate'])->name('transcripts.bulk-generate');
    });

    // Reports
    Route::middleware('permission:view-reports')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/gpa-distribution', [ReportController::class, 'gpaDistribution'])->name('reports.gpa-distribution');
        Route::get('/reports/course-performance', [ReportController::class, 'coursePerformance'])->name('reports.course-performance');
        Route::get('/reports/student-performance', [ReportController::class, 'studentPerformance'])->name('reports.student-performance');
        Route::get('/reports/programme-statistics', [ReportController::class, 'programmeStatistics'])->name('reports.programme-statistics');
        Route::get('/reports/classification-distribution', [ReportController::class, 'classificationDistribution'])->name('reports.classification-distribution');
        Route::get('/reports/semester-comparison', [ReportController::class, 'semesterComparison'])->name('reports.semester-comparison');
        Route::get('/reports/export-excel', [ReportController::class, 'exportToExcel'])->name('reports.export-excel');
    });

    // Import/Export
    Route::middleware('permission:manage-import-export')->group(function () {
        Route::get('/import-export', [ImportExportController::class, 'index'])->name('import-export.index');
        Route::post('/import-export/import', [ImportExportController::class, 'processImport'])->name('import.process');
        Route::post('/import-export/export', [ImportExportController::class, 'processExport'])->name('export.process');
        Route::get('/import-export/templates/{type}', [ImportExportController::class, 'downloadTemplate'])->name('templates.download');
        Route::get('/import-export/exports/{export}/download', [ImportExportController::class, 'downloadExport'])->name('exports.download');
        Route::get('/import-export/students/form', [ImportExportController::class, 'importStudentsForm'])->name('import.students.form');
        Route::post('/import-export/students/import', [ImportExportController::class, 'importStudents'])->name('import.students');
        Route::get('/import-export/courses/form', [ImportExportController::class, 'importCoursesForm'])->name('import.courses.form');
        Route::post('/import-export/courses/import', [ImportExportController::class, 'importCourses'])->name('import.courses');
        Route::get('/import-export/results/form', [ImportExportController::class, 'importResultsForm'])->name('import.results.form');
        Route::post('/import-export/results/import', [ImportExportController::class, 'importResults'])->name('import.results');
        Route::post('/import-export/students/export', [ImportExportController::class, 'exportStudents'])->name('export.students');
        Route::post('/import-export/courses/export', [ImportExportController::class, 'exportCourses'])->name('export.courses');
        Route::post('/import-export/results/export', [ImportExportController::class, 'exportResults'])->name('export.results');
        Route::post('/import-export/report/export', [ImportExportController::class, 'exportReport'])->name('export.report');
    });

    // Student Dashboard Routes
    Route::middleware(['auth'])->group(function () {
        Route::group(['middleware' => function ($request, $next) {
            if (auth()->check() && auth()->user()->role === 'student') {
                return $next($request);
            }
            return redirect()->route('login');
        }, 'prefix' => 'student', 'as' => 'student.'], function () {
            Route::get('/dashboard', [\App\Http\Controllers\Auth\StudentAuthController::class, 'dashboard'])->name('dashboard');
            Route::get('/results', [\App\Http\Controllers\Auth\StudentAuthController::class, 'results'])->name('results');
            Route::get('/transcript', [\App\Http\Controllers\Auth\StudentAuthController::class, 'transcript'])->name('transcript');

            // Course Registration Routes
            Route::get('/registration', [RegistrationController::class, 'index'])->name('registration.index');
            Route::get('/registration/create', [RegistrationController::class, 'create'])->name('registration.create');
            Route::post('/registration', [RegistrationController::class, 'store'])->name('registration.store');
            Route::get('/registration/print', [RegistrationController::class, 'print'])->name('registration.print');
            Route::delete('/registration/{registration}', [RegistrationController::class, 'destroy'])->name('registration.destroy');

            Route::get('/continuous-assessment', [\App\Http\Controllers\Auth\StudentAuthController::class, 'continuousAssessment'])->name('continuous-assessment');

            Route::get('/fees', [\App\Http\Controllers\StudentFeeController::class, 'index'])->name('fees.index');

            // STS / Internship self-service
            Route::get('/sts', [\App\Http\Controllers\StsSelectionController::class, 'index'])->name('sts.index');
            Route::get('/sts/schools', [\App\Http\Controllers\StsSelectionController::class, 'schools'])->name('sts.schools');
            Route::post('/sts/schools/{partnerSchool}/select', [\App\Http\Controllers\StsSelectionController::class, 'select'])->name('sts.select');
            Route::get('/sts/letter', [\App\Http\Controllers\StsSelectionController::class, 'printLetter'])->name('sts.letter');

            Route::get('/timetable', [\App\Http\Controllers\Auth\StudentAuthController::class, 'timetable'])->name('timetable');
            Route::get('/timetable/print', [\App\Http\Controllers\Auth\StudentAuthController::class, 'printTimetable'])->name('timetable.print');

            Route::get('/profile/edit', [\App\Http\Controllers\Auth\StudentAuthController::class, 'editProfile'])->name('profile.edit');
            Route::post('/profile/update', [\App\Http\Controllers\Auth\StudentAuthController::class, 'updateProfile'])->name('profile.update');
        });
    });
    
    // Admin Only Routes
    Route::middleware(['auth', 'admin'])->group(function () {
        // User Management
        Route::middleware('permission:manage-users')->group(function () {
            Route::resource('users', UserController::class);
            Route::get('/users/search', [UserController::class, 'search'])->name('users.search');
            Route::get('/users/filter/role', [UserController::class, 'filterByRole'])->name('users.filter.role');
            Route::get('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
            Route::put('/users/{user}/update-password', [UserController::class, 'updatePassword'])->name('users.update-password');

            // Student User Management
            Route::get('/students/{student}/create-user', [UserController::class, 'createStudentUser'])->name('users.create-student-user');
            Route::post('/students/{student}/create-user', [UserController::class, 'storeStudentUser'])->name('users.store-student-user');
        });

        // Roles & Permissions
        Route::middleware('permission:manage-roles')->group(function () {
            Route::resource('roles', \App\Http\Controllers\RoleController::class)->except(['show']);
        });

        // Settings
        Route::middleware('permission:manage-settings')->group(function () {
            Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');

            // Academic Year Settings
            Route::get('/settings/academic-years', [SettingController::class, 'academicYears'])->name('settings.academic-years');
            Route::get('/settings/academic-years/create', [SettingController::class, 'createAcademicYear'])->name('settings.academic-years.create');
            Route::post('/settings/academic-years', [SettingController::class, 'storeAcademicYear'])->name('settings.academic-years.store');
            Route::get('/settings/academic-years/{academicYear}/edit', [SettingController::class, 'editAcademicYear'])->name('settings.academic-years.edit');
            Route::put('/settings/academic-years/{academicYear}', [SettingController::class, 'updateAcademicYear'])->name('settings.academic-years.update');
            Route::delete('/settings/academic-years/{academicYear}', [SettingController::class, 'destroyAcademicYear'])->name('settings.academic-years.destroy');
            Route::put('/settings/academic-years/{academicYear}/set-current', [SettingController::class, 'setCurrentAcademicYear'])->name('settings.academic-years.set-current');

            // Grade Scheme Settings
            Route::get('/settings/grade-schemes', [SettingController::class, 'gradeSchemes'])->name('settings.grade-schemes');
            Route::get('/settings/grade-schemes/create', [SettingController::class, 'createGradeScheme'])->name('settings.grade-schemes.create');
            Route::post('/settings/grade-schemes', [SettingController::class, 'storeGradeScheme'])->name('settings.grade-schemes.store');
            Route::get('/settings/grade-schemes/{gradeScheme}/edit', [SettingController::class, 'editGradeScheme'])->name('settings.grade-schemes.edit');
            Route::put('/settings/grade-schemes/{gradeScheme}', [SettingController::class, 'updateGradeScheme'])->name('settings.grade-schemes.update');
            Route::delete('/settings/grade-schemes/{gradeScheme}', [SettingController::class, 'destroyGradeScheme'])->name('settings.grade-schemes.destroy');
            Route::patch('/settings/grade-schemes/{gradeScheme}/set-default', [SettingController::class, 'setDefaultGradeScheme'])->name('settings.grade-schemes.set-default');

            // Classification Settings
            Route::get('/settings/classifications', [SettingController::class, 'classifications'])->name('settings.classifications');
            Route::get('/settings/classifications/create', [SettingController::class, 'createClassification'])->name('settings.classifications.create');
            Route::post('/settings/classifications', [SettingController::class, 'storeClassification'])->name('settings.classifications.store');
            Route::get('/settings/classifications/{classification}/edit', [SettingController::class, 'editClassification'])->name('settings.classifications.edit');
            Route::put('/settings/classifications/{classification}', [SettingController::class, 'updateClassification'])->name('settings.classifications.update');
            Route::delete('/settings/classifications/{classification}', [SettingController::class, 'destroyClassification'])->name('settings.classifications.destroy');

            // Database Backup
            Route::get('/settings/backup', [SettingController::class, 'backup'])->name('settings.backup');
            Route::post('/settings/backup/create', function(\Illuminate\Http\Request $request) {
                \Log::info('Route debug info:', [
                    'method' => $request->method(),
                    'url' => $request->url(),
                    'path' => $request->path(),
                    'ajax' => $request->ajax(),
                    'headers' => $request->headers->all(),
                    'middleware' => Route::current()->middleware()
                ]);
                return app()->call([app(SettingController::class), 'backupDatabase'], ['request' => $request]);
            })->name('settings.backup.create');
            Route::get('/settings/backup/{filename}/download', [SettingController::class, 'downloadBackup'])->name('settings.backup.download');
            Route::delete('/settings/backup/{filename}', [SettingController::class, 'destroyBackup'])->name('settings.backup.destroy');

            // System Settings
            Route::get('/settings/system', [SettingController::class, 'system'])->name('settings.system');
            Route::put('/settings/system', [SettingController::class, 'updateSystem'])->name('settings.system.update');

            // Institution Settings
            Route::get('/settings/institution', [SettingController::class, 'institution'])->name('settings.institution');
            Route::put('/settings/institution', [SettingController::class, 'updateInstitution'])->name('settings.institution.update');

            // CA Score Settings
            Route::resource('ca-score-settings', \App\Http\Controllers\CaScoreSettingController::class)->except(['show']);

            // STS Settings
            Route::get('/settings/sts', [SettingController::class, 'sts'])->name('settings.sts');
            Route::put('/settings/sts', [SettingController::class, 'updateSts'])->name('settings.sts.update');
        });

        // Bulk Results Upload
        Route::middleware('permission:manage-results')->group(function () {
            Route::get('/bulkresults/upload', [BulkResultController::class, 'showUploadForm'])->name('bulkresults.upload');
            Route::post('/bulkresults/process', [BulkResultController::class, 'processUpload'])->name('bulkresults.process');
            Route::get('/bulkresults/semesters', [BulkResultController::class, 'getSemesters'])->name('bulkresults.semesters');
            Route::get('/bulkresults/courses', [BulkResultController::class, 'getCourses'])->name('bulkresults.courses');
            Route::get('/bulkresults/template/{type?}', [BulkResultController::class, 'downloadTemplate'])->name('bulkresults.template');
        });
    });
});
