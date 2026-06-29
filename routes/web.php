<?php

use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\BulkResultController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GpaDistributionController;
use App\Http\Controllers\ImportExportController;
use App\Http\Controllers\ProgrammeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\SemesterController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StudentController;
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
    
    // Password Reset
    Route::get('password/reset', [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('password/email', [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('password/reset/{token}', [\App\Http\Controllers\Auth\ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('password/reset', [\App\Http\Controllers\Auth\ResetPasswordController::class, 'reset'])->name('password.update');
});

Route::post('logout', [\App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');
Route::post('student/logout', [\App\Http\Controllers\Auth\StudentAuthController::class, 'logout'])->name('student.logout');

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
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
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
    
    // Students
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
    
    // Courses
    Route::resource('courses', CourseController::class);
    Route::get('/courses/search', [CourseController::class, 'search'])->name('courses.search');
    Route::get('/courses/filter/programme', [CourseController::class, 'filterByProgramme'])->name('courses.filter.programme');
    Route::get('/courses/filter/semester', [CourseController::class, 'filterBySemester'])->name('courses.filter.semester');
    Route::get('/courses/{course}/students', [CourseController::class, 'students'])->name('courses.students');
    Route::get('/courses/{course}/students/add', [CourseController::class, 'addStudentsForm'])->name('courses.students.add');
    Route::post('/courses/{course}/students/add', [CourseController::class, 'addStudents'])->name('courses.students.store');
    Route::get('/courses/{course}/results', [CourseController::class, 'results'])->name('courses.results');
    Route::get('/courses/{course}/export', [CourseController::class, 'export'])->name('courses.export');
    
    // Results Management
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
    
    // Programmes
    Route::resource('programmes', ProgrammeController::class);
    Route::get('/programmes/search', [ProgrammeController::class, 'search'])->name('programmes.search');
    Route::get('/programmes/{programme}/students', [ProgrammeController::class, 'students'])->name('programmes.students');
    Route::get('/programmes/{programme}/courses', [ProgrammeController::class, 'courses'])->name('programmes.courses');
    Route::get('/programmes/{programme}/export', [ProgrammeController::class, 'export'])->name('programmes.export');
    
    // Academic Years

    
    // Semesters
    Route::get('/semesters/filter/academic-year', [SemesterController::class, 'filterByAcademicYear'])->name('semesters.filter.academic-year');
    Route::put('/semesters/{semester}/set-current', [SemesterController::class, 'setCurrent'])->name('semesters.set-current');
    Route::get('/semesters/{semester}/courses', [SemesterController::class, 'courses'])->name('semesters.courses');
    Route::get('/semesters/{semester}/results', [SemesterController::class, 'results'])->name('semesters.results');
    Route::resource('semesters', SemesterController::class);
    
    // Transcripts
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
    
    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/gpa-distribution', [ReportController::class, 'gpaDistribution'])->name('reports.gpa-distribution');
    Route::get('/reports/course-performance', [ReportController::class, 'coursePerformance'])->name('reports.course-performance');
    Route::get('/reports/student-performance', [ReportController::class, 'studentPerformance'])->name('reports.student-performance');
    Route::get('/reports/programme-statistics', [ReportController::class, 'programmeStatistics'])->name('reports.programme-statistics');
    Route::get('/reports/classification-distribution', [ReportController::class, 'classificationDistribution'])->name('reports.classification-distribution');
    Route::get('/reports/semester-comparison', [ReportController::class, 'semesterComparison'])->name('reports.semester-comparison');
    Route::get('/reports/export-excel', [ReportController::class, 'exportToExcel'])->name('reports.export-excel');
    
    // Import/Export
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
            Route::get('/profile/edit', [\App\Http\Controllers\Auth\StudentAuthController::class, 'editProfile'])->name('profile.edit');
            Route::post('/profile/update', [\App\Http\Controllers\Auth\StudentAuthController::class, 'updateProfile'])->name('profile.update');
        });
    });
    
    // Admin Only Routes
    Route::middleware(['auth', 'admin'])->group(function () {
        // User Management
        Route::resource('users', UserController::class);
        Route::get('/users/search', [UserController::class, 'search'])->name('users.search');
        Route::get('/users/filter/role', [UserController::class, 'filterByRole'])->name('users.filter.role');
        Route::get('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::put('/users/{user}/update-password', [UserController::class, 'updatePassword'])->name('users.update-password');
        
        // Student User Management
        Route::get('/students/{student}/create-user', [UserController::class, 'createStudentUser'])->name('users.create-student-user');
        Route::post('/students/{student}/create-user', [UserController::class, 'storeStudentUser'])->name('users.store-student-user');
        
        // Settings
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

        // Bulk Results Upload
        Route::get('/bulkresults/upload', [BulkResultController::class, 'showUploadForm'])->name('bulkresults.upload');
        Route::post('/bulkresults/process', [BulkResultController::class, 'processUpload'])->name('bulkresults.process');
        Route::get('/bulkresults/semesters', [BulkResultController::class, 'getSemesters'])->name('bulkresults.semesters');
        Route::get('/bulkresults/courses', [BulkResultController::class, 'getCourses'])->name('bulkresults.courses');
        Route::get('/bulkresults/template/{type?}', [BulkResultController::class, 'downloadTemplate'])->name('bulkresults.template');
        
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
    });
});
