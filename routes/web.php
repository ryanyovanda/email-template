<?php

use App\Http\Controllers\Admin\AiUsageController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\TemplateController as AdminTemplateController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AiDraftController;
use App\Http\Controllers\ApplicantProfileController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\ApplicationExportController;
use App\Http\Controllers\ApplicationRenderController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TemplateGalleryController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('profile/setup', [ApplicantProfileController::class, 'edit'])->name('applicant-profile.edit');
    Route::post('profile/setup', [ApplicantProfileController::class, 'update'])->name('applicant-profile.update');

    Route::middleware('profile.complete')->group(function () {
        Route::get('templates', [TemplateGalleryController::class, 'index'])->name('templates.index');

        Route::get('applications', [ApplicationController::class, 'index'])->name('applications.index');
        Route::post('applications', [ApplicationController::class, 'store'])->name('applications.store');
        Route::get('applications/{application}/edit', [ApplicationController::class, 'edit'])->name('applications.edit');
        Route::put('applications/{application}', [ApplicationController::class, 'update'])->name('applications.update');
        Route::delete('applications/{application}', [ApplicationController::class, 'destroy'])->name('applications.destroy');

        Route::post('applications/{application}/render', ApplicationRenderController::class)->name('applications.render');
        Route::post('applications/{application}/ai-draft', [AiDraftController::class, 'store'])->name('applications.ai-draft');
        Route::get('applications/{application}/download', [ApplicationExportController::class, 'download'])->name('applications.download');
        Route::post('applications/{application}/copied', [ApplicationExportController::class, 'markCopied'])->name('applications.copied');
    });
});

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminDashboardController::class)->name('dashboard');

    Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
    Route::post('users/{user}/ban', [AdminUserController::class, 'ban'])->name('users.ban');
    Route::delete('users/{user}/ban', [AdminUserController::class, 'unban'])->name('users.unban');
    Route::patch('users/{user}/limit', [AdminUserController::class, 'updateLimit'])->name('users.limit');
    Route::delete('users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

    Route::get('templates', [AdminTemplateController::class, 'index'])->name('templates.index');
    Route::get('templates/create', [AdminTemplateController::class, 'create'])->name('templates.create');
    Route::post('templates', [AdminTemplateController::class, 'store'])->name('templates.store');
    Route::get('templates/{template}/edit', [AdminTemplateController::class, 'edit'])->name('templates.edit');
    Route::put('templates/{template}', [AdminTemplateController::class, 'update'])->name('templates.update');
    Route::delete('templates/{template}', [AdminTemplateController::class, 'destroy'])->name('templates.destroy');
    Route::post('templates/analyse', [AdminTemplateController::class, 'analyse'])->name('templates.analyse');

    Route::get('ai-usage', [AiUsageController::class, 'index'])->name('ai-usage.index');
});

require __DIR__.'/settings.php';
