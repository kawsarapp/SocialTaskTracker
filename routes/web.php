<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\AnalyticsController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth'])->group(function () {
    // Dashboard routing based on user role
    Route::get('/dashboard', function () {
        $user = Auth::user();
        if ($user->isSuperAdmin()) {
            return redirect()->route('superadmin.index');
        }
        return redirect()->route('tasks.index');
    })->name('dashboard');

    // Attendance Management
    Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn'])->name('attendance.check-in');
    Route::post('/attendance/check-out', [AttendanceController::class, 'checkOut'])->name('attendance.check-out');
    Route::post('/settings/attendance/leave', [AttendanceController::class, 'storeLeave'])->name('settings.attendance.leave');

    // Task Management
    Route::get('/calendar', [TaskController::class, 'calendar'])->name('tasks.calendar');
    Route::get('/tasks/fetch-metadata', [TaskController::class, 'fetchMetadata'])->name('tasks.fetch-metadata');
    Route::resource('tasks', TaskController::class);
    Route::post('/tasks/{task}/comments', [CommentController::class, 'store'])->name('tasks.comments.store');
    Route::get('/tasks/comments/poll', [CommentController::class, 'poll'])->name('tasks.comments.poll');
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

    // Tenant Configuration Panel (Admins and Super Admins)
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings/task-types', [SettingsController::class, 'storeTaskType'])->name('settings.task-types.store');
    Route::delete('/settings/task-types/{taskType}', [SettingsController::class, 'destroyTaskType'])->name('settings.task-types.destroy');
    
    Route::post('/settings/fb-pages', [SettingsController::class, 'storeFbPage'])->name('settings.fb-pages.store');
    Route::delete('/settings/fb-pages/{facebookPage}', [SettingsController::class, 'destroyFbPage'])->name('settings.fb-pages.destroy');
    
    Route::post('/settings/yt-channels', [SettingsController::class, 'storeYtChannel'])->name('settings.yt-channels.store');
    Route::delete('/settings/yt-channels/{youtubeChannel}', [SettingsController::class, 'destroyYtChannel'])->name('settings.yt-channels.destroy');
    
    Route::post('/settings/areas', [SettingsController::class, 'storeArea'])->name('settings.areas.store');
    Route::delete('/settings/areas/{area}', [SettingsController::class, 'destroyArea'])->name('settings.areas.destroy');
    
    Route::post('/settings/teams', [SettingsController::class, 'storeTeam'])->name('settings.teams.store');
    Route::delete('/settings/teams/{team}', [SettingsController::class, 'destroyTeam'])->name('settings.teams.destroy');
    
    Route::post('/settings/shifts', [SettingsController::class, 'storeShift'])->name('settings.shifts.store');
    Route::delete('/settings/shifts/{shift}', [SettingsController::class, 'destroyShift'])->name('settings.shifts.destroy');
    
    Route::post('/settings/users', [SettingsController::class, 'storeUser'])->name('settings.users.store');
    Route::post('/settings/webhooks', [SettingsController::class, 'updateWebhookSettings'])->name('settings.webhooks.update');

    // Super Admin Portal
    Route::get('/superadmin', [SuperAdminController::class, 'index'])->name('superadmin.index');
    Route::post('/superadmin/tenants', [SuperAdminController::class, 'storeTenant'])->name('superadmin.tenants.store');
    Route::post('/superadmin/tenants/{tenant}/toggle', [SuperAdminController::class, 'toggleTenant'])->name('superadmin.tenants.toggle');

    // Profile Management
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
