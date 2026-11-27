<?php

use App\Http\Controllers\Auth\LoginController;
use App\Livewire\AcademicYears\AcademicYearIndex;
use App\Livewire\AcademicYears\EditAcademicYear;
use App\Livewire\Classes\ClassIndex;
use App\Livewire\Dashboard;
use App\Livewire\Members\MemberIndex;
use App\Livewire\Platform\PlatformSchools;
use App\Livewire\School\SchoolSettings;
use App\Livewire\Schools\NoSchool;
use App\Livewire\Schools\SelectSchool;
use App\Livewire\Staff\StaffProfileIndex;
use App\Livewire\Students\EditStudent;
use App\Livewire\Students\StudentIndex;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login')->name('login.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/schools/select', SelectSchool::class)->name('schools.select');
    Route::get('/no-school', NoSchool::class)->name('schools.none');

    Route::middleware('school')->group(function () {
        Route::get('/', Dashboard::class)->name('dashboard');
        Route::get('/academic-years', AcademicYearIndex::class)->name('academic-years.index');
        Route::get('/academic-years/{academicYear}/edit', EditAcademicYear::class)->name('academic-years.edit');
        Route::get('/students', StudentIndex::class)->name('students.index');
        Route::get('/students/{student}/edit', EditStudent::class)->name('students.edit');
        Route::get('/classes', ClassIndex::class)->name('classes.index');
        Route::get('/staff', StaffProfileIndex::class)->name('staff.index');
        Route::get('/members', MemberIndex::class)->name('members.index');
        Route::get('/settings', SchoolSettings::class)->name('school.settings');
    });

    Route::middleware('platform')->prefix('platform')->name('platform.')->group(function () {
        Route::get('/schools', PlatformSchools::class)->name('schools.index');
    });
});
