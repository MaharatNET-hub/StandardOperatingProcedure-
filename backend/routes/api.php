<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChecklistController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\LicenseController;
use App\Http\Controllers\Api\PerformanceReportController;
use App\Http\Controllers\Api\PluginRequestController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ProjectPhaseController;
use App\Http\Controllers\Api\QaReviewController;
use App\Http\Controllers\Api\SignoffController;
use App\Http\Controllers\Api\ToolController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/tools', [ToolController::class, 'index']);

    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store'])->middleware('role:admin');

    Route::apiResource('projects', ProjectController::class)->except(['destroy']);

    Route::patch('/projects/{project}/phases/{phase}', [ProjectPhaseController::class, 'update']);

    Route::get('/projects/{project}/checklist', [ChecklistController::class, 'index']);
    Route::patch('/projects/{project}/checklist/{item}', [ChecklistController::class, 'update']);
    Route::post('/projects/{project}/checklist/{item}/evidence', [ChecklistController::class, 'storeEvidence']);
    Route::delete('/evidence/{evidence}', [ChecklistController::class, 'destroyEvidence']);

    Route::get('/projects/{project}/plugin-requests', [PluginRequestController::class, 'index']);
    Route::post('/projects/{project}/plugin-requests', [PluginRequestController::class, 'store']);
    Route::patch('/plugin-requests/{pluginRequest}/decide', [PluginRequestController::class, 'decide'])
        ->middleware('role:admin,it_specialist');

    Route::get('/projects/{project}/licenses', [LicenseController::class, 'index']);
    Route::post('/projects/{project}/licenses', [LicenseController::class, 'store']);
    Route::patch('/projects/{project}/licenses/{license}', [LicenseController::class, 'update']);
    Route::delete('/projects/{project}/licenses/{license}', [LicenseController::class, 'destroy']);

    Route::get('/projects/{project}/performance-reports', [PerformanceReportController::class, 'index']);
    Route::post('/projects/{project}/performance-reports', [PerformanceReportController::class, 'store']);

    Route::get('/projects/{project}/qa-reviews', [QaReviewController::class, 'index']);
    Route::get('/projects/{project}/qa-reviews/latest', [QaReviewController::class, 'latest']);
    Route::post('/projects/{project}/qa-reviews', [QaReviewController::class, 'store'])
        ->middleware('role:admin,qa_reviewer');
    Route::patch('/qa-reviews/{qaReview}/items/{qaReviewItem}', [QaReviewController::class, 'updateItem'])
        ->middleware('role:admin,qa_reviewer');
    Route::post('/qa-reviews/{qaReview}/submit', [QaReviewController::class, 'submit'])
        ->middleware('role:admin,qa_reviewer');

    Route::post('/projects/{project}/signoffs', [SignoffController::class, 'store'])
        ->middleware('role:admin');
});
