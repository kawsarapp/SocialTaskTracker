<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\SocialPublishController;
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
    Route::get('/tasks/export', [TaskController::class, 'exportCsv'])->name('tasks.export');
    Route::get('/tasks/fetch-metadata', [TaskController::class, 'fetchMetadata'])->name('tasks.fetch-metadata');
    Route::get('/tasks/comments/poll', [CommentController::class, 'poll'])->name('tasks.comments.poll');
    Route::post('/tasks/bulk-action', [TaskController::class, 'bulkAction'])->name('tasks.bulk-action');
    Route::patch('/tasks/{task}/quick-links', [TaskController::class, 'quickUpdateLinks'])->name('tasks.quick-links');
    Route::resource('tasks', TaskController::class);
    Route::post('/tasks/{task}/sync-stats', [TaskController::class, 'syncSingleTask'])->name('tasks.sync-stats');
    Route::post('/tasks/{task}/comments', [CommentController::class, 'store'])->name('tasks.comments.store');
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
    Route::post('/social-publish', [SocialPublishController::class, 'store'])->name('social-publish.store');
    Route::get('/social-publish/history', [SocialPublishController::class, 'history'])->name('social-publish.history');

    // Tenant Configuration Panel (Admins and Super Admins)
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings/switch-tenant', [SettingsController::class, 'switchTenant'])->name('settings.switch-tenant');
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
    
    Route::post('/settings/designations', [SettingsController::class, 'storeDesignation'])->name('settings.designations.store');
    Route::delete('/settings/designations/{designation}', [SettingsController::class, 'destroyDesignation'])->name('settings.designations.destroy');
    Route::put('/settings/designations/{designation}/permissions', [SettingsController::class, 'updateDesignationPermissions'])->name('settings.designations.permissions.update');
    Route::put('/settings/designations/{designation}/toggle-column', [SettingsController::class, 'toggleTaskColumn'])->name('settings.designations.toggle-column');
    
    Route::post('/settings/users', [SettingsController::class, 'storeUser'])->name('settings.users.store');
    Route::post('/settings/task-form-config', [SettingsController::class, 'updateTaskFormConfig'])->name('settings.task-form-config.update');
    Route::post('/settings/webhooks', [SettingsController::class, 'updateWebhookSettings'])->name('settings.webhooks.update');
    Route::post('/settings/api-keys', [SettingsController::class, 'updateApiSettings'])->name('settings.api-keys.update');
    Route::post('/settings/api-keys/sync', [SettingsController::class, 'triggerBulkSync'])->name('settings.api-keys.sync');

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
